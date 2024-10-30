<?php

class kickpress_date extends kickpress_form_elements {
	public function __construct( $args ) {
		parent::__construct( $args );
		
		wp_enqueue_style( 'jquery-ui-datepicker', plugins_url( 'kickpress/includes/css/ui.datepicker.css' ) );
		wp_enqueue_script( 'jquery-ui-datepicker' );
	}
	
	public function input( $params ) {
		$date_params = array();
		$date_params[] = 'dateFormat: "yy-mm-dd"';
		
		if ( in_array( $params['caption'], array( 'Birthdate', 'Birthday' ) ) )
			$date_params[] = 'yearRange: "-100:+0"';
		
		$date_params = '{' . implode( ',', $date_params ) . '}';
		
		$script = sprintf( 'jQuery(document).ready(function($){
				$("input#%1$s").datepicker(%2$s);
			});', $params['id'], $date_params );
		
		extract( $params, EXTR_SKIP );
		
		$html = self::_html_input( 'text', $name, $value, array(
			'id'    => $id,
			'class' => 'datepicker ' . $class
		) );
		
		$html .= self::_html_tag( 'script', $script, array(
			'type' => 'text/javascript'
		) );
		
		$html .= '<div>Format: (YYYY/MM/DD)</div>';
		
		return $html;
	}
}

?>