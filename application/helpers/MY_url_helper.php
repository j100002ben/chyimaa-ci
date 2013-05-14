<?php

if( !function_exists('asset_url') )
{   
    function asset_url($relate = FALSE)
    {
        // the helper function doesn't have access to $this, so we need to get a reference to the 
        // CodeIgniter instance.  We'll store that reference as $CI and use it instead of $this
        $CI =& get_instance();
        $asset_path = $CI->config->item('asset_path');
        $asset_url = $$relate || empty($asset_path) ? base_url() . $asset_path : $asset_path;
        
        // return the asset_url
        return $asset_url;
    }
}

if( !function_exists('is_ssl') )
{   
    function is_ssl()
    {
        return isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
    }
}

if( !function_exists('is_post') )
{
	function is_post()
	{
		return strtolower($_SERVER['REQUEST_METHOD']) === 'post';
	}
}

if( !function_exists('is_get') )
{
	function is_get()
	{
		return strtolower($_SERVER['REQUEST_METHOD']) === 'get';
	}
}

if( !function_exists('window_open') )
{
	function window_open($url = '')
	{
		return "window.open('{$url}', '_blank', 'width=1100,height=600,scrollbars=yes,status=no,location=no,resizable=yes,screenx=100,screeny=50');";
	}
}

/* End of file url_helper.php */
/* Location: ./application/helpers/url_helper.php */