<?php

class kickpress_post_type_fields extends kickpress_form_elements {
	public function element($params) {
		global $kickpress_api;
		
		if ( empty( $params['post_name'] ) ) {
			$post_type_attributes = kickpress_api::get_default_fields();
		} else {
			if ( $kickpress_api = kickpress_init_api( $params['post_name'] ) )
				$post_type_attributes = $kickpress_api->get_custom_fields();
		}

		if ( ! count($post_type_attributes) )
			return false;

		$options['false'] = 'None';
		foreach ( $post_type_attributes as $key=>$value ) {
			$options[$key] = (isset($value['caption'])?$value['caption']:kickpress_make_readable($value['name']));
		}

		$select_params = array(
			'id'=>$params['id'],
			'name'=>$params['name'],
			'value'=>$params['value'],
			'options'=>$options
		);

		$select_html = $this->option_list($select_params);

		$html = sprintf('
			<tr valign="top" class="form-group">
				<th scope="row"><label for="%1$s"> %3$s</label></th>
				<td>
					%4$s
					%5$s
				</td>
			</tr>',
			$params['id'],
			$params['name'],
			$params['caption'],
			$select_html,
			(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
		);
		return $html;
	}
}

?>