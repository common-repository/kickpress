<?php

class kickpress_select_or_new extends kickpress_form_elements {
	public function element($params) {
		if ( ! isset($params['post_id']) )
			return false;

		global $wpdb;
		$params['options'] = array();

		if ( $params['parent_term'] == 'media' ) {
			$user = wp_get_current_user();
			
			$sql = "
				SELECT
					$wpdb->posts.ID,
					$wpdb->posts.post_title
				FROM
					$wpdb->posts
				WHERE
					$wpdb->posts.post_type = 'attachment'
					AND $wpdb->posts.post_author = '$user->ID'";
		} else {
			$sql = "
				SELECT
					$wpdb->posts.ID,
					$wpdb->posts.post_title
				FROM
					$wpdb->posts
				INNER JOIN {$wpdb->prefix}terms
					ON {$wpdb->prefix}terms.slug = '".$params['parent_term']."'
				INNER JOIN {$wpdb->prefix}term_taxonomy
					ON {$wpdb->prefix}term_taxonomy.taxonomy = 'category'
					AND {$wpdb->prefix}term_taxonomy.term_id = {$wpdb->prefix}terms.term_id
				INNER JOIN {$wpdb->prefix}term_relationships
					ON {$wpdb->prefix}term_relationships.object_id = $wpdb->posts.ID
					AND {$wpdb->prefix}term_relationships.term_taxonomy_id = {$wpdb->prefix}term_taxonomy.term_taxonomy_id";
		}
		
		if ( $results = $wpdb->get_results($sql) ) {
			foreach ( $results as $post ) {
				$params['options'][$post->ID] = $post->post_title;
			}

			$select_html = $this->option_list($params);
			
			// "add new" form has to be in a modal thickbox because we
			// can't have a form inside of a form for the ajax submit.
			$html = sprintf('
				<tr valign="top" class="form-group">
					<th scope="row"><label for="%1$s"> %2$s</label></th>
					<td>
						<div id="%1$s_wrapper">%3$s</div>
						&nbsp;(OR)&nbsp;
						<a id="%5$s_add_new" class="form-button add thickbox" title="Add New" href="/%5$s/quick-edit/alias/%1$s_wrapper/connect_table/%6$s/parent_term/%5$s/parent_id/%7$s/">Add New</a>
						%4$s
					</td>
				</tr>',
				$params['id'],
				$params['caption'],
				$select_html,
				( isset($params['notes']) ? '<p class="help-block">'.$params['notes'].'</p>' : '' ),
				$params['parent_term'],
				$params['connect_table'],
				$params['post_id']
			);
			return $html;
		} else {
			// nothing for now
		}

		return $html;
	}
}

?>