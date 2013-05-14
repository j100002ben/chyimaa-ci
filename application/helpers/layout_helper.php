<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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


function layout($name, $component, $args = null, $output = TRUE){
	
	// the helper function doesn't have access to $this, so we need to get a reference to the 
	// CodeIgniter instance.  We'll store that reference as $CI and use it instead of $this
	$CI =& get_instance();
	
	$CI->load->library('layout');
	$CI->layout->render($name, $component, $args, $output);
	
}

/* End of file layout_helper.php */
/* Location: ./application/helpers/layout_helper.php */