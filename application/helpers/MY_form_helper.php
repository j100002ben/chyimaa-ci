<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

function form_radios($name, $options, $defaults = array(), $args = array()){
	return _form_check_input('radio', $name, $options, $defaults, $args);
}

function form_checkboxs($name, $options, $defaults = array(), $args = array()){
	return _form_check_input('checkbox', $name, $options, $defaults, $args);
}

function _form_check_input($type, $name, $options, $defaults = array(), $args = array()){
	static $input_indexs = array();
	
	if( is_string($defaults) ) $defaults = array($defaults);
	if($type != 'radio' && $type != 'checkbox') return '';
	$form_set_func = "set_{$type}";
	$output = '';
	$input_id = '';
	$input_name = ($type == 'checkbox') ? "{$name}[]" : $name;
	$inline = isset($args['inline']) && $args['inline'] ? 'inline' : '';
	if(!isset($input_indexs[$name])) $input_indexs[$name] = 1;
	foreach($options as $value => $title){
		$input_id = $name . '-' . $input_indexs[$name];
		$value = form_prep($value);
		$output .= "<label class=\"{$type} {$inline}\" for=\"{$input_id}\">" . 
			" <input type=\"{$type}\" name=\"{$input_name}\" id=\"{$input_id}\" value=\"{$value}\"" . 
			$form_set_func($input_name, $value, in_array($value, $defaults)) . 
			"> {$title}</label>";
		$input_indexs[$name] += 1;
	}
	return $output;
}
                
/* End of file MY_form_helper.php */
/* Location: ./application/helpers/MY_form_helper.php */