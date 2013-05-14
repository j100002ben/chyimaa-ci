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

class MY_Form_validation extends CI_Form_validation {
	
	private $current_field_name;
	private $current_list;
	
    public function __construct()
    {
        parent::__construct();
		$this->current_field_name = '';
		$this->current_list = array();
		
		$this->set_message('required', '[%s] 必需填寫。');
		$this->set_message('isset', '[%s] 必須選擇。');
		$this->set_message('valid_email', '[%s] 格式不合。');
		$this->set_message('valid_emails', '[%s] 包含格式不合的Email。（半形逗號分隔）');
		$this->set_message('valid_url', '[%s] 格式不合。');
		$this->set_message('valid_ip', '[%s] 格式不合。');
		$this->set_message('min_length', '[%s] 長度至少需要%s個字。');
		$this->set_message('max_length', '[%s] 長度不能超過%s個字。');
		$this->set_message('exact_length', '[%s] 長度必須是%s個字。');
		$this->set_message('alpha', '[%s] 只能填寫英文大小寫。');
		$this->set_message('alpha_numeric', '[%s] 只能填寫英文大小寫或是數字。');
		$this->set_message('alpha_dash', '[%s] 只能填寫英文大小寫、數字或是底線。');
		$this->set_message('numeric', '[%s] 只能填寫數字。');
		$this->set_message('is_numeric', '[%s] 只能有數字符號。');
		$this->set_message('integer', '[%s] 必須是整數。');
		$this->set_message('regex_match', '[%s] 格式不合。');
		$this->set_message('matches', '[%s] 欄位和 [%s] 欄位不相符。');
		$this->set_message('is_unique', '[%s] 和其他資料重複。');
		$this->set_message('is_unique_except_self', '[%s] 和其他資料重複。');
		$this->set_message('is_natural', '[%s] 只能是正整數。（包括0）');
		$this->set_message('is_natural_no_zero', '[%s] 只能是正整數。（不包括0）');
		$this->set_message('decimal', '[%s] 必須是小數格式。');
		$this->set_message('less_than', '[%s] 必須小於%s。');
		$this->set_message('greater_than', '[%s] 必須大於%s。');
		
		$this->set_message('alpha_dash_first', '[%s] 第一個字只能填寫英文大小寫或是底線。');
		$this->set_message('matches_gender', '[%s] 與性別不合。');
		$this->set_message('valid_tel', '[%s] 格式不合。（02-00000000）');
		$this->set_message('valid_mobile', '[%s] 格式不合。（0900-000000）');
		$this->set_message('valid_phone', '[%s] 格式不合。（0900-000000 / 02-00000000）');
		$this->set_message('valid_date', '[%s] 格式不合。（2012-01-01）');
		$this->set_message('valid_uid', '[%s] 格式不合。');
		$this->set_message('valid_list', '[%s] 不是允許的選項。');
		$this->set_message('custom_model', '[%s] 格式不合。');
		
    }
	
	public function _execute($row, $rules, $postdata = NULL, $cycles = 0)
    {
		if($row['field'] !== $this->current_field_name && in_array('valid_list', $rules)){
			global $form_template_list;
			$field = rtrim($row['field'],'[]');
			if( !empty($form_template_list[$field]) )
				$this->current_list = $form_template_list[$field];
			else
				$this->current_list = array();
		}
		$this->current_field_name = $row['field'];
		parent::_execute($row, $rules, $postdata, $cycles);
	}

	public function is_unique_except($str, $params)
	{
		list($table, $field)=explode(':', $params);
		$fields = explode(';', $field);
		$field = array_shift($fields);
		foreach($fields as $f){
			$f = trim($f);
			if(!empty($f))
				$this->CI->db->where($f, NULL, FALSE);
		}
		$query = $this->CI->db->limit(1)->where("{$field} =", $str)->get($table);
		
		return $query->num_rows() === 0;
	}
	
	public function custom_model($str, $field)
	{
		list($model, $func) = explode('.', $field);
		$this->CI->load->model($model);
		return !method_exists($this->CI->{$model}, $func) || call_user_func(array($this->CI->{$model}, $func), $str) ;
	}
	
	public function alpha_dash_first($str)
	{
		return ( strlen($str) < 1 || ! preg_match("/^([a-z_])$/i", $str[0])) ? FALSE : TRUE;
	}
	
	public function matches_gender($str, $field)
	{
		$default = array(
			'male' => 1,
			'female' => 2,
			'M' => 1,
			'F' => 2,
			'm' => 1,
			'f' => 2,
			'boy' => 1,
			'girl' => 2,
			'男' => 1,
			'女' => 2,
			'男性' => 1,
			'女性' => 2,
			1 => 1,
			0 => 2,
			2 => 2
		);
		if ( ! isset($_POST[$field]))
		{
			return FALSE;
		}
		$field = $default[$_POST[$field]];
		return ! (strlen($str) != 10 || $str[1] != $field );
	}
	
	public function valid_list($str)
	{
		return isset($this->current_list[$str]);
	}
	
	public function valid_tel($tel)
	{
		return (bool) preg_match('/^0[0-9]{1,2}[-]?[0-9-]{6,9}$/s', $tel);
	}
	
	public function valid_mobile($mobile)
	{
		return (bool) preg_match('/^09[0-9]{2}[-]?[0-9]{3}[-]?[0-9]{3}$/s', $mobile);
	}
	
	public function valid_phone($phone)
	{
		return (bool) ( $this->valid_tel($phone) || $this->valid_mobile($phone) );
	}
	
	public function valid_date($date)
	{
		if( is_php('5.1.0') ){
			return strtotime($date) !== FALSE;
		}else{
			return strtotime($date) !== -1;
		}
	}
	
	public function valid_uid($uid)
	{
		if(! preg_match('/^[A-Za-z][12][0-9]{8}$/s', $uid)) return FALSE;
		$uid = strtoupper($uid);
		$ID_First_val = array(1,10,19,28,37,46,55,64,39,73,82,2,11,20,48,29,38,47,56,65,74,83,21,3,12,30);
		$ID_total_value = $ID_First_val[ord($uid[0]) - 65] + (int)$uid[9];
		for( $i = 1 ; $i < 9 ; $i++ ) $ID_total_value += (int)$uid[$i] * ( 9 - $i );
		return ! (bool)( $ID_total_value % 10 );
	}
	
}

