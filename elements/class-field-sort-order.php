<?php

class kickpress_field_sort_order extends kickpress_form_elements {
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

		$options = array(''=>'Sort Field');
		foreach ( $post_type_attributes as $key=>$value ) {
			//if ( $value['type']!='hidden' ) {
				$options[$key] = (isset($value['caption'])?$value['caption']:kickpress_make_readable($value['name']));
			//}
		}

		if ( strpos($params['value'], ',') )
			$sort_values = split(',', $params['value']);
		elseif ( ! empty($params['value']) )
			$sort_values = array($params['value']);
		else
			$sort_values = array();

		for ( $i=0;$i<3;$i++ ) {
			$options[''] = 'Sort Field #'.($i+1);
			$select_params = array(
				'id'      => $params['id']."_$i",
				'name'    => $params['name']."[$i]",
				'value'   => ( isset($sort_values[$i]) ? $sort_values[$i] : '' ),
				'options' => $options
			);

			$select_html[$i] = $this->option_list($select_params);
		}

		$html = sprintf('
			<tr valign="top" class="form-group">
				<th scope="row"><label for="%1$s"> %3$s</label></th>
				<td>
					%4$s
					%5$s
					%6$s
					%7$s
				</td>
			</tr>',
			$params['id'],
			$params['name'],
			$params['caption'],
			$select_html[0],
			$select_html[1],
			$select_html[2],
			(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
		);
		return $html;
	}
}

?>