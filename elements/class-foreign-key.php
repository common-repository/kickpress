<?php

class kickpress_foreign_key extends kickpress_form_elements {
	public function element($params) {
		if ( ! isset($params['foreign_post_type']) )
			return false;

		global $wpdb;
		$params['options'] = array();

		$sql = "
			SELECT
				$wpdb->posts.ID,
				$wpdb->posts.post_title
			FROM
				$wpdb->posts
			WHERE
				$wpdb->posts.post_type = '".$params['foreign_post_type']."'
				AND $wpdb->posts.post_status = 'publish'
			ORDER BY
				$wpdb->posts.post_title";

		$params['options'][''] = 'None';
		if ( $results = $wpdb->get_results($sql) ) {
			foreach ( $results as $post ) {
				$params['options'][$post->ID] = $post->post_title;
			}

			$select_html = $this->option_list($params);

			$html = sprintf('
				<tr valign="top" class="form-group">
					<th scope="row"><label for="%1$s"> %2$s</label></th>
					<td>
						%3$s
						%4$s
					</td>
				</tr>',
				$params['id'],
				$params['caption'],
				$select_html,
				(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
			);
			return $html;
		}
		return false;
/*
			$html = sprintf('
				<table>
					<tr>
						<td><div id="%9$s_%8$s_%2$s_wrapper">%1$s</div></td>
						<td>
						</td>
					</tr>
				</table>',
				$oSelect->html, 1
				$params['name'], 2
				$params['value'], 3
				$params['site'], 4
				$params['parent_term'], 5
				$params['parentidfield'], 6
				$params['parentcaptionfield'], 7
				$params['post_id'], 8
				$params['connect_table'] 9
			);
*/

	}
}

?>