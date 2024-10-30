<?php

class kickpress_radio extends kickpress_form_elements {

	/**
	 * This method over-rides the parent class
	 */
	public function __construct( $args = array() ) {
		$defaults = array(
			'before_input'   => '<td colspan="2">'
		);
		$args = wp_parse_args( $args, $defaults );
		parent::__construct( $args );
	}

	/**
	 * This method over-rides the parent class
	 */
	public function element( $params ) {
		$html = $this->_before_element
		      . $this->_before_input
		      . $this->input( $params )
		      . $this->notes( $params )
		      . $this->_after_input
		      . $this->_after_element;
		
		return $html;
	}

	/**
	 * This method over-rides the parent class
	 */
	public function input( $params ) {
		$html = '<fieldset><legend>' . $params['caption'] . '</legend>';
		
		foreach ( (array) $params['options'] as $value => $label ) {
			$id   = $params['id'] . '-' . $value;
			$name = $params['name'];
			
			$html .= self::_html_input( 'radio', $name, $value, array(
				'id'      => $id,
				'checked' => $params['value'] == $value
			) );
			
			$html .= self::_html_label( $label, array(
				'for' => $id
			) );
			
			$html .= '<br>';
		}
		
		$html .= '</fieldset>';
		
		return $html;
	}
}

?>