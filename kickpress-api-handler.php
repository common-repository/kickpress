<?php

abstract class kickpress_api_handler {
	protected $_api;

	public function __construct( $api ) {
		$this->_api = $api;
	}

	/**
	 * This method MAY be over-ridden in child classes
	 */
	public function get_post_type_options() {
		return array();
	}

	public abstract function get_custom_fields();

	public abstract function update_meta_fields( $post, $post_data, $form_data );

	public function add_filter( $action, $method = null, $priority = 10, $num_args = 1 ) {
		if ( is_null( $method ) )
			$method = $action;

		if ( method_exists( $this, $method ) ) {
			return add_filter( $action, array( $this, $method ), $priority, $num_args );
		}

		return false;
	}

	public function add_action( $action, $method = null, $priority = 10, $num_args = 1 ) {
		return $this->add_filter( $action, $method, $priority, $num_args );
	}

	public function remove_filter( $action, $method = null, $priority = 10 ) {
		if ( is_null( $method ) )
			$method = $action;

		if ( method_exists( $this, $method ) ) {
			return remove_filter( $action, array( $this, $method ), $priority );
		}

		return false;
	}

	public function remove_action( $action, $method = null, $priority = 10 ) {
		return $this->remove_filter( $action, $method, $priority );
	}

	public function do_curl( $request ) {
		$curl = curl_init( $request );

		curl_setopt( $curl, CURLOPT_HEADER, false );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, $timeout );

		$response = curl_exec( $curl );

		curl_close( $curl );

		return $response;
	}

	public function join_filter( $join ) {
		return $join;
	}

	public function where_filter( $where ) {
		return $where;
	}

	public function form_footer() {
		return null;
	}
}
