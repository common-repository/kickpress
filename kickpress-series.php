<?php

function kickpress_series_get_posts( $post, $args = array() ) {
	return __kickpress_series_call( 'get_posts', $post, $args );
}

function kickpress_series_get_tasks( $post, $args = array() ) {
	return __kickpress_series_call( 'get_tasks', $post, $args );
}

function kickpress_series_get_next_post( $post, $series_post = null ) {
	return __kickpress_series_call( 'get_next_post', $post, $series_post );
}

function kickpress_series_get_user_posts( $post, $args = array() ) {
	return __kickpress_series_call( 'get_user_posts', $post, $args );
}

function kickpress_series_get_user_tasks( $post, $args = array() ) {
	return __kickpress_series_call( 'get_user_tasks', $post, $args );
}

function kickpress_series_get_next_user_post( $post ) {
	return __kickpress_series_call( 'get_next_user_post', $post );
}

function kickpress_series_get_user_progress( $post, $type = 'any' ) {
	return __kickpress_series_call( 'get_user_progress', $post, $type );
}

function __kickpress_series_call( $method, $post ) {
	$post = is_object( $post ) ? $post : get_post( $post );

	$args = func_get_args();

	array_shift( $args );

	if ( $kickpress_api = kickpress_init_api( $post->post_type ) ) {
		if ( 'series' == $kickpress_api->params['meta_type'] ) {
			if ( is_callable( array( $kickpress_api, $method ) ) ) {
				return call_user_func_array( array( $kickpress_api, $method ), $args );
			}
		}
	}

	return false;
}