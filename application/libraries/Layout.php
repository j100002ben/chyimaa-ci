<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*

* Copyright (c) 2012, Benjamin Peng(j100002ben@gmail.com)
* All rights reserved.
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
*     * Redistributions of source code must retain the above copyright
*       notice, this list of conditions and the following disclaimer.
*     * Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*     * Neither the name of the author nor the names of its contributors may
*       be used to endorse or promote products derived from this software 
*       without specific prior written permission.
*
* THIS SOFTWARE IS PROVIDED BY BENJAMIN PENG AND CONTRIBUTORS "AS IS" AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL BENJAMIN PENG AND CONTRIBUTORS BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
* LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
* ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
* SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/


class Layout {
	
	private $CI;
	
    public function __construct()
    {
		$this->CI =& get_instance();
    }
	
	/**
	 * 呈現佈局組件
	 * @param string 佈局名稱
	 * @param string $component 組件名稱
	 * @param array $args 附加參數
	 */
	public function render($name, $component, $args = NULL, $output = TRUE)
	{
		$data = NULL;
		$layout = $this->CI->load->layout($name);
		
		if($layout != NULL && in_array(strtolower($component), array_map('strtolower', get_class_methods($layout)))){
			if(NULL !== $args){
				$data = call_user_func_array(array($layout,$component), $args);
			}else{
				$data = call_user_func(array($layout,$component));
			}
		}
		
		if( !is_array($data) )
			$data = is_array($args) ? $args : NULL;
		
		$result = $this->CI->load->view('_layout/' . $name . '/' . $component, $data, TRUE);
		if($output) echo $result;
		
		return NULL;
	}
	
}

