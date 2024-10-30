<?php

class kickpress_field_sort_direction extends kickpress_form_elements {
	public function element($params) {
		$options = array(
			''     => __( 'Sort Field', 'kickpress' ),
			'ASC'  => __( 'Ascending Order (A-Z)', 'kickpress' ),
			'DESC' => __( 'Descending Order (Z-A)', 'kickpress' )
		);
		
		if ( is_array( $params['value'] ) )
			$sort_values = $params['value'];
		elseif ( strpos( $params['value'], ',' ) )
			$sort_values = split( ',', $params['value'] );
		elseif ( ! empty( $params['value'] ) )
			$sort_values = array( $params['value'] );
		else
			$sort_values = array();

		for ( $i = 0; $i < 3; $i++ ) {
			$options[''] = 'Sort Field #' . ( $i + 1 );
			
			$select_params = array(
				'id'      => $params['id'] . "_$i",
				'name'    => $params['name'] . "[$i]",
				'value'   => isset( $sort_values[$i] ) ? $sort_values[$i] : '',
				'options' => $options
			);

			$select_html[$i] = $this->option_list( $select_params );
		}

		$notes = isset( $params['notes'] )
		       ? '<p class="help-block">' . $params['notes'] . '</p>'
		       : '';

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
			$notes
		);
		
		return $html;
	}
}

?>