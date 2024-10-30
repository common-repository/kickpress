<?php

class kickpress_password extends kickpress_form_elements {
	/* public function element($params) {
		$html = sprintf('
			<tr valign="top">
				<th scope="row"><label for="%1$s"> %4$s</label></th>
				<td>
					<input type="password" id="%1$s" name="%2$s" value="%3$s"%5$s%6$s />
					%7$s
				</td>
			</tr>',
			$params['id'],
			$params['name'],
			$params['value'],
			$params['caption'],
			(isset($params['class'])?' class="'.$params['class'].'"':''),
			$params['properties'],
			(isset($params['notes'])?'<span class="description">'.$params['notes'].'</span>':'')
		);

		return $html;
	} */
	
	public function input( $params ) {
		extract( $params, EXTR_SKIP );
		
		// TODO consider making $params['properties'] an array and merging
		return self::_html_input( 'password', $name, $value, array(
			'id'    => $id,
			'class' => $class
		) );
	}
}

?>