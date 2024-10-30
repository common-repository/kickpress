<?php

class kickpress_tags extends kickpress_form_elements {
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
				ON {$wpdb->prefix}term_taxonomy.taxonomy = 'post_tag'
				AND {$wpdb->prefix}term_taxonomy.term_id = {$wpdb->prefix}terms.term_id
			INNER JOIN {$wpdb->prefix}term_relationships
				ON {$wpdb->prefix}term_relationships.term_taxonomy_id = {$wpdb->prefix}term_taxonomy.term_taxonomy_id
				AND {$wpdb->prefix}term_relationships.object_id = '".$params['post_id']."'
			ORDER BY
				{$wpdb->prefix}terms.name";

		if ( $results = $wpdb->get_results($sql) ) {
			$input_options .= '<div class="term-list">';
			foreach ( $results as $term ) {
				$input_options .= sprintf('
					<label for="%1$s_%3$s"><input type="checkbox" id="%1$s_%3$s" name="%2$s[%3$s]" value="%4$s"%5$s /> %4$s</label><br />',
					$params['id'],
					$params['name'],
					$term->slug,
					$term->name,
					( (string) $term->object_id == $params['post_id'] ? ' checked="checked"' : '' )
				);
			}
			$input_options .= '</div>';
		}

		$add_new_tags = sprintf('
			<label for="%1$s_new">%3$s</label> <input type="text" class="form-control" id="%1$s_new" name="%2$s[new]" value="" />
			<p class="help-block">%4$s</p>',
			$params['id'],
			$params['name'],
			__( 'Add Tags', 'kickpress' ),
			__( 'Comma separated list of new tags.', 'kickpress' )
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
			$add_new_tags,
			(isset($params['notes'])?'<p class="help-block">'.$params['notes'].'</p>':'')
		);
		
		return $html;
	}
}

?>