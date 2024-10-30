<?php

/**
 * This file holds the code that enables custom post types
 * to be added using shortcodes
 */

// Sample Shortcode: [kickpress post_type="people" view="archive"]
function kickpress_shortcode_handler( $atts, $content = null ) {
	global $kickpress_api, $post;

	$kickpress_global_temp = $kickpress_api;

	$params = shortcode_atts( array(
		'id'                => null,
		'post_id'           => null,
		'post_type'         => 'post',
		'posts_per_page'    => '10',
		'title'             => '',
		'terms'             => '',
		'sort_field'        => 'date',
		'sort_direction'    => 'DESC',
		'ajax'              => false,
		'bottom_pagination' => true,
		'view'              => ''
	), $atts );

	if ( isset( $params['terms'] ) ) {
		$local_terms = $params['terms'];
		unset( $params['terms'] ); // causing colissions with global terms array
	}

	if ( isset( $kickpress_api->params['term'] ) )
		unset( $kickpress_api->params['term'] );

	if ( $kickpress_api = kickpress_init_api( $params['post_type'] ) ) {

		if ( $alias = $kickpress_api->is_valid_view($params['view'])){
			//$kickpress_api->params['view'] = $alias;
			//$kickpress_api->params['view_alias'] = $params['view'];
			$params['view_alias'] = $params['view'];
			$params['view'] = $alias;
		}

		$kickpress_api->params = array_merge( $kickpress_api->params, $params );

		if ( isset( $kickpress_api->params['post_id'] ) && ! isset( $kickpress_api->params['id'] ) )
			$kickpress_api->params['id'] = $kickpress_api->params['post_id'];

		if ( ! empty( $kickpress_api->params['id'] ) )
			$kickpress_api->params['posts_per_page'] = null;

		// Find an instance of the terms array and parse it.
		if ( isset( $local_terms ) ) {
			if ( ! isset( $kickpress_api->params['term'] ) || ! is_array( $kickpress_api->params['term'] ) )
				$kickpress_api->params['term'] = array();

			$terms_array = explode( ',', $local_terms );

			foreach ( $terms_array as $term_pair ) {
				if ( $try_term = kickpress_parse_term_pair( $term_pair, $kickpress_api->params['terms'] ) ) // $params['terms']
					$kickpress_api->params['term'][$try_term[0]][] = $try_term[1];
			}
		}

		extract( $kickpress_api->params );

		// Create a new query instance
		$query_args = array(
			'post_type'=>$post_type
		);

		if ( ! empty( $id ) ) {
			$query_args = array(
				'p'             => $id,
				'post_type'      => $post_type,
				'posts_per_page' => 1
			);
		} else {
			$query_args = array(
				'post_type'      => $post_type,
				'orderby'        => $sort_field,
				'order'          => $sort_direction,
				'posts_per_page' => $posts_per_page
			);
		}

		if ( ! empty( $term ) ) {
			foreach ( $term as $taxonomy => $term_names ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $term_names
				);
			}
		}

		kickpress_query( $query_args );

		$template_args = array(
			'view'      => $view,
			'post_type' => $post_type
		);

		if ( function_exists( 'get_post_format' ) ) {
			if ( $post_format = get_post_format( $post->ID ) )
				$template_args['post_format'] = $post_format;
		}

		if ( ! $html = kickpress_get_loop_template( $template_args ) ) {
			$html = '<p>A valid loop.php file does not exist for this theme, please contact the site adminstrator.</p>';
		}

		kickpress_reset_query();

		$kickpress_api = $kickpress_global_temp;

		return $html;
	}
}

// Sample Shortcode: [kickpress-bookmarks post_type="people" view="archive"]
function kickpress_bookmarks_shortcode( $atts, $content = null ) {
	global $post;

	$params = shortcode_atts( array(
		'blog_id'           => null,
		'post_id'           => null,
		'count'             => null,
		'orderby'           => 'comment_date_gmt',
		'order'             => 'DESC',
		'number'            => 'unlimited',
		'style'             => 'ul'
	), $atts );

	if ( isset( $params['blog_id'] ) ) {
		// TODO: Allow the user to specify a blog from the WordPress Multisite environment
	}

	$bookmarks = kickpress_get_bookmarks( $params );
	$html = kickpress_get_bookmark_list( array( 'style' => $params['style'] ), $bookmarks );

	return $html;
}

// Sample Shortcode: [kickpress-notes post_type="people" view="archive"]
function kickpress_notes_shortcode( $atts, $content = null ) {
	global $post;

	$params = shortcode_atts( array(
		'blog_id'           => null,
		'post_id'           => null,
		'count'             => null,
		'orderby'           => 'comment_date_gmt',
		'order'             => 'DESC',
		'number'            => 'unlimited',
		'style'             => 'ul'
	), $atts );

	if ( isset( $params['blog_id'] ) ) {
		// TODO: Allow the user to specify a blog from the WordPress Multisite environment
	}

	$notes = kickpress_get_notes( $params );
	$html = kickpress_get_note_list( array( 'style' => $params['style'] ), $notes );

	return $html;
}

// Sample Shortcode: [kickpress-tasks post_type="post" view="archive"]
function kickpress_tasks_shortcode( $atts, $content = null ) {
	global $post;

	$params = shortcode_atts( array(
		'blog_id'           => null,
		'post_id'           => null,
		'count'             => null,
		'orderby'           => 'comment_date_gmt',
		'order'             => 'DESC',
		'number'            => 'unlimited',
		'style'             => 'ul'
	), $atts );

	if ( isset( $params['blog_id'] ) ) {
		// TODO: Allow the user to specify a blog from the WordPress Multisite environment
	}

	$tasks = kickpress_get_tasks( $params );
	$html = kickpress_get_task_list( array( 'style' => $params['style'] ), $tasks );

	return $html;
}

function kickpress_series_shortcode( $atts, $content = null ) {
	$params = shortcode_atts( array(
		'post_type' => 'any',
		'task_type' => 'any'
	), $atts );

	$tasks = kickpress_get_tasks( array(
		'meta_query' => array(
			array(
				'key'     => '_series',
				'value'   => '_series',
				'compare' => 'LIKE'
			)
		)
	) );

	$progress = array();

	foreach ( $tasks as $task ) {
		$meta_key = explode( ':', $task->comment_meta['_series'] );

		if ( count( $meta_key ) >= 3 ) {
			$post_type = $meta_key[1];
			$post_name = $meta_key[2];
			$task_type = isset( $meta_key[3] ) && 'reading' == $meta_key[3] ? 'post' : 'task';

			if ( ! isset( $progress[$post_type] ) ) {
				$progress[$post_type] = array();
			}

			if ( ! isset( $progress[$post_type][$post_name] ) ) {
				$progress[$post_type][$post_name] = array(
					'count' => 0,
					'total' => 0
				);
			}

			if ( in_array( $task_type, array( 'any', $params['task_type'] ) ) ) {
				if ( ! isset( $progress[$post_type][$post_name]['series']) ) {
					$post = array_shift( get_posts( array(
						'post_type' => $post_type,
						'name'      => $post_name
					) ) );

					$progress[$post_type][$post_name]['series'] = $post;
				}

				$progress[$post_type][$post_name]['count'] += min( $task->comment_karma, 1 );
				$progress[$post_type][$post_name]['total']++;
			}
		}
	}

	$html = '<ul class="series-summary-list">';

	foreach ( $progress as $post_type => $posts ) {
		foreach ( $posts as $post_name => $post_data ) {
			$html .= sprintf( '<li class="series-summary">'
				. '<a href="%s" class="series-title">%s</a> '
				. '<span class="series-progress">%d%%</span>' . '<a href="%s" class="fa fa-times" title="Un-enroll"></a></li>',
				get_permalink( $post_data['series']->ID ),
				$post_data['series']->post_title,
				$post_data['count'] / $post_data['total'] * 100,
				get_permalink( $post_data['series']->ID ) . 'api/quit-series?callback=' . urlencode( get_site_url( $blog_id, '/profile' ) )
			);
		}
	}

	$html .= '</ul>';

	return $html;
}

?>
