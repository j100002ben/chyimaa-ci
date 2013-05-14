<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

abstract class Authentication 
{
	
	protected $CI = NULL;
	protected $model = NULL;
	protected $error = array();
	protected $auth_cookie = NULL;
	protected $auth_login = NULL;
	protected $is_set_auth_cookie = FALSE;
	protected $is_clear_auth_cookie = FALSE;
	
	public function __construct()
	{
		$this->CI =& get_instance();
		$this->CI->load->library('encrypt');
		$this->CI->load->helper('password');
		$this->init_auth_session();
	}
	
	public function __call($method, $arguments) 
	{
		if($this->model == null){
			show_error("You need to load auth target model.");
		}
		
		if(empty($arguments) || !is_array($arguments))
			call_user_func(array($this, $method));
		else
			call_user_func_array(array($this, $method), $arguments);
	}
	
	public function is_account_available($login_account)
	{
		if(preg_match('/(admin|root|webmaster|server|sys|master|adm|www|test|yuelao|ylb|\|)/imus', $login_account)) return FALSE;
		
		return TRUE;
	}
	
	/**
	 * Check if user logged in. Also test if user is activated or not.
	 *
	 * @param	bool
	 * @return	bool
	 */
	public function is_logged_in($logout = FALSE)
	{
		$login_status = $this->validate_auth_cookie( '',  '');
		if($login_status === FALSE && $logout){
			$this->account_logout();
		}
		if($login_status !== FALSE){
			$this->auth_cookie = $this->parse_auth_cookie('', '');
		}
		return $login_status;
	}

	/**
	 * Get person_id
	 *
	 * @return	string
	 */
	public function get_person_id()
	{
		$this->set_auth_login();
		if($this->auth_login){
			return $this->auth_login->person_id;
		}
		return FALSE;
	}
	
	/**
	 * Get login_id
	 *
	 * @return	string
	 */
	public function get_login_id()
	{
		$this->set_auth_login();
		if($this->auth_login){
			return $this->auth_login->login_id;
		}
		return FALSE;
	}
	
	/**
	 * Get login object
	 *
	 * @return	string
	 */
	public function get_login()
	{
		$this->set_auth_login();
		if($this->auth_login){
			return $this->auth_login;
		}
		return FALSE;
	}
	
	protected function set_auth_login()
	{
		if($this->auth_login == NULL){
			$login_account = $this->get_login_account();
			if($login_account){
				$login = $this->model->get_login($login_account, 'login_account');
				if($login){
					$this->auth_login = $login;
				}
			}
		}
	}

	/**
	 * Get account
	 *
	 * @return	string
	 */
	public function get_login_account()
	{
		if($this->auth_cookie){
			return $this->auth_cookie['login_account'];
		}
		return FALSE;
	}
	
	/**
	 * Get capacities
	 *
	 * @return	string
	 */
	public function get_login_capacities()
	{
		$capacities = FALSE;
		$login_id = $this->get_login_id();
		if($login_id){
			$capacities = $this->model->get_capacities($login_id);
			return $capacities;
		}
		return $capacities;
	}
	
	/**
	 * Login user on the site. Return TRUE if login is successful
	 * (user exists and activated, password is correct), otherwise FALSE.
	 *
	 * @param	string	(user account)
	 * @param	string
	 * @param	bool
	 * @return	bool
	 */
	public function account_login($login_account, $password, $login_attempts_limit = 5, $expire_period = 86400)
	{
		$ip_address = $this->CI->input->ip_address();
		
		if(!empty($login_account) && !empty($password)) {
			$login_attempts = $this->model->get_login_attempts_num($ip_address, $login_account, $expire_period);
			if($login_attempts >= $login_attempts_limit){
				$this->error = array(
					'login_attempts' => '登入錯誤次數過多。'
					);
				return FALSE;
			}
			if($this->is_account_available($login_account) == FALSE){
				$this->error = array(
					'rescrited_account' => '無法使用的帳號。'
					);
				return FALSE;
			}
			
			if(!is_null($login = $this->model->get_login($login_account, 'login_account'))) {	// login_account ok
				
				if(check_login_password($password, $login->login_password_salt, $login->login_password)) {		// password ok

					if ($login->login_status == 'banned') {									// fail - banned
						$this->error = array(
							'banned' => $login->login_ban_reason
							);
					}else if( $login->login_onactivate_time > time() || 
							  ( $login->login_deactivate_time != 0 && $login->login_deactivate_time < time() ) 
							){
						$this->error = array(
							'not_activated' => '此帳號尚未生效或以經過期。'
							);
					} else {
						$this->model->clear_login_attempt($ip_address, $login_account, $expire_period);
						$this->model->update_login_info($login);
						
						$this->set_auth_cookie($login);
						$this->set_auth_session($login);

						return TRUE;
					}
				} else {														// fail - wrong password
					$this->model->increase_login_attempt($ip_address, $login_account);
					$this->error = array(
						'password' => '密碼錯誤。'
						);
				}
			} else {															// fail - wrong login
				$this->model->increase_login_attempt($ip_address, $login_account);
				$this->error = array(
					'login' => '帳號錯誤。'
					);
			}
		}
		
		return FALSE;
	}
	
	public function account_logout()
	{
		if($this->auth_login){
			$this->model->update_logout_info($this->auth_login);
		}
		$this->clear_auth_cookie();
		$this->clear_auth_session();
	}
	
	protected function set_auth_cookie($login){
		if($this->is_set_auth_cookie == TRUE) return ;
		
		$expiration = time() + 3600;
		$expire = 0;
		$secure = is_ssl();
		if ( $secure ) {
			$auth_cookie_name = SECURE_AUTH_COOKIE;
			$scheme = 'secure_auth';
		} else {
			$auth_cookie_name = AUTH_COOKIE;
			$scheme = 'auth';
		}
		$auth_cookie = $this->generate_auth_cookie($login->login_account, $login->login_password, $expiration, $scheme);
		$logged_in_cookie = $this->generate_auth_cookie($login->login_account, $login->login_password, $expiration, 'logged_in');
		// Set httponly if the php version is >= 5.2.0
		if ( is_php('5.2.0') ) {
			setcookie($auth_cookie_name, $auth_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, $secure, TRUE);
			setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, FALSE, TRUE);
		} else {
			$cookie_domain = COOKIE_DOMAIN;
			if ( !empty($cookie_domain) )
				$cookie_domain .= '; HttpOnly';
			setcookie($auth_cookie_name, $auth_cookie, $expire, ADMIN_COOKIE_PATH, $cookie_domain, $secure);
			setcookie(LOGGED_IN_COOKIE, $logged_in_cookie, $expire, ADMIN_COOKIE_PATH, $cookie_domain, FALSE);
		}
		
		$this->is_set_auth_cookie = TRUE;
	}
	
	protected function validate_auth_cookie($cookie = '', $scheme = '') {
		if ( ! $cookie_elements = $this->parse_auth_cookie($cookie, $scheme) ) {
			return FALSE;
		}

		extract($cookie_elements, EXTR_OVERWRITE);

		$expired = $expiration;

		// Quick check to see if an honest cookie has expired
		if ( $expired < time() ) {
			return FALSE;
		}

		$login = $this->model->get_login($login_account, 'login_account');
		if ( is_null($login) ) {
			return FALSE;
		}

		$pass_frag = substr($login->login_password, 8, 4);

		$key = wp_hash($login_account . $pass_frag . '|' . $expiration, $scheme);
		$hash = $this->CI->encrypt->phash($login_account . '|' . $expiration . $key, $cookiehash, 'sha512');
		
		if ( $cookiehash != $hash ) {
			return FALSE;
		}
		
		if ( $expired < (time() + 3600) - 30 ) {
			$this->set_auth_cookie($login);
		}
		
		return TRUE;
	}
	
	protected function parse_auth_cookie($cookie = '', $scheme = '') {
		if ( empty($cookie) ) {
			switch ($scheme){
				case 'auth':
					$cookie_name = AUTH_COOKIE;
					break;
				case 'secure_auth':
					$cookie_name = SECURE_AUTH_COOKIE;
					break;
				case "logged_in":
					$cookie_name = LOGGED_IN_COOKIE;
					break;
				default:
					if ( is_ssl() ) {
						$cookie_name = SECURE_AUTH_COOKIE;
						$scheme = 'secure_auth';
					} else {
						$cookie_name = AUTH_COOKIE;
						$scheme = 'auth';
					}
			}

			if ( empty($_COOKIE[$cookie_name]) )
				return FALSE;
			$cookie = $_COOKIE[$cookie_name];
		}

		$cookie_elements = explode('|', $cookie);
		if ( count($cookie_elements) != 3 )
			return FALSE;

		list($login_account, $expiration, $cookiehash) = $cookie_elements;

		return compact('login_account', 'expiration', 'cookiehash', 'scheme');
	}
	
	protected function generate_auth_cookie($login_account, $password_hash, $expiration, $scheme = 'auth'){
		$pass_frag = substr($password_hash, 8, 4);
		
		$key = wp_hash($login_account . $pass_frag . '|' . $expiration, $scheme);
		$hash = $this->CI->encrypt->phash($login_account . '|' . $expiration . $key, NULL, 'sha512');

		$cookie = $login_account . '|' . $expiration . '|' . $hash;
		return $cookie;
	}
	
	protected function clear_auth_cookie() {
		if($this->is_clear_auth_cookie == TRUE) return ;
		
		$this->auth_login = NULL;
		$this->auth_cookie = NULL;
		setcookie(AUTH_COOKIE, ' ', time() - 31536000, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
		setcookie(SECURE_AUTH_COOKIE, ' ', time() - 31536000, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
		setcookie(LOGGED_IN_COOKIE, ' ', time() - 31536000, ADMIN_COOKIE_PATH, COOKIE_DOMAIN);
		
		$this->is_clear_auth_cookie = TRUE;
	}
	
	protected function init_auth_session(){}
	
	protected function set_auth_session($login){}
	
	protected function clear_auth_session(){}
	
	/**
	 * Get error message.
	 * Can be invoked after any failed operation such as login or register.
	 *
	 * @return	string
	 */
	public function get_error_message()
	{
		return $this->error;
	}
	
}


/* End of file Authentication.php */
/* Location: ./application/libraries/Authentication.php */