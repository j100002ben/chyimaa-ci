<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class EXCHANGE_Controller extends CI_Controller
{
	
    function __construct()
    {
        parent::__construct();
		$this->config->set_item('csrf_protection', FALSE);
		$this->output->parse_exec_vars = FALSE;
    }
	
}

class MY_Controller extends CI_Controller
{
	
	protected $sub_controller = FALSE;
	protected $parent;
	private $properties = array();
	private static $reflections = array();
	
	protected $directory_name;
	protected $class_name;
	protected $method_name;
	
    public function __construct($sub_controller = FALSE)
    {
    	if( ! $sub_controller){
    		parent::__construct();
    		$this->output->parse_exec_vars = FALSE;
    		$this->directory_name = $this->router->fetch_directory();
			$this->class_name = $this->router->fetch_class();
			$this->method_name = $this->router->fetch_method();
    	}
    }
    
    /**
	 * Decorate
	 *
	 * Decorates the child with the parent driver lib's methods and properties
	 *
	 * @param	object
	 * @return	void
	 */
	public function decorate($parent)
	{
		// Lock down attributes to what is defined in the class
		// and speed up references in magic methods
		
		$class_name = get_class($parent);
		
		if ( ! isset(self::$reflections[$class_name]))
		{
			$r = new ReflectionObject($parent);
			
			foreach ($r->getProperties() as $prop)
			{
				if ($prop->isPublic() || $prop->isProtected())
				{
					$var = $prop->getName();
					$this->$var =& $parent->$var;
					$this->properties[] = $var;
				}
			}
			$this->directory_name = $this->router->fetch_directory();
			$this->class_name = $this->router->fetch_class();
			$this->method_name = $this->router->fetch_method();

			self::$reflections[$class_name] = $this->properties;
		}
		else
		{
			$this->properties = self::$reflections[$class_name];
		}
	}
	
	public function _remap($method, $params = array())
	{
		$method_action = "{$method}_action";
		if (method_exists($this, $method_action)){
			$result = call_user_func_array(array($this, $method_action), $params);
			if(is_array($result)){
				$this->_change_controller($result[0], $result[1], array_slice($result, 2));
			}else if(is_string($result)){
				$this->load->helper('url');
				if(preg_match('#^https?://#', $result))
					redirect($result);
				else
					redirect(site_url($result));
			}else if(is_integer($result)){
				if($result == 404) {
					show_404();
					return ;
				}
				$this->output->set_status_header($result);
			}
			return ;
		}
		show_404();
	}

	protected function _change_controller($class, $method = 'index', $params = array())
	{
		if(empty($method)) $method = 'index';
		
		$RTR =& $this->router;
		if(empty($params)) $params = array_slice($this->uri->rsegments, 2);
		
		if ( ! file_exists(APPPATH.'controllers/'.$class.'.php')){
			show_error('Unable to load your default controller. Please make sure the controller specified in your Routes.php file is valid.');
		}
		
		include(APPPATH.'controllers/'.$class.'.php');
		if ( ! class_exists($class)
			OR strncmp($method, '_', 1) == 0
			OR in_array(strtolower($method), array_map('strtolower', get_class_methods('CI_Controller')))
			)
		{
			if ( ! empty($RTR->routes['404_override'])){
				$x = explode('/', $RTR->routes['404_override']);
				$class = $x[0];
				$method = (isset($x[1]) ? $x[1] : 'index');
				if ( ! class_exists($class)){
					if ( ! file_exists(APPPATH.'controllers/'.$class.'.php')){
						show_404("{$class}/{$method}");
					}
					include_once(APPPATH.'controllers/'.$class.'.php');
				}
			}else{
				show_404("{$class}/{$method}");
			}
		}
		$CI = new $class(TRUE);
		$CI->decorate($this);
		//print_r($CI);
		//exit();
		
		if (method_exists($CI, '_remap')){
			return $CI->_remap($method, $params);
		}else{
			// is_callable() returns TRUE on some versions of PHP 5 for private and protected
			// methods, so we'll use this workaround for consistent behavior
			if ( ! in_array(strtolower($method), array_map('strtolower', get_class_methods($CI)))){
				// Check and see if we are using a 404 override and use it.
				if ( ! empty($RTR->routes['404_override'])){
					$x = explode('/', $RTR->routes['404_override']);
					$class = $x[0];
					$method = (isset($x[1]) ? $x[1] : 'index');
					if ( ! class_exists($class)){
						if ( ! file_exists(APPPATH.'controllers/'.$class.'.php')){
							show_404("{$class}/{$method}");
						}
						include_once(APPPATH.'controllers/'.$class.'.php');
						unset($CI);
						$CI = new $class();
						foreach (is_loaded() as $var => $class)
						{
							$CI->$var = &$this->$var;
						}
						$CI->load = &$this->load;
					}
				}else{
					show_404("{$class}/{$method}");
				}
			}

			// Call the requested method.
			// Any URI segments present (besides the class/function) will be passed to the method for convenience
			return call_user_func_array(array(&$CI, $method), $params);
		}
		
	}
	
}

class ERROR_Controller extends MY_Controller
{
	
    public function __construct()
    {
        parent::__construct();
    }
	
	public function _remap($method, $params = array())
	{
		parent::_remap($method, $params);
	}
	
}

abstract class AUTH_Controller extends MY_Controller
{
	public $authlib = NULL;
	public static $person_id = FALSE;
	protected static $init_capacities = FALSE;
	protected static $capacities = FALSE;
	protected $noauth = array();
	
	public function __construct()
	{
		parent::__construct();
		$this->output->set_header('X-Powered-By: YuelaoBank');
		//$this->load->library('db_session');
		
		if($this->config->item('csrf_protection') == FALSE){
			$this->config->set_item('csrf_protection', TRUE);
			$this->security->csrf_verify();
		}
		
		$this->init();
	}
	
	public function __call($method, $arguments) 
	{
		if($method != 'init' && $this->authlib === null){
			show_error("You need to load auth library.");
		}
		
		if(empty($arguments) || !is_array($arguments))
			call_user_func(array($this, $method));
		else
			call_user_func_array(array($this, $method), $arguments);
	}
	
	abstract protected function init();
	abstract protected function set_auth_capacities();
	
	public function _remap($method, $params = array())
	{
		if ( ! in_array("{$method}_action", $this->noauth) && ! $this->authlib->is_logged_in(TRUE) ) {
			if($this->input->is_cli_request()){
				$this->output->set_output('Unauthorized');
			}else if( $this->input->is_ajax_request() ||
			    strpos($this->uri->uri_string(), '/ajax') !== FALSE ){
				$this->output->set_status_header('401');
			}else{
				//$this->db_session->set_flashdata('auth-login-error', array('超過限制的時間未操作。<br>系統強制登出。'));
				redirect('/index');
			}
			return ;
		}
		parent::_remap($method, $params);
	}

	public function error($class, $method = 'index')
	{
		return $this->_change_controller($class, $method);
	}

	public function can($cap)
	{
		if(self::$capacities === FALSE) return FALSE;
		if(isset(self::$capacities[0]) && self::$capacities[0] == 'full_capacities') return TRUE;
		
		return  isset(self::$capacities[$cap]);
	}
	
}

class ADMIN_AUTH_Controller extends AUTH_Controller
{
	
	public function __construct()
	{
		parent::__construct();
	}
	
	protected function init()
	{
		$this->load->library('admin_authentication');
		$this->authlib = &$this->admin_authentication;
		self::$person_id = $this->authlib->get_person_id();
		$this->set_auth_capacities();
	}
	
	protected function set_auth_capacities()
	{
		if(self::$init_capacities == FALSE){
			self::$capacities = $this->authlib->get_login_capacities();
		}
		self::$init_capacities = TRUE;
	}
	
	
}