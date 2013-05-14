<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');


function wp_hash($data, $scheme = 'auth') {
	$salt = wp_salt($scheme);

	return hash_hmac('sha1', $data, $salt);
}

function wp_salt($scheme = 'auth') {
	$secret_key = '';
	if ( defined('SECRET_KEY') && ('' != SECRET_KEY) )
		$secret_key = SECRET_KEY;

	if ( 'auth' == $scheme ) {
		if ( defined('AUTH_KEY') && ('' != AUTH_KEY) )
			$secret_key = AUTH_KEY;

		if ( defined('AUTH_SALT') && ('' != AUTH_SALT) ) {
			$salt = AUTH_SALT;
		} elseif ( defined('SECRET_SALT') && ('' != SECRET_SALT) ) {
			$salt = SECRET_SALT;
		} else {
			$salt = hash_hmac('md5', $scheme, $secret_key);
		}
	} elseif ( 'secure_auth' == $scheme ) {
		if ( defined('SECURE_AUTH_KEY') && ('' != SECURE_AUTH_KEY) )
			$secret_key = SECURE_AUTH_KEY;

		if ( defined('SECURE_AUTH_SALT') && ('' != SECURE_AUTH_SALT) ) {
			$salt = SECURE_AUTH_SALT;
		} else {
			$salt = hash_hmac('md5', $scheme, $secret_key);
		}
	} elseif ( 'logged_in' == $scheme ) {
		if ( defined('LOGGED_IN_KEY') && ('' != LOGGED_IN_KEY) )
			$secret_key = LOGGED_IN_KEY;

		if ( defined('LOGGED_IN_SALT') && ('' != LOGGED_IN_SALT) ) {
			$salt = LOGGED_IN_SALT;
		} else {
			$salt = hash_hmac('md5', $scheme, $secret_key);
		}
	} elseif ( 'nonce' == $scheme ) {
		if ( defined('NONCE_KEY') && ('' != NONCE_KEY) )
			$secret_key = NONCE_KEY;

		if ( defined('NONCE_SALT') && ('' != NONCE_SALT) ) {
			$salt = NONCE_SALT;
		} else {
			$salt = hash_hmac('md5', $scheme, $secret_key);
		}
	} else {
		// ensure each auth scheme has its own unique salt
		$salt = hash_hmac('md5', $scheme, $secret_key);
	}

	return $secret_key . $salt;
}


/* End of file wp_helper.php */
/* Location: ./application/helpers/wp_helper.php */