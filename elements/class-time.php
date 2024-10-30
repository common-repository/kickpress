<?php

class kickpress_time extends kickpress_form_elements {
	public static $hours = array(
		'00' => '12 AM', '01' => '01 AM', '02' => '02 AM',
		'03' => '03 AM', '04' => '04 AM', '05' => '05 AM',
		'06' => '06 AM', '07' => '07 AM', '08' => '08 AM',
		'09' => '09 AM', '10' => '10 AM', '11' => '11 AM',
		'12' => '12 PM', '13' => '01 PM', '14' => '02 PM',
		'15' => '03 PM', '16' => '04 PM', '17' => '05 PM',
		'18' => '06 PM', '19' => '07 PM', '20' => '08 PM',
		'21' => '09 PM', '22' => '10 PM', '23' => '11 PM'
	);
	
	public static $minutes = array( '00' => '00',
		'15' => '15', '30' => '30', '45' => '45' );
	
	/* public function element( $params ) {
		if ( ! isset( $params['value'] ) && ! strstr( $params['value'], ':') )
			$params['value'] = isset( $_POST['selected_time'] )
			                 ? $_POST['selected_time'] : "08:00:00";

		$time = explode( ':', $params['value'] );
		
		$hour_name   = 'hour_'   . $params['name'];
		$minute_name = 'minute_' . $params['name'];
		
		$time = strtotime( $params['value'] );
		
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
		
		$hour   = self::_html_select( $hour_name,   $time[0], self::$hours,   $attr );
		$minute = self::_html_select( $minute_name, $time[1], self::$minutes, $attr );
		
		$html = sprintf('
			<tr valign="top">
				<th scope="row"><label for="%1$s"> %3$s</label></th>
				<td>
					%4$s
					%5$s
					%6$s
				</td>
			</tr>',
			$params['id'],
			$params['name'],
			$params['caption'],
			$hour,
			$minute,
			(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
		);

		return $html;
	} */
	
	public function input( $params ) {
		$hour_name   = 'hour_'   . $params['name'];
		$minute_name = 'minute_' . $params['name'];
		
		$time = strtotime( $params['value'] );
		
		if ( 0 == $time ) {
			$hour_value   = '00';
			$minute_value = '00';
		} else {
			$hour_value   = date( 'H', $time );
			$minute_value = date( 'i', $time );
		}
		
		$attr = $params['properties'];
		$attr['style'] = $this->style;
		
		$hour   = self::_html_select( $hour_name,
			$hour_value, self::$hours, $attr );
		
		$minute = self::_html_select( $minute_name,
			$minute_value, self::$minutes, $attr );
		
		return $hour . $minute;
	}
}

?>