<?php

require_once dirname( __FILE__ ) . '/class-list.php';

class kickpress_boolean extends kickpress_list {
	public function input( $params ) {
		$params['properties']['style'] .= 'width: 50px;';
		$params['options'] = array(
			'1' => 'Yes',
			'0' => 'No'
		);
		
		unset( $params['default'] );
		
		return parent::input( $params );
	}
}

?>