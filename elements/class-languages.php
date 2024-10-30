<?php

require_once dirname( __FILE__ ) . '/class-xml-list.php';

class kickpress_languages extends kickpress_xml_list {
	public function __construct( $args ) {
		parent::__construct( $args );
		
		$this->_list_file = 'languages';
		$this->_all_items = 'All Languages';
	}
}

?>