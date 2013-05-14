<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

$CI =& get_instance();
$CI->load->interface_class(array(
	'libraries/authentication'
));
class Admin_authentication extends Authentication 
{
	
	public function __construct()
	{
		parent::__construct();
		$this->CI->load->model('admin_model');
		$this->model = &$this->CI->admin_model;
		$this->is_logged_in();
	}
	
	protected function init_auth_session()
	{
		
		$this->CI->load->driver('civ3_session');
		
	}
	
	protected function set_auth_session($login)
	{
		$role_names = $this->model->get_role_names($login->login_id, 'login_id');
		$session_data = array(
			'login_id'	=> $login->login_id,
			'person_id' => $login->person_id,
			'login_account'	=> $login->login_account,
			'login_username' => $login->login_username,
			'login_last_login' => $login->login_last_login,
			'login_last_ip' => $login->login_last_ip,
			'login' => $login,
			'role_names' => implode(', ', $role_names)
		);
		$this->CI->civ3_session->set_userdata($session_data);
	}
	
	protected function clear_auth_session()
	{
		$this->CI->civ3_session->set_userdata(array('login_id' => '', 'login_account' => ''));
		$this->CI->civ3_session->sess_destroy();
	}
	
}

/* End of file Admin_authentication.php */
/* Location: ./application/libraries/Admin_authentication.php */