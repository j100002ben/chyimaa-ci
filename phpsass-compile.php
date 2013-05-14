<?php

/**
 * This file is intended to be used in conjunction with Apache2's mod_actions,
 * wherein you can have a .htaccess file like so for automatic compilation:
 *     Action compile-sass /git/phpsass/compile-apache.php
 *     AddHandler compile-sass .sass .scss
 */
define('CSS_CACHE', TRUE);

header('X-Powered-By: phpsass');
header('Content-type: text/css');

if( !function_exists('apache_request_headers') ) {
    function apache_request_headers() {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach($_SERVER as $key => $val) {
            if( preg_match($rx_http, $key) ) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                    foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return( $arh );
    }
}

$file_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$cache_path = __DIR__ . '/css/cache/';
$css_file = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . $file_path;
$syntax = strtolower(substr($css_file, -4, 4));
if( $syntax != 'scss' && $syntax != 'sass' ){
	exit();
}
$style = isset($_GET['style']) ? $_GET['style'] : 'expanded';
if(!is_string($style) || !in_array($style, array('compressed', 'expanded', 'nested', 'compact'))){
	$style = 'expanded';
}
$cache_file  = $cache_path . '/' . md5($file_path) . ".{$syntax}.{$style}.css";

// Execute the compiler.
try {
	if( CSS_CACHE ){
        $modify_time = file_modify_time($cache_file);
		if( file_modify_time($css_file) > $modify_time ){
			include './phpsass/SassParser.php';
            $options = phpsass_options($style, $syntax, $css_file);
			$parser = new SassParser($options);
			$css = $parser->toCss($css_file);
			file_put_contents($cache_file, $css);
            $modify_time = file_modify_time($cache_file);
            header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modify_time).' GMT', true, 200);
			echo $css;
		}else{
            $headers = apache_request_headers();
            if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == $modify_time)) {
                header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modify_time).' GMT', true, 304);
            }else{
                header('Last-Modified: '.gmdate('D, d M Y H:i:s', $modify_time).' GMT', true, 200);
                echo file_get_contents($cache_file);
            }
		}
	}else{
        include './phpsass/SassParser.php';
        $options = phpsass_options($style, $syntax, $css_file);
        $parser = new SassParser($options);
        echo $parser->toCss($css_file);
    }
} catch (Exception $e) {
	echo $e->getMessage();	
}

function phpsass_options($style, $syntax, $css_file)
{
    return array(
        'style' => $style,
        'cache' => CSS_CACHE,
        'syntax' => $syntax,
        'debug' => TRUE,
        'line_numbers' => TRUE,
        'callbacks' => array(
            'warn' => 'warn',
            'debug' => 'debug'
        ),
        'load_path_functions' => array('loadCallback'),
        'load_paths' => array(dirname($css_file)),
        'functions' => getFunctions(array('Compass')),
        'extensions' => array('Compass')
    );
}

function file_modify_time($file_path)
{
    clearstatcache();
	return file_exists($file_path) ? max( @filectime($file_path), @filemtime($file_path) ) : -1;
}

function warn($text, $context)
{
	print "/** WARN: $text, on line {$context->node->token->line} of {$context->node->token->filename} **/\n";
}
function debug($text, $context)
{
	print "/** DEBUG: $text, on line {$context->node->token->line} of {$context->node->token->filename} **/\n";
}
function loadCallback($file, $parser)
{
    $paths = array();
    foreach ($parser->extensions as $extensionName) {
        $namespace = ucwords(preg_replace('/[^0-9a-z]+/', '_', strtolower($extensionName)));
        $extensionPath = './phpsass/Extensions/' . $namespace . '/' . $namespace . '.php';
        if (file_exists($extensionPath)) {
            require_once($extensionPath);
            $hook = $namespace . '::resolveExtensionPath';
            $returnPath = call_user_func($hook, $file, $parser);
            if (!empty($returnPath)) {
                $paths[] = $returnPath;
            }

        }
    }
    return $paths;
}
function getFunctions($extensions)
{
    $output = array();
    if (!empty($extensions)) {
        foreach ($extensions as $extension) {
            $name = explode('/', $extension, 2);
            $namespace = ucwords(preg_replace('/[^0-9a-z]+/', '_', strtolower(array_shift($name))));
            $extensionPath = './phpsass/Extensions/' . $namespace . '/' . $namespace . '.php';
            if (file_exists(
                $extensionPath
            )
            ) {
                require_once($extensionPath);
                $namespace = $namespace . '::';
                $function = 'getFunctions';
                $output = array_merge($output, call_user_func($namespace . $function, $namespace));
            }
        }
    }
    return $output;
}