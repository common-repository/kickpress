<?php

class kickpress_term_search_options extends kickpress_form_elements {
	public function element($params) {
		global $wpdb;
		$post_id = $params['post_id'];
		$post_type = $params['post_name'];

		if ( $api = kickpress_init_api($post_type) ) {
/*
			[term_id] => 58
			[name] => Hobbies
			[slug] => hobbies
			[term_group] => 0
			[term_taxonomy_id] => 65
			[taxonomy] => custom-post-type-categories
			[description] =>
			[parent] => 0
			[count] => 1
*/
			foreach ( $api->params['terms'] as $key=>$term ) {
				$term_filter_id = '_term_filter_'.str_replace('-', '_', $term->slug);
				$term_filter_name = $term_filter_id; //'_term_filter['.$term->term_id.']';

				if ( ! $term_filter_value = get_post_meta($post_id, $term_filter_id, true) )
					$term_filter_value = '';
/*
				if ( ! $current_caption = get_post_meta($post_id, 'term_'.$post->term_id, true) )
					$current_caption = '';

				$input_options .= sprintf('
					<label for="term_%1$s">Filter by type: <input type="checkbox" id="term_%1$s" name="term[%1$s]" value="%2$s"%3$s /></label><br />',
					$post->term_id,
					($category_selection ? '1' : '0'),
					($category_selection ? ' checked="checked"' : '')
				);
*/
				$select_params = array(
					'id'=>$term_filter_id,
					'name'=>$term_filter_name,
					'default'=>'',
					'class'=>'category',
					'value'=>$term_filter_value,
					'options'=>array( 'false'=>'None', 'default'=>'Category Dropdowns', 'links'=>'Links', 'tabs'=>'Tabs', 'checkboxes'=> 'Checkboxes' )
				);

				$term_selection = $this->option_list($select_params);
				$select_html .= sprintf('
					<label for="%1$s">%2$s: %3$s</label><br />',
					$term_filter_id,
					$term->name,
					$term_selection
				);
			}
		}

		$html = sprintf('
			<tr valign="top" class="form-group">
				<th scope="row"> %1$s</th>
				<td>
					%2$s
					%3$s
				</td>
			</tr>',
			$params['caption'],
			$select_html,
			(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
		);
		
		return $html;
	}
}

?>