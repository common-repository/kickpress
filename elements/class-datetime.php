<?php

require_once dirname( __FILE__ ) . '/class-time.php';

class kickpress_datetime extends kickpress_form_elements {
	public function __construct( $args = array() ) {
		parent::__construct( $args );
		
		wp_enqueue_style( 'jquery-ui-datepicker', plugins_url( 'kickpress/includes/css/ui.datepicker.css' ) );
		wp_enqueue_script( 'jquery-ui-datepicker' );
	}
	
	public function element($params) {
		// kickpress_auto_version('/css/ui.datepicker.css'
		
		$date_params = array();
		
		if ( in_array( $params['caption'], array( 'Birthdate', 'Birthday' ) ) )
			$date_params['yearRange'] = '-99:+00';
		
		$time = strtotime( $params['value'] );
		
		$hour_name   = 'hour_'   . $params['name'];
		$minute_name = 'minute_' . $params['name'];
		
		if ( 0 == $time ) {
			$date = '';
			
			$hour_value   = '00';
			$minute_value = '00';
		} else {
			$date = date( 'm/d/Y', $time );
			
			$hour_value   = date( 'H', $time );
			$minute_value = date( 'i', $time );
		}
		
		$attr = $params['properties'];
		$attr['style'] = $this->style;
		
		$hour   = self::_html_select( $hour_name,   $hour_value,   kickpress_time::$hours,   $attr );
		$minute = self::_html_select( $minute_name, $minute_value, kickpress_time::$minutes, $attr );

		$html = sprintf('
			<tr valign="top" class="form-group">
				<th scope="row"><label for="%1$s"> %4$s</label></th>
				<td>
					<input type="text" id="%1$s" name="%2$s" value="%3$s" class="datepicker%5$s"%6$s />
					%9$s
					%10$s
					<br class="grid-break" />
					<div>Format: (MM/DD/YYYY)</div>
					%7$s
					<script type="text/javascript">
						jQuery(document).ready(function($) {
							$("input#%1$s").datepicker(%8$s);
						});
					</script>
				</td>
			</tr>',
			$params['id'],
			$params['name'],
			$date,
			$params['caption'],
			isset( $params['class'] ) ? ' ' . $params['class'] : '',
			$params['properties'],
			isset( $params['notes'] ) ? '<p class="help-block">' . $params['notes'] . '</p>' : '',
			empty( $date_params ) ? '' : json_encode( $date_params ),
			$hour,
			$minute
		);
		return $html;
	}
	
	/* public function input( $params ) {
		$date_params = array();
		
		if ( in_array( $params['caption'], array( 'Birthdate', 'Birthday' ) ) )
			$date_params['yearRange'] = '-99:+00';
				
		'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="datepicker%5$s"%6$s />
		%9$s
		%10$s
		<br class="grid-break" />
		<div>Format: (MM/DD/YYYY)</div>'
		
		$time = new kickpress_time()->input( $params );
	} */
}

?>