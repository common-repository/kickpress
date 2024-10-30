<?php

class kickpress_barcode extends kickpress_form_elements {
	/* public function element( $params ) {
		$base_url = get_bloginfo( 'wpurl' ) . '/wp-content/plugins/kickpress/';

		$html = sprintf('
			<tr valign="top">
				<th scope="row"><label for="%1$s"> %3$s</label></th>
				<td>
					<img id="%1$s"%4$s%5$s src="%7$s/kickpress-barcode.php?text=%8$s" alt="%2$s" />
					%6$s
				</td>
			</tr>',
			$params['id'],
			$params['value'],
			$params['caption'],
			(isset($params['class'])?' class="'.$params['class'].'"':''),
			$params['properties'],
			(isset($params['notes'])?'<span class="description">'.$params['notes'].'</span>':''),
			$base_url,
			urlencode($params['value'])
		);
		return $html;
	} */
	
	public function input( $params ) {
		$url = plugins_url( 'kickpress/kickpress-barcode.php' )
		     . '?text=' . urlencode( $params['value'] );
		
		return self::_html_tag( 'img', null, array(
			'id'    => $params['id'],
			'src'   => $url,
			'alt'   => $params['value'],
			'class' => $params['class'],
		) );
	}
}

?>