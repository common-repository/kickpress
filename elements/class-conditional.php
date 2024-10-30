<?php

class kickpress_conditional extends kickpress_form_elements {
	/* public function element($params) {
		$html = sprintf('
			<input type="checkbox" id="%1$s" name="%2$s"%5$s class="conditional%3$s"%4$s />',
			$params['id'],
			$params['name'],
			( isset($params['class']) ? ' '.$params['class'] : ''),
			$params['properties'],
			( '1' == $params['value'] ? ' checked="checked"' : '' )
		);
		return $html;
	} */
	
	public function input( $params ) {
		extract( $params, EXTR_SKIP );
		
		return self::_html_input( 'checkbox', $name, 1, array(
			'id'      => $id,
			'class'   => 'conditional ' . $class,
			'checked' => 1 == $value
		) );
	}
}

?>