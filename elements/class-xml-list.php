<?php

include dirname( __FILE__ ) . '/class-list.php';

class kickpress_xml_list extends kickpress_list {
	protected $_list_file;
	protected $_all_items;
	
	public function input( $params ) {
		$params['options'] = array( 'all' => $this->_all_items );
		
		// options are in an xml list
		$file = sprintf(
			'%s/lists/%s.xml',
			dirname( __FILE__ ),
			$this->_list_file
		);
		
		if ( file_exists( $file ) ) {
			$xml = simplexml_load_file( $file );
			
			foreach ( $xml->option as $option ) {
				if ( ! empty( $option ) ) {
					$key   = (string) $option['value'];
					$value = (string) $option;
					
					$params['options'][$key] = $value;
				}
			}
		}
		
		// die( '<pre>' . print_r( $params, true ) . '</pre>' );
		
		return parent::input( $params );
	}
}

?> 