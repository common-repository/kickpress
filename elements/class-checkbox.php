<?php

class kickpress_checkbox extends kickpress_form_elements {
	public function __construct( $args = array() ) {
		$defaults = array(
			'before_element' => '<tr valign="top" class="checkbox">',
			'before_input'   => '<td colspan="2">'
		);
		$args = wp_parse_args( $args, $defaults );
		parent::__construct( $args );
	}

	public function element( $params ) {
		if ( empty( $params['label'] ) ) {
			$html = $this->_before_element
			      . $this->_before_input
			      . $this->input( $params )
			      . $this->label( $params )
			      . $this->notes( $params )
			      . $this->_after_input
			      . $this->_after_element;
		} else {
			$label_params = array_merge( $params, array( 'caption' => $params['label'] ) );
			$label = $this->label( $label_params );
			$html = $this->_before_element
			      . $this->_before_label
			      . $label
			      . $this->_after_label
			      . $this->_before_input
			      . $this->input( $params )
			      . $this->label( $params )
			      . $this->notes( $params )
			      . $this->_after_input
			      . $this->_after_element;
		}

		return $html;
	}

	public function input( $params ) {
		extract( $params, EXTR_SKIP );

		$value = ( ! empty( $value ) ? $value : $default );

		$attr = $this->get_attributes( $params );
		$attr['checked'] = kickpress_boolean( $value );

		return self::_html_input( 'checkbox', $name, 'enable', $attr );
	}

	public function validate_input( $data ) {
		return kickpress_boolean( $data ) ? 'enable' : 'disable';
	}
}

?>