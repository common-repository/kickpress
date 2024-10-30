<?php

/**
 * This file holds all of the form elements used in custom post types admin interface
 */

class kickpress_form_elements {
	protected $_before_element;
	protected $_after_element;
	protected $_before_label;
	protected $_after_label;
	protected $_before_input;
	protected $_after_input;

	public function __construct( $args = array() ) {
		$defaults = array(
			'before_element' => '<tr valign="top" class="form-group">',
			'after_element'  => '</tr>',
			'before_label'   => '<th scope="row">',
			'after_label'    => '</th>',
			'before_input'   => '<td>',
			'after_input'    => '</td>'
		);

		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$this->_before_element = $before_element;
		$this->_after_element  = $after_element;
		$this->_before_label   = $before_label;
		$this->_after_label    = $after_label;
		$this->_before_input   = $before_input;
		$this->_after_input    = $after_input;
	}

	/**
	 * This method MAY be over-ridden in child classes
	 */
	public function element( $params ) {
		$html = $this->_before_element
		      . $this->_before_label
		      . $this->label( $params )
		      . $this->_after_label
		      . $this->_before_input
		      . $this->input( $params )
		      . $this->notes( $params )
		      . $this->_after_input
		      . $this->_after_element;

		return $html;
	}

	/**
	 * This method MAY be over-ridden in child classes
	 */
	public function label( $params ) {
		return self::_html_label( $params['caption'], array(
			'for' => $params['id']
		) );
	}

	/**
	 * This method SHOULD be over-ridden in child classes
	 */
	public function input( $params ) {
		extract( $params, EXTR_SKIP );

		if ( ! isset( $value ) ) $value = null;

		$attr = $this->get_attributes( $params );

		return self::_html_input( 'text', $name, $value, $attr );
	}

	/**
	 * This method MAY be over-ridden in child classes
	 */
	public function notes( $params ) {
		if ( isset( $params['notes'] ) ) {
			return self::_html_tag( 'p', $params['notes'], array(
				'class' => 'help-block'
			) );
		}

		return null;
	}

	protected function get_attributes( $params ) {
		extract( $params, EXTR_SKIP );

		$attr = isset( $properties ) ? $properties : array();
		$attr['id']    = $id;
		$attr['class'] = $class;

		return $attr;
	}

	/**
	 * This method SHOULD be over-ridden in child classes
	 */
	public function validate_input( $value ) {
		return $value;
	}

	public function simple_select( $name, $value, $options = array(), $properties, $style = '', $first = '' ) {
		if ( ! empty( $first ) ) {
			$options = array( '' => $first ) + $option;
			/* $options[] = self::_html_tag( 'option', $first, array(
				'value' => null
			) ); */
		}

		/* foreach ( (array) $options as $set_value => $option ) {
			$options[] = self::_html_tag( 'option', $option, array(
				'value'    => $set_value,
				'selected' => $set_value == $value
			) );
		}

		$options = implode( PHP_EOL, (array) $options ); */

		if ( ! is_array( $properties ) ) $properties = array();

		$properties['id']    = $name;
		$properties['style'] = $style;

		return self::_html_select( 'select', $value, $options, $properties );
	}

	public function option_list( $params ) {
		extract( $params, EXTR_SKIP );

		if ( @is_array( $options ) && isset( $default ) && ! isset( $options[$default] ) )
			$options = array_merge( array( $default => $default, $options ) );

		if ( empty( $value ) && isset( $default ) )
			$value = $default;
		elseif ( ! isset( $value ) )
			$value = null;

		$attr = isset( $properties ) ? $properties : array();

		$attr['id']    = $id;
		$attr['class'] = $class;

		return self::_html_select( $name, $value, $options, $attr );
	}

	/**
	 *
	 */
	public static function filter_alpha( $value ) {
		return preg_replace( '/[^a-z]/i', '', $value );
	}

	/**
	 *
	 */
	public static function filter_alnum( $value ) {
		return preg_replace( '/[^0-9a-z]/i', '', $value );
	}

	/**
	 *
	 */
	public static function filter_digit( $value ) {
		return preg_replace( '/[^0-9]/', '', $value );
	}

	/**
	 *
	 */
	public static function filter_xdigit( $value ) {
		return preg_replace( '/[^0-9a-f]/i', '', $value );
	}

	/**
	 * Helper method for generating complex HTML elements
	 *
	 * @param string $name the HTML tag name, without angle brackets
	 * @param string $body the text or HTML fragment contained within the tag
	 * @param array $attr associative array of attribute for the tag
	 * @param bool $auto_close always append closing tag if true
	 */
	protected static function _html_tag( $name, $body = null, $attr = array(), $auto_close = false ) {
		$attr_str = '';
		foreach ( (array) $attr as $attr_name => $attr_value ) {
			// if ( empty( $attr_value ) ) continue;

			if ( true === $attr_value ) $attr_value = $attr_name;
			elseif ( false === $attr_value ) continue;

			$attr_str = sprintf( '%s %s="%s"', $attr_str,
				$attr_name, esc_attr( $attr_value ) );
		}

		$html = sprintf( '<%s%s>', $name, $attr_str );

		if ( ! empty( $body ) || $auto_close )
			$html .= sprintf( '%s</%s>', $body, $name );

		return $html;
	}

	/**
	 * Convenience method for generating labels
	 */
	protected static function _html_label( $text, $attr = array() ) {
		if ( isset( $attr['caption_args'] ) )
			$text = vsprintf( $text, $attr['caption_args'] );
		return self::_html_tag( 'label', $text, $attr, true );
	}

	/**
	 * Convenience method for generating inputs
	 */
	protected static function _html_input( $type, $name, $value = null, $attr = array() ) {
		$attr['type']  = $type;
		$attr['name']  = $name;
		$attr['value'] = $value;

		return self::_html_tag( 'input', null, $attr );
	}

	/**
	 * Convenience method for generating select boxes
	 */
	protected static function _html_select( $name, $value = null, $opts = array(), $attr = array() ) {
		foreach ( (array) $opts as $opt_value => $opt_label ) {
			$opt_tags[] = self::_html_tag( 'option', $opt_label, array(
				'value'    => $opt_value,
				'selected' => $opt_value == $value
			) );
		}

		$body = implode( PHP_EOL, (array) $opt_tags );

		$attr['name'] = $name;

		return self::_html_tag( 'select', $body, $attr );
	}
}

?>