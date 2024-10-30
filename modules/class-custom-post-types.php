<?php

class kickpress_custom_post_types extends kickpress_api {
	public function get_custom_fields($merge=true) {
		return parent::get_post_type_options($merge);
	}
}

?>