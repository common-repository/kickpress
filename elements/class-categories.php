<?php

class kickpress_categories extends kickpress_form_elements {
	public function element($params) {
		global $wpdb;
		$input_options = '';

		$sql = "
			SELECT 
				{$wpdb->prefix}terms.*,
				{$wpdb->prefix}term_relationships.object_id
			FROM
				{$wpdb->prefix}terms
			INNER JOIN {$wpdb->prefix}term_taxonomy
				ON {$wpdb->prefix}term_taxonomy.taxonomy = 'category'
				AND {$wpdb->prefix}term_taxonomy.term_id = {$wpdb->prefix}terms.term_id
			LEFT JOIN {$wpdb->prefix}term_relationships
				ON {$wpdb->prefix}term_relationships.term_taxonomy_id = {$wpdb->prefix}term_taxonomy.term_taxonomy_id
				AND {$wpdb->prefix}term_relationships.object_id = '".$params['post_id']."'
			ORDER BY
				{$wpdb->prefix}terms.name";

		if ( $results = $wpdb->get_results($sql) ) {
			$input_options .= '<div class="term-list">';
			foreach ( $results as $term ) {
				$input_options .= sprintf('
					<label for="%1$s_%3$s"><input type="checkbox" id="%1$s_%3$s" name="%2$s[%3$s]" value="%5$s"%6$s /> %4$s</label><br />',
					$params['id'],
					$params['name'],
					$term->slug,
					$term->name,
					$term->term_id,
					( $params['post_id'] != 0 && (string) $term->object_id == $params['post_id'] ? ' checked="checked"' : '' )
				);
			}
			$input_options .= '</div>';
		}

		$add_new_categories = sprintf('
			<label for="%1$s_new">%3$s</label> <input type="text" id="%1$s_new" name="%2$s[new]" value="" />
			<p class="help-block">%4$s</p>',
			$params['id'],
			$params['name'],
			__( 'Add Categories:', 'kickpress' ),
			__( 'Comma separated list of new categories.', 'kickpress' )
		);

		$html = sprintf('
			<tr valign="top" class="form-group">
				<td colspan="2">
					<fieldset>
						<legend class="screen-reader-text"><span>%1$s</span></legend>
						%2$s
						%3$s
					</fieldset>
					%4$s
				</td>
			</tr>',
			$params['caption'],
			$input_options,
			$add_new_categories,
			(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
		);
		
		return $html;
	}
}

?>