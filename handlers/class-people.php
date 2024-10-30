<?php

class kickpress_people_handler extends kickpress_api_handler {
	public function get_custom_fields() {
		$custom_fields = array(
			'first_name' => array(
				'name'       => '_first_name',
				'caption'    => 'First Name',
				'type'       => 'text',
				'exportable' => true,
				'filterable' => true,
				'searchable' => true,
				'sortable'   => true
			),
			'last_name' => array(
				'name'       => '_last_name',
				'caption'    => 'Last Name',
				'type'       => 'text',
				'exportable' => true,
				'filterable' => true,
				'searchable' => true,
				'sortable'   => true
			)
		);
		
		return $custom_fields;
	}
	
	public function update_meta_fields( $post, $post_data, $form_data ) {
		return $post_data;
	}
}

?>