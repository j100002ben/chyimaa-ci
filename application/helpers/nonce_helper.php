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

function nonce_link($tag, $id){
	$CI =& get_instance();
	$CI->load->library('encrypt');
	$account = $CI->db_session->userdata('account');
	if(!$account) $account = '';
	$key = NONCE_KEY . $tag . '-' . $id . '-' . md5($account);
	$str = NONCE_SALT . $tag . '-' . $id . '-' . md5($account);
	
	$key = md5(md5($key) . NONCE_KEY);
	$str = $CI->encrypt->phash($str, '', 'sha384');
	
	return "{$key}={$str}";
}

function nonce_link_check($tag, $id){
	$CI =& get_instance();
	$CI->load->library('encrypt');
	$account = $CI->db_session->userdata('account');
	if(!$account) $account = '';
	$key = NONCE_KEY . $tag . '-' . $id . '-' . md5($account);
	$str = NONCE_SALT . $tag . '-' . $id . '-' . md5($account);
	
	$key = md5(md5($key) . NONCE_KEY);
	if(empty($_GET[$key])) return FALSE;
	$hash = $_GET[$key];
	
	return $CI->encrypt->phash($str, $hash, 'sha384') === $hash;
}

function nonce_field($tag, $id){
	$CI =& get_instance();
	$CI->load->library('encrypt');
	$account = $CI->db_session->userdata('account');
	if(!$account) $account = '';
	$str = NONCE_SALT . $tag . '-' . $id . '-' . md5($account);
	
	return $CI->encrypt->phash($str, '', 'sha384');
}

function nonce_field_check($tag, $id, $hash){
	$CI =& get_instance();
	$CI->load->library('encrypt');
	$account = $CI->db_session->userdata('account');
	if(!$account) $account = '';
	$str = NONCE_SALT . $tag . '-' . $id . '-' . md5($account);
	
	return $CI->encrypt->phash($str, $hash, 'sha384') === $hash;
}

/* End of file layout_helper.php */
/* Location: ./application/helpers/layout_helper.php */