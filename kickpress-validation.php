<?php

class validation {
	public $html;
	public $errors = false;
	public $currentName;
	public $currentCaption;
	public $post_type;
	public $notes = array();
	public $locator;
	private $params;

	public function __construct($params, $locator) {
		$this->params = $params;
		$this->locator = $locator;
		$this->errors = false;
	}

	public function validate($field_name, $field, $field_value) {
		// if ( is_null($value) )
		//	return NULL;
		$field_length = strlen($field_value);
		$field_value = urldecode($field_value);
		$field_type = isset($field['type'])?$field['type']:'text';

		if ( isset($field['escapeHTML']) && $field['escapeHTML'] )
			$value = htmlspecialchars($field_value);

		//if ( ! get_magic_quotes_gpc() )
		//$value = addslashes($value);

		$this->currentName = $field_name;

		// Make sure a caption exists
		if ( isset($field['caption']) )
			$this->currentCaption = (string) $field['caption'];
		else
			$this->currentCaption = $field_name;

		// Validate that fields are not null if they are required
		if ( isset($field['required']) && $field['required'] == true ) {
			if ( empty($field_value) )
		 		$this->build_error_message('Required Field');
		}

		if ( method_exists($this, $field_type) )
			$this->$field_type($field_value);

		if ( isset($field['validate']) ) {
			$field_validate = $field['validate'];
			if ( method_exists($this, $field_validate) )
				$this->$field_validate($field_value);
		}

		if ( isset($field['length']) && ($field_length > $field['length']) )
			$this->build_error_message('Input exceeds maximum allowed characters, maximum input length is '.$field['length'].'.');

		if ( isset($field['min_length']) && ($field_length < $field['min_length']) )
			$this->build_error_message('Input does not contain enough characters, minimum input length is '.$field['min_length'].'.');

		if ( isset($field['confirm']) && 'true' == (string) $field['confirm'] ) {
			if ( isset($this->params[$field_name.'_confirm']) && (string) $this->params[$field_name.'_confirm'] != $field_value )
				$this->build_error_message('Values do not match');
		}
	}

	public function build_error_message($message, $name=null, $caption=null) {
		$this->errors = true;

		if ( ! isset($this->notes['error']) )
			$this->notes['error'] = array();

		$this->notes['error'][] = sprintf('
			<label for="%4$s_%5$s%1$s" class="error-caption" title="Click here to jump to error on form">&quot;<strong>%2$s</strong>&quot;: %3$s</label>',
			isset($name)?$name:$this->currentName,
			isset($caption)?$caption:$this->currentCaption,
			$message,
			$this->params['post_type'],
			$this->locator
		);
	}

	private function numeric($value) {
		if ( preg_match ("/[^0-9]/", $value) )
			$this->build_error_message('Invalid Characters!');
	}

	private function alpha($value) {
		if ( preg_match ("/[^A-z]/", $value) )
			$this->build_error_message('Invalid Characters!');
	}

	private function alpha_numeric($value) {
		if ( preg_match ("/[^a-zA-Z0-9\.\-\??\?? ]+$/s", $value) )
			$this->build_error_message('Invalid Characters!');
	}

	private function phone($value) {
		if ( preg_match("/[^0-9\ ]+$/",$value) ) {
			$this->build_error_message('Invalid Characters!');
	 		return false;
		} else {
			return true;
		}
	}

	private function mailto($value) {
		//if ( preg_match("/^[\w-\.\']{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]{2,}$/", $value) )
		if ( ! preg_match ("/^[A-z0-9\._-]+@[A-z0-9][A-z0-9-]*(\.[A-z0-9_-]+)*\.([A-z]{2,6})$/", $value) )
			$this->build_error_message('Invalid Email!');
	}

	private function zip($value) {
		if ( preg_match("/[^0-9]+$/ ",$value) ) {
			$this->build_error_message('Invalid Characters!');
	 		return false;
		} else {
			return true;
		}
	}

	private function password($value) {
		return true;
	}

	private function http($value) {
		if ( empty($value) ) {
	 		$this->build_error_message('Required Field');
			return false;
		}

		return true;

		//check, if a valid url is provided
		if ( ! filter_var( $value, FILTER_VALIDATE_URL ) ) {
			$this->build_error_message('Invalid web address!');
			return false;
		}

		//initialize curl
		$curlInit = curl_init($value);
		curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
		curl_setopt($curlInit,CURLOPT_HEADER,true);
		curl_setopt($curlInit,CURLOPT_NOBODY,true);
		curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);
		
		//get answer
		$response = curl_exec($curlInit);
		
		curl_close($curlInit);
		
		if ( $response ) {
			return true;
		} else {
			$this->build_error_message('Invalid web address!');
			return false;
		}

/*
		//url
		//return preg_match ("/http:\/\/(.*)\.(.*)/i", $url);
		if ( ! preg_match ("/http:\/\/(.*)\.(.*)/i", $value) ) {
			$this->build_error_message('Invalid web address!');
		} else {
			$parts = parse_url('http://'.$value);
			$fp = fsockopen($parts['host'], 80, $errno, $errstr, 10);
			if ( ! $fp ) {
				$this->build_error_message('Invalid web address!');
			}
			fclose($fp);
		}
		return true;
*/
	}

	private function noscript($value) {
		return true;
	}

	private function check_mail_code($code, $country) {
		$code = preg_replace("/[\s|-]/", "", $code);
		$length = strlen ($code);
		
		switch (strtoupper ($country)) {
			case 'US':
			case 'MX':
				if ( ($length <> 5) && ($length <> 9) ) {
					return false;
				}
				return isDigits($code);
			case 'CA':
			if ( $length <> 6 ) {
				return false;
			}
			return preg_match ("/([A-z][0-9]){3}/", $code);
		}
	}

	private function check_password($password) {
		$length = strlen($password);
		if ( $length < 8 ) {
			return false;
		}
		$unique = strlen (count_chars ($password, 3));
		$difference = $unique / $length;
		echo $difference;
		if ( $difference < .60 ) {
			return false;
		}
		return preg_match ("/[A-z]+[0-9]+[A-z]+/", $password);
	}
	
	public function validate_field($field_value, $escape_html = false, $field_type='') {
		$field_value = urldecode($field_value);
	
		if ( is_null($field_value) )
			return null;

		if ( $escape_html )
			$field_value = htmlspecialchars($field_value);

		// if ( ! get_magic_quotes_gpc() )
			$field_value = addslashes($field_value);

		return $field_value;
	}
}

?>