<?php

class kickpress_user_ip_address extends kickpress_form_elements {
	public function element( $params ) {
		$ip = $_SERVER['REMOTE_ADDR'];

		$html = self::_html_input( 'hidden', $params['name'], $ip, array(
			'id' => $params['id']
		) );
		
		return $html;
	}
}

?>