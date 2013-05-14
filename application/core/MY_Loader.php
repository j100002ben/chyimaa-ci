<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*

Copyright (C) 2012 Benjamin Peng(j100002ben@gmail.com)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/


class MY_Loader extends CI_Loader {
	
	protected $_ci_layout_paths		= array();
	protected $_ci_layouts			= array();
	
	protected $_ci_interface_paths	= array();
	protected $_ci_interface		= array();
	
    function __construct()
    {
        parent::__construct();
		$this->_ci_layout_paths = array(APPPATH);
		$this->_ci_interface_paths = array(APPPATH);
    }
	
	/**
	 * Load Layout
	 *
	 * This function loads the specified layout file.
	 *
	 * @param	mixed
	 * @return	void
	 */
	public function layout($layout)
	{
		$old_layout = $layout;
		$layout = strtolower($layout);
		$layout_classname = ucfirst($layout) . 'Layout';
		$layout_filenames = array(
			$old_layout,
			ucfirst($layout), 
			$layout, 
			$old_layout.'_layout',
			ucfirst($layout).'_layout', 
			$layout.'_layout', 
			strtoupper($layout));
		
		if (isset($this->_ci_layouts[$layout_classname]))
		{
			return $this->_ci_layouts[$layout_classname];
		}

		// Try to load the layout
		foreach ($this->_ci_layout_paths as $path)
		{
			foreach ($layout_filenames as $layout_filename)
			{
				if (file_exists($path.'layouts/'.$layout_filename.'.php'))
				{
					include_once($path.'layouts/'.$layout_filename.'.php');

					$this->_ci_layouts[$layout_classname] = new $layout_classname();
					log_message('debug', 'Layout loaded: '.$layout);
					return $this->_ci_layouts[$layout_classname];
				}
			}
		}

		// unable to load the layout
		if ( ! isset($this->_ci_layouts[$layout_classname]))
		{
			show_error('Unable to load the requested file: layouts/'.$old_layout);
		}
		
		return NULL;
	}
	
	/**
	 * Load Interface
	 *
	 * This function loads the specified interface file.
	 *
	 * @param	mixed
	 * @return	void
	 */
	public function interface_class($interface = array())
	{
		if(is_array($interface)){
			foreach($interface as $i){
				$this->interface_class($i);
			}
			return ;
		}
		$path = '';
		// Is the interface in a sub-folder? If so, parse out the filename and path.
		if (($last_slash = strrpos($interface, '/')) !== FALSE)
		{
			// The path is in front of the last slash
			$path = substr($interface, 0, $last_slash + 1);

			// And the interface name behind it
			$interface = substr($interface, $last_slash + 1);
		}
		
		$old_interface = $interface;
		$interface = strtolower($interface);
		$interface_filenames = array(
			$old_interface,
			ucfirst($interface), 
			$interface, 
			$old_interface.'_interface',
			ucfirst($interface).'_interface', 
			$interface.'_interface', 
			strtoupper($interface));
		
		if (isset($this->_ci_interface[$interface]))
		{
			return ;
		}

		// Try to load the layout
		foreach ($this->_ci_interface_paths as $basepath)
		{
			foreach ($interface_filenames as $interface_filename)
			{
				if (file_exists($basepath.$path.$interface_filename.'.php'))
				{
					include_once($basepath.$path.$interface_filename.'.php');

					$this->_ci_interface[$interface] = $interface;
					log_message('debug', 'Interface loaded: '.$interface);
					return ;
				}
			}
		}

		// unable to load the layout
		if ( ! isset($this->_ci_interface[$interface]))
		{
			show_error('Unable to load the requested file: '.$old_interface);
		}
		
		return NULL;
	}

	// --------------------------------------------------------------------

	/**
	 * Load Interfaces
	 *
	 * This is simply an alias to the above function in case the
	 * user has written the plural form of this function.
	 *
	 * @param	array
	 * @return	void
	 */
	public function interfaces($interfaces = array())
	{
		$this->interface_class($interfaces);
	}
	
}