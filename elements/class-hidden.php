<?php

class kickpress_hidden extends kickpress_form_elements {
	public function element( $params ) {
		return $this->input( $params );
	}
	
	public function input( $params ) {
		extract( $params, EXTR_SKIP );
		
		return self::_html_input( 'hidden', $name, $value, array( 'id' => $id ) );
	}
}

?>