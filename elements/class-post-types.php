<?php

class kickpress_post_types extends kickpress_form_elements {
	public function element($params) {
// TODO: if admin, else return;
		global $kickpress_post_types;
		$exclude = array('page','nav_menu_item','attachment');
		$options = array('any'=>'All Post Types');

		foreach ( $kickpress_post_types as $key=>$post_type ) {
			if ( in_array($key, $exclude) )
				continue;

			if ( isset($post_type['post_type_title']) )
				$title = $post_type['post_type_title'];
			else
				$title = kickpress_make_readable($post_type['post_type']);

			$options[$post_type['post_type']] = $title;
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