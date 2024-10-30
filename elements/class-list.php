<?php

class kickpress_list extends kickpress_form_elements {
	public function input( $params ) {
		extract( $params, EXTR_SKIP );
		
		if ( @is_array( $options ) && isset( $default ) && ! isset( $options[$default] ) )
			$options = array_merge( array( $default => $default ), $options );
		
		if ( empty( $value ) && isset( $default ) ) $value = $default;
		
		$attr = isset( $properties ) ? $properties : array();
		
		$attr['id']    = $id;
		$attr['class'] = $class;
		
		return self::_html_select( $name, $value, $options, $attr );
	}
}

?> 