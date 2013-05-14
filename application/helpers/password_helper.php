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

function random_password_salt($len = 8)
{
	$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ`~!@#$%^&*()_+-=[]{}|;:,.<>/?`~!@#$%^&*()_+-=[]{}|;:,.<>/?';
	$str = '';
	for ($i=0; $i < $len; $i++)
	{
		$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
	}
	return $str;
}

function login_password_hash($account, $password, $salt = '')
{
	if( empty($salt) || !is_string($salt)){
		$password_salt = random_password_salt(64) . uniqid('', true);
	}else{
		$password_salt = $salt;
	}
	$password .= $password_salt;
	
	$crypt_salt_string = wp_hash($account . microtime(), 'nonce');
	$crypt_salt_string = substr($crypt_salt_string, rand(0, strlen($crypt_salt_string)-16), 16);
	
	$rand_rounds = 499999 + mt_rand(1, 9999);
	
	$password_hash = crypt($password, '$6$rounds=' . $rand_rounds . '$' . $crypt_salt_string);
	
	return array($password_hash, $password_salt);
}

function check_login_password($password, $password_salt, $hash_password)
{
	$password .= $password_salt;
	
	return crypt($password, $hash_password) == $hash_password;
}



/* End of file password_helper.php */
/* Location: ./application/helpers/password_helper.php */