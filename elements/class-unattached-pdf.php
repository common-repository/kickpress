<?php

class kickpress_unattached_pdf extends kickpress_form_elements {
	public function element($params) {
		global $wpdb;
		$params['options'] = array();

		$sql = "
			SELECT
				$wpdb->posts.ID,
				$wpdb->posts.post_title
			FROM
				$wpdb->posts
			WHERE
				$wpdb->posts.post_type = 'attachment'
				AND $wpdb->posts.post_mime_type = 'application/pdf'
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
	}
}

?>