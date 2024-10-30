<?php

class kickpress_textarea extends kickpress_form_elements {
	/**
	 * Over-ridden to make default input area two columns wide
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array_merge( array(
			'before_label' => '<th scope="row" colspan="2">',
			'after_label'  => '</th></tr>',
			'before_input' => '<tr valign="top" class="form-group"><td colspan="2">'
		), $args ) );
	}
	
	/**
	 * Over-ridden to place notes above input
	 */
	public function element( $params ) {
		$html = $this->_before_element
		      . $this->_before_label
		      . $this->label( $params )
		      . $this->_after_label
		      . $this->_before_input
		      . $this->notes( $params )
		      . $this->input( $params )
		      . $this->_after_input
		      . $this->_after_element;
		
		return $html;
	}
	
	public function input( $params ) {
		
		$attr = array(
			'id'    => $params['id'],
			'name'  => $params['name'],
			'cols'  => 50,
			'rows'  => 10,
			'class' => 'large-text code ' . $params['class']
		);
		
		if ( isset( $params['properties'] ) ) {
			$attr = array_merge( $attr, $params['properties'] );
		}
		
		return self::_html_tag( 'textarea', $params['value'], $attr, true );
	}
	
	/**
	 * Over-ridden to wrap notes in <p> tag
	 */
	public function notes( $params ) {
		return '<p>' . parent::notes( $params ) . '</p>';
	}
}

?>