<?php

class kickpress_call extends kickpress_form_elements {
	/* public function element($params) {
		$callOut = "+1" . str_replace( ".", "", str_replace( "-", "", $params['value'] ) );
		
		$html = sprintf('
			<a href="callto://%4$s"%2$s%3$s>%1$s</a>',
			$params['caption'],
			( isset($params['class']) ? ' class="'.$params['class'].'"' : '' ),
			$params['properties'],
			$callOut
		);
		return $html;
	} */
	
	public function input( $params ) {
		extract( $params, EXTR_SKIP );
		
		$chars  = array( '-', '.', ' ' );
		$number = '+1' . str_replace( $chars, '', $value );
		
		return self::_html_tag( 'a', $caption, array(
			'href'  => 'callto://' . $number,
			'class' => $class
		) );
	}
}

?>