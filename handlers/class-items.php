<?php

class kickpress_items_handler extends kickpress_api_handler {
	public function get_custom_fields() {
		$custom_fields = array(
		);
		
		return $custom_fields;
	}
	
	public function update_meta_fields( $post, $post_data, $form_data ) {
		return $post_data;
	}
}

?>