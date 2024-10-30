<?php
/**
 * This file holds all of kickpress functions
 */

function kickpress_option_query( $option_name, $args = array(), $default = null ) {
	global $wpdb;

	$filters = array();

	if ( ! empty( $option_name ) )
		$filters[] = "option_name LIKE '{$option_name}'";

	foreach ( (array) $args as $key => $value ) {
		if ( empty( $key ) || empty( $value ) ) continue;

		$pattern = sprintf( 's:%d:"%s";', strlen( $key ),
			_kickpress_escape_key( $key ) );

		if ( is_int( $value ) )
			$pattern .= sprintf( 'i:%d;', $value );
		elseif ( is_string( $value ) )
			$pattern .= sprintf( 's:%d:"%s";', strlen( $value ),
				_kickpress_escape_value( $value ) );

		$filters[] = "option_value LIKE '%{$pattern}%'";
	}

	if ( empty( $filters ) ) return $default;

	$sql = "SELECT * FROM {$wpdb->options} "
	     . "WHERE " . implode( " AND ", $filters );

	if ( $results = $wpdb->get_results( $sql ) ) {
		$objects = array();

		foreach ( $results as $row ) {
			$objects[$row->option_name] = maybe_unserialize( $row->option_value );
		}

		return $objects;
	}

	return $default;
}

function kickpress_user_meta_query( $user_id, $meta_key, $args = array(), $default = null ) {
	global $wpdb;

	$filters = array();

	if ( $user_id = intval( $user_id ) )
		$filters[] = "user_id = {$user_id}";

	if ( ! empty( $meta_key ) )
		$filters[] = "meta_key LIKE '{$meta_key}'";

	foreach ( (array) $args as $key => $value ) {
		if ( empty( $key ) || empty( $value ) ) continue;

		$pattern = sprintf( 's:%d:"%s";', strlen( $key ),
			_kickpress_escape_key( $key ) );

		/* if ( is_int( $value ) )
			$pattern .= sprintf( 'i:%d;', $value );
		elseif ( is_string( $value ) ) */
			$pattern .= sprintf( 's:%d:"%s";', strlen( $value ),
				_kickpress_escape_value( $value ) );

		$filters[] = "meta_value LIKE '%{$pattern}%'";
	}

	if ( empty( $filters ) ) return $default;

	$sql = "SELECT * FROM {$wpdb->usermeta} "
	     . "WHERE " . implode( " AND ", $filters );

	if ( $results = $wpdb->get_results( $sql ) ) {
		$objects = array();

		foreach ( $results as $row ) {
			$objects[] = maybe_unserialize( $row->meta_value );
		}

		return $objects;
	}

	return $default;
}

function _kickpress_escape_key( $key ) {
	return str_replace( array( '%', '_' ), array( '\%', '\_' ), $key );
}

function _kickpress_escape_value( $value ) {
	return _kickpress_escape_key( addslashes( $value ) );
}

/**
 * Request Section
 *
 * This section holds functions for retrieving information about the current
 * request and its parameters.
 */

/**
 * Evaluates if this is a full page containing a header and footer,
 * or if this is an AJAX call that only requires content within a
 * given page to be loaded.
 */
function kickpress_is_fullpage( $output = null ) {
	if ( kickpress_is_ajax() ) {
		return false;
	} else {
		if ( ! empty( $output ) ) echo $output;
		return true;
	}
}

/**
 * AJAX calls don't get a header, footer, or sidebar
 */
function kickpress_is_ajax() {
	return ( @$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' )
		|| ( @$_SERVER['HTTP_USER_AGENT'] == 'Shockwave Flash' );
}

function kickpress_is_remote_app() {
	return defined( 'REMOTE_APP_TOKEN' ) && REMOTE_APP_TOKEN;
}

function kickpress_is_archive( $args = array() ) {
		return ( kickpress_is_single($args) ? false : true );
}

function kickpress_is_single( $args = array() ) {
	$defaults = array(
		'post_type' => get_post_type(),
		'view'      => kickpress_get_view()
	);

	$args = array_merge( $defaults, $args );

	if ( $api = kickpress_init_api( $args['post_type'] ) ) {
		$views = $api->get_valid_views();

		if ( isset( $views[ $args['view'] ] ) && is_array( $views[ $args['view'] ] ) )
			return $views[ $args['view'] ]['single'];
	}

	return false;
}

function kickpress_get_view() {
	global $kickpress_api;

	if ( ! is_null( $kickpress_api ) && isset( $kickpress_api->params['view'] ) )
		return $kickpress_api->params['view'];

	return null;
}

function kickpress_get_view_alias() {
	global $kickpress_api;

	if ( ! is_null( $kickpress_api ) && isset( $kickpress_api->params['view_alias'] ) )
		return $kickpress_api->params['view_alias'];

	return null;
}

function kickpress_get_post_type( $post_type = '' ) {
	global $kickpress_plugin_options, $kickpress_post_types, $kickpress_api;

	if ( empty( $post_type ) ) {
		$post_type = get_query_var( 'post_type' );
	}

	if ( is_array( $post_type ) ) {
		if ( count( $post_type ) == 1 )
			$post_type = $post_type[0];
	}

	if ( ! is_array( $post_type ) && empty( $post_type ) ) {
		$post_type = 'any';
	/*
	} else {
		$post_type = array();
		foreach ( $kickpress_post_types as $post_type_name => $post_type_values )
			$post_type[] = $post_type_name;
	*/
	}

	return $post_type;
}

function kickpress_get_post_format( $post_id = null ) {
	global $kickpress_api;

	if ( ! empty( $post_id ) )
		return get_post_format( $post_id );
	if ( ! is_null( $kickpress_api ) && isset( $kickpress_api->params['post_format'] ) )
		return $kickpress_api->params['post_format'];

	return null;
}

/**
 * URL Section
 *
 * This section holds functions for generating KickPress API URL's.
 */

function kickpress_api_url( $post_id = null, $args = array() ) {
	global $wp_rewrite, $wp_query, $kickpress_api, $kickpress_plugin_options;

	if ( ! $wp_rewrite->using_permalinks() ) return false;

	$api_trigger = $kickpress_plugin_options['api_trigger']['value'];

	if ( is_array( $post_id ) && empty( $args ) ) {
		$args    = $post_id;
		$post_id = null;
	}

	$defaults = array(
		'post_type'      => get_post_type(),
		'post_format'    => null,
		'page'           => 0,
		'posts_per_page' => 10,
		'action'         => '',
		'action_key'     => null,
		'view'           => '',
		'view_alias'     => '',
		'first_letter'   => '',
		'search'         => is_search() ? $_GET['s'] : '',
		'filter'         => array(),
		'author'         => array(),
		'term'           => array(),
		'date'           => array(),
		'sort'           => array(),
		'rel'            => array(),
		'extra_pairs'    => array(),
		'extra_slugs'    => array()
	);

	$api_args = @array_intersect_key( $kickpress_api->params, $defaults );
	$defaults = wp_parse_args( $api_args, $defaults );

	$defaults['year']  = get_query_var( 'year' );
	$defaults['month'] = get_query_var( 'monthnum' );
	$defaults['day']   = get_query_var( 'day' );

	if ( isset( $defaults['author'] ) && ! is_array( $defaults['author'] ) ){
		$defaults['author'] = explode( ',', $defaults['author'] );
		$defaults['author'] = array_filter( $defaults['author'], 'strlen' );
	}

	if ( is_category() ) {
		$cat = get_category( get_query_var( 'cat' ) );
		$defaults['term']['category'][] = $cat->slug;
	} elseif ( is_tag() ) {
		$tag = get_tag( get_query_var( 'tag_id' ) );
		$defaults['term']['post_tag'][] = $tag->slug;
	} elseif ( is_tax() ) {
		$taxonomy = get_query_var( 'taxonomy' );
		$term_temp = get_query_var( 'term' );
		$term_id_temp = get_query_var( 'term_id' );

		if ( empty( $term_temp ) && ! empty( $term_id_temp ) ) {
			$term_object = get_term_by( 'id', $term_id_temp, $taxonomy );
			$term_temp = $term_object->slug;
		}

		if ( ! empty( $term_temp ) ) {
			$defaults['term'][$taxonomy][] = $term_temp;
		}
	} elseif ( is_author() ){
		$authorVar = get_user_by( 'id', get_query_var( 'author' ) );
		$defaults['author'][] = $authorVar->data->user_nicename;
	}

	// Takes into account a single post format, not an array
	//if ( isset( $defaults['term'][ 'post_format' ] ) ) {
		//$operators = array_keys( $defaults['term'][ 'post_format' ] );
		//$operator  = $operators[0];
		//$defaults[ 'post_format' ] = $defaults['term'][ 'post_format' ][$operator][0];
	unset( $defaults['term'][ 'post_format' ] );
	//}

	// Reset the view_alias if needed
	if ( isset( $args['view'] ) && ! isset( $args['view_alias'] ) )
		$args['view_alias'] = $args['view'];

	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

	if ( is_array( $post_type ) || 'any' == $post_type )
		$post_type = '';

	if ( isset( $term ) )
		$term = kickpress_normalize_term( $term );

	$single_term = count( $term ) == 1;

	if ( $single_term ) {
		$taxonomies = array_keys( $term );
		$taxonomy = $taxonomies[0];

		$single_term = count( $term[$taxonomy] ) == 1;

		if ( $single_term ) {
			$operators = array_keys( $term[$taxonomy] );
			$operator  = $operators[0];
			$terms = $term[$taxonomy][$operator];

			$single_term = count($terms) == 1;
		}
	}

	if ( 0 < $post_id )
		$base_url = get_permalink( $post_id );
	elseif ( ! empty( $post_type ) && 'post' != $post_type ) {
		$base_url = get_post_type_archive_link( $post_type );
		$single_term = false;
	} elseif ( $single_term ){
		$base_url =  get_term_link($term[$taxonomy][$operator][0], $taxonomy);
	} elseif ( 0 < $year ) {
		if ( 0 < $month ) {
			if ( 0 < $day )
				$base_url = get_day_link( $year, $month, $day );
			else
				$base_url = get_month_link( $year, $month);
		} else {
			$base_url = get_year_link( $year, $month, $day );
		}
	} else {
		$base_url = trailingslashit( home_url() );
	}

	if ( 1 < $page && !isset($page_base)) {
		$page_base = $wp_rewrite->pagination_base;
		$base_url .= user_trailingslashit( "{$page_base}/{$page}", 'paged' );
	}

	// $url_parts = array();
	$api_slugs = array();

	/* if ( ! empty( $post_type ) ) {
		if ( 'post' == $post_type ) {
			if ( ! empty( $year ) && is_null( $post_id ) ) {
				$url_parts[] = $year;

				if ( ! empty( $month ) ) {
					$url_parts[] = $month;

					if ( ! empty( $day ) )
						$url_parts[] = $day;
				}
			}
		} else {
			$url_parts[] = $post_type;
		}
	} */

	/* if ( ! is_null( $post_id ) ) {
		$post = get_post( $post_id );

		if ( 'post' == $post->post_type ) {
			$url_parts[0] = date( 'Y', strtotime( $post->post_date ) );
			$url_parts[]  = date( 'm', strtotime( $post->post_date ) );
			$url_parts[]  = date( 'd', strtotime( $post->post_date ) );
		} else {
			$url_parts[0] = $post->post_type;
		}

		$url_parts[] = $post->post_name;

		if ( 1 < $page ) {
			$url_parts[] = 'paged';
			$url_parts[] = (int) $page;
		}
	} elseif ( 1 < $page ) {
		$url_parts[] = 'page';
		$url_parts[] = (int) $page;
	} */

	if ( ! empty( $action ) ) {
		if ( ! is_null( $action_key ) )
			$api_slugs[] = sprintf( KICKPRESS_ACTION_SLUG, $action, $action_key );
		else
			$api_slugs[] = $action;
	} elseif ( ! empty( $view_alias ) ) {
		$api_slugs[] = $view_alias;
	} elseif ( ! empty( $view ) ) {
		$api_slugs[] = $view;
	}

	if ( ! empty( $post_format ) ) {
		$api_slugs[] = 'post_format';
		$api_slugs[] = $post_format;
	}

	// Taking post_type into account - DST 2013.04.19
	if ( ! empty( $post_type ) ) {
		$api_slugs[] = 'post_type';
		$api_slugs[] = $post_type;
	}

	if ( ! empty( $posts_per_page ) ) {
		if ( 10 != $posts_per_page && is_null( $post_id ) ) {
			$api_slugs[] = 'posts_per_page';
			$api_slugs[] = (int) $posts_per_page;
		}
	}

	if ( ! empty( $author ) ) {
		$api_slugs[] = 'author';

		if ( is_array( $author ) )
			$api_slugs[] = implode( ',', array_unique( $author ) );
		else
			$api_slugs[] = $author;
	}

	if ( ! empty( $search ) ) {
		$api_slugs[] = 'search';
		$api_slugs[] = urlencode( $search );
	}

	if ( ! empty( $post_types ) ) {
		$api_slugs[] = 'post_types';

		if ( is_array( $post_types ) )
			$api_slugs[] = implode( ',', array_unique( $post_types ) );
		else
			$api_slugs[] = $post_types;
	}

	if ( ! empty( $filter ) ) {
		foreach ( $filter as $key => $terms ) {
			$api_slugs[] = 'filter[' . $key . ']';
			$api_slugs[] = $terms;
		}
	}

	if ( ! empty( $term ) && ! $single_term ) {
		unset( $term['relationship'] );

		foreach ( $term as $taxonomy => $taxonomy_terms ) {
			foreach ( $taxonomy_terms as $operator => $terms ) {
				$api_slugs[] = "term[{$taxonomy}][{$operator}]";
				$api_slugs[] = implode( ',', array_unique( $terms) );
			}
		}
	}

	if ( ! empty( $date ) ) {
		if ( isset( $date['min'] ) ) {
			$api_slugs[] = 'date[min]';
			$api_slugs[] = date( 'Y-m-d', strtotime( $date['min'] ) );
		}

		if ( isset( $date['max'] ) ) {
			$api_slugs[] = 'date[max]';
			$api_slugs[] = date( 'Y-m-d', strtotime( $date['max'] ) );
		}
	}

	if ( ! empty( $sort ) && is_null( $post_id ) ) {
		foreach ( $sort as $field => $order ) {
			// Sanity check for broken post-type sort settings
			if ( preg_match( '/Sort (Field|Order) #\d/i', $field . $order ) )
				continue;

			$api_slugs[] = 'sort[' . $field . ']';
			$api_slugs[] = $order;
		}
	}

	if ( ! empty( $rel ) ) {
		foreach ( $rel as $name => $slugs ) {
			if ( is_array( $slugs ) )
				$slugs = implode( ',', array_unique( $slugs ) );

			$api_slugs[] = 'rel[' . $name . ']';
			$api_slugs[] = $slugs;
		}
	}

	if ( ! empty( $first_letter ) ) {
		$api_slugs[] = 'first_letter';
		$api_slugs[] = $first_letter;
	}

	if ( ! empty( $extra_pairs ) ) {
		foreach ( (array) $extra_pairs as $key => $value ) {
			$api_slugs[] = $key;
			$api_slugs[] = $value;
		}
	}

	if ( ! empty( $extra_slugs ) ) {
		foreach ( (array) $extra_slugs as $slug ) {
			$api_slugs[] = $slug;
		}
	}

	if ( ! empty( $api_slugs ) ) {
		// $url_parts = array_merge( $url_parts, array( $api_trigger ), $api_parts );
		array_unshift( $api_slugs, $api_trigger );

		$api_path = trailingslashit( implode( '/', $api_slugs ) );
	}

	// return home_url( implode( '/', $url_parts ) . '/' );
	return $base_url . $api_path;
}

function kickpress_api_trigger() {
	echo kickpress_get_api_trigger();
}

function kickpress_get_api_trigger() {
	global $kickpress_plugin_options;
	return $kickpress_plugin_options['api_trigger']['value'];
}

/**
 * Debug Section
 *
 * This section holds functions for conveniently logging debugging information.
 */

function kickpress_log( $data, $file = 'kickpress.log' ) {
	$file = KICKPRESSPATH . '/' . $file;

	if ( $log = @fopen( $file, 'a' ) ) {
		$line = sprintf(
			"%s.%3d %s\t%s\t%s",
			date( 'Y-m-d H:i:s' ),
			fmod( microtime( true ), 1 ) * 1000,
			date( 'P' ),
			KICKPRESS_DEBUG_TOKEN,
			$data
		);

		fwrite( $log, $line . PHP_EOL );
		fflush( $log );
		fclose( $log );

		return true;
	}

	return false;
}

function kickpress_log_backtrace() {
	ob_start();
	debug_print_backtrace();
	$data = ob_get_contents();
	ob_end_clean();

	kickpress_log( PHP_EOL . $data );
}

function kickpress_print_backtrace() {
	ob_start();
	debug_print_backtrace();
	$backtrace = ob_get_contents();
	ob_end_clean();

	echo '<pre>' . htmlspecialchars( $backtrace ) . '</pre>';
}

/**
 * Resource Section
 *
 * This section holds functions for loading various external resources that
 * KickPress requires (eg: stylesheets, javascript libraries, etc).
 */

function kickpress_ajax_reload( $args = array(), $target = 'content-wrapper', $append = 'ajax-append' ) {
	$html = sprintf( '<a rel="%s" href="%s" class="%s">Load More</a>',
		$target,
		kickpress_api_url( $args ),
		$append
	);

	add_action( 'wp_footer', 'kickpress_ajax_footer' );

	return $html;
}

function kickpress_ajax_footer() {
	$src = plugins_url( 'includes/js/kickpress-ajax.js' , __FILE__ );

	wp_register_script( 'ajax-reload', $src );
	wp_enqueue_script( 'ajax-reload' );
}

function kickpress_admin_scripts() {
	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	//wp_enqueue_script('datepicker');
}

function kickpress_enqueue_scripts() {
	global $kickpress_api;

	wp_enqueue_script('jquery');
	wp_enqueue_script('kickpress', get_bloginfo('wpurl') . '/wp-content/plugins/kickpress/includes/js/kickpress.js', false, kickpress_auto_version('/js/kickpress.js', true));

	wp_enqueue_style('grid', get_bloginfo('wpurl') . '/wp-content/plugins/kickpress/includes/css/grid.css', false, kickpress_auto_version('/css/grid.css', true), 'screen');
	wp_enqueue_style('note', get_bloginfo('wpurl') . '/wp-content/plugins/kickpress/includes/css/note.css', false, kickpress_auto_version('/css/note.css', true), 'screen');

	if ( ! is_null( $kickpress_api ) )
		$kickpress_api->enqueue_scripts();
}

/**
 * String Processing Section
 *
 * This section holds function for parsing/generating various utility strings.
 */

function kickpress_normalized_query_parameters( $query_params, $query_string = array(), $unset_array = array() ) {
	parse_str( $query_params, $array );
	if ( get_magic_quotes_gpc() )
		$array = stripslashes_deep( $array );

	if ( ! empty($unset_array) ) {
		foreach( $unset_array as $unset ) {
			unset( $array[$unset] );
		}
	}

	if ( ! empty($query_string) ) {
		$array = array_merge($array, $query_string);
	}

	$names  = array_keys( $array );
	$values = array_values( $array );

	$names  = array_map( 'kickpress_encode_3986', $names  );
	$values = array_map( 'kickpress_encode_3986', $values );

	$pairs  = array_map( 'kickpress_join_with_equal_sign', $names, $values );

	return $pairs;
}

function kickpress_encode_3986( $string ) {
	$string = rawurlencode( $string );
	// prior to PHP 5.3, rawurlencode was RFC 1738
	return str_replace( '%7E', '~', $string );
}

function kickpress_join_with_equal_sign( $name, $value ) {
	return "{$name}={$value}";
}

/**
 * Currently never called
 */
function kickpress_header_includes() {
	global $kickpress_api;

	if ( isset( $kickpress_api->params['post_type'] ) )
		$kickpress_api->include_scripts_and_styles();
}

function kickpress_make_readable($value) {
	// Make a caption human friendly
	return ucwords(str_replace(array("_","-"), " ", $value));
}

function kickpress_filter_pairs() {
	global $wp_query;
	$filter_pairs = array();

	if ( $year = $wp_query->query_vars['year'] )
		$filter_pairs['year'] = $year;

	if ( $month = $wp_query->query_vars['monthnum'] )
		$filter_pairs['monthnum'] = $month;
	elseif ( isset($wp_query->query_vars['month']) )
		$filter_pairs['monthnum'] = $wp_query->query_vars['month'];

	if ( $day = $wp_query->query_vars['day'] )
		$filter_pairs['day'] = $day;

	if ( ! empty($_GET['s']) )
		$filter_pairs['search'] = esc_attr($_GET['s']);
	elseif ( $search = $wp_query->query_vars['s'] )
		$filter_pairs['search'] = $search;
	elseif ( isset($wp_query->query_vars['search']) )
		$filter_pairs['search'] = $wp_query->query_vars['search'];

	if ( isset($wp_query->query_vars['category']) )
		$filter_pairs['category'] = $wp_query->query_vars['category'];
	elseif ( isset($wp_query->query_vars['category_name']) )
		$filter_pairs['category'] = $wp_query->query_vars['category_name'];

	if ( $tag = $wp_query->query_vars['tag'] )
		$filter_pairs['tag'] = $tag;

	return $filter_pairs;
}

function kickpress_auto_version($url, $plugin_dir=true) {
	if ( $plugin_dir )
		$file_path = WP_PLUGIN_DIR.'/kickpress/includes';
	else
		$file_path = $_SERVER['DOCUMENT_ROOT'];

	return filemtime($file_path.$url);
}

function kickpress_query_pairs($args=array(), $filter_pairs=array(), $path='', $base=true, $query_params='') {
	global $kickpress_api, $wp_query;
	$api_trigger = kickpress_get_api_trigger();
	$filter_defaults = array();
	$pretty = true;

	// get_query_var( 'post_type' )
	if ( ! is_object($kickpress_api) || empty($kickpress_api->params) ) {
		$post_type = kickpress_get_post_type();
		$kickpress_api = kickpress_init_api( $post_type );
	}

	$filter_array = $kickpress_api->get_filter_array();

	// Fetch all filters
	foreach ( $filter_array as $filter ) {
		if ( isset($args[$filter]) ) {
			// find filters that are arrays
			if ( is_array($args[$filter]) ) {
				foreach ( $args[$filter] as $filter_key=>$filter_value ) {
					if ( is_array($filter_value) ) {
						foreach ( $filter_value as $sub_filter_key=>$sub_filter_value )
							$filter_defaults[$filter.'['.$filter_key.']['.$sub_filter_key.']'] = $sub_filter_value;
					} else {
						$filter_defaults[$filter.'['.$filter_key.']'] = $filter_value;
					}
				}
			} else {
				$filter_defaults[$filter] = $args[$filter];
			}
		}
	}

	if ( is_array($filter_defaults) )
		$filter_array = array_merge($filter_defaults, $filter_pairs);
	else
		$filter_array = $filter_pairs;

/*
	// Make sure that category is the first in the list
	if ( isset($filter_array['category']) ) {
		$category = $filter_array['category'];
		unset($filter_array['category']);
		$filter_array = array_merge(array('category'=>$category), $filter_array);
	}

	// Make sure that tag is the first in the list
	if ( isset($filter_array['tag']) ) {
		$tag = $filter_array['tag'];
		unset($filter_array['tag']);
		$filter_array = array_merge(array('tag'=>$tag), $filter_array);
	}
*/

	if ( $base ) {
		if ( ! empty($args['id']) )
			$path = get_permalink($args['id']);

		if ( $path == '' ) {
			// Figure out the current page
			if ( get_query_var('paged') )
				$cur_page = get_query_var('paged');
			elseif ( get_query_var('page') )
				$cur_page = get_query_var('page');
			else
				$cur_page = 1;

			if ( isset($filter_array['page']) )
				$page = $filter_array['page'];
			else
				$page = 1;

			if ( ! (($cur_page == $page) && ($page > 1)) )
				$path = esc_url(get_pagenum_link($page));
			elseif ( ! empty($args['post_type']) )
				$path = '/'.$args['post_type'].'/';
			else
				$path = '/';
		}
	}

	// Get the ?query=string
	$path_split = explode('?', $path);
	$path = $path_split[0];
	if ( isset($path_split[1]) )
		$query_params = trim($query_params) == '' ? $path_split[1] : '&'.$path_split[1];

	// Defaults to "api/"
	$path .= $api_trigger . '/';

	if ( isset($filter_array['action']) ) {
		$path .= $filter_array['action'].'/';
		unset($filter_array['action']);
	} elseif ( isset($filter_array['view']) ) {
		$path .= $filter_array['view'].'/';
		unset($filter_array['view']);
	}

	$first_item = 1;
	foreach ( $filter_array as $filter=>$value ) {
		if ( $filter == 'page' )
			continue;

		$value = trim(str_replace(array(' ','%20'), '+', $value));
		if ( $value != '+' && $value != '' && $value != NULL ) {
			$path .= "$filter/$value/";
/*
				if ( $first_item++ )
					$path .= "?$filter=$value";
				else
					$path .= "&$filter=$value";
*/
		}
	}

/*
	if ( trim($query_params) != '' ) {
		if ( strpos($path, '?') === false )
			$path .= '?'.$query_params;
		else
			$path .= '&'.$query_params;
	}

	if ( ! empty($anchor) )
		$path .= '#'.$anchor;
*/
	return $path;
}

function kickpress_parse_term_pair($term_pair, $valid_terms=null) {
	if ( strpos($term_pair, '=') ) {
		$term_parts = explode('=', $term_pair);
		$term_parts[0] = trim($term_parts[0]);
		$term_parts[1] = trim($term_parts[1]);

		return $term_parts;
	}

	return false;
}

/**
 * Options Section
 *
 * This section holds functions used for parsing and handling post type options
 * form element settings.
 */

function kickpress_get_builtin_options($builtin_options_name) {
	$function_name = 'kickpress_get_'.$builtin_options_name;
	if ( function_exists($function_name) )
		return $function_name();
	else
		return array();
}

/**
 * Evaluates a variable to TRUE or FALSE.
 *
 * Strings containing only whitespace and 'false' are evaluated to FALSE. Note
 * this isn't PHP's default behavior.
 *
 * All other scalar values are evaluate to their boolean equivalents.
 *
 * Arrays, objects, resources and NULL values are evaluated to the given default
 * if one is provided, or FALSE otherwise.
 */
function kickpress_boolean( $value = null, $default = false ) {
	// ensure that the default is a boolean value
	if ( ! is_bool( $default ) )
		$default = kickpress_boolean( $default );

	if ( is_null( $value ) ) {
		return $default;
	} elseif ( is_string( $value ) ) {
		$true_strings  = array( 'true',  'enable' );
		$false_strings = array( 'false', 'disable' );

		$lower_value = strtolower( $value );

		if ( in_array( $lower_value, $true_strings ) )
			return true;
		elseif ( in_array( $lower_value, $false_strings ) )
			return false;

		return (bool) trim( $value );
	} elseif ( is_scalar( $value ) ) {
		return (bool) $value;
	}

	return $default;
}

/**
 * URL Shortener Section
 *
 * This section holds functions for generating and handling shortened URL's.
 */

function kickpress_slug_hooks($slug) {
	global $kickpress_post_types;

	foreach ( $kickpress_post_types as $key=>$value ) {
		if ( ! isset($value['post_type']) )
			continue;

		$post_type = (string) $value['post_type'];

		if ( $api = kickpress_init_api($post_type) ) {
			if ( $location = $api->slug_hook($slug) ) {
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: '.$location);
				exit;
			}
		}
	}

	return false;
}

function kickpress_id2slug($id, $key=false) {
	// post->ID to Custom URL Slug
	$slug = '';
	$key = kickpress_shortener_key($key);
	$base = strlen($key);

	for ( $t=floor(log10($id)/log10($base)); $t>=0;$t-- ) {
		$a = floor($id/pow($base, $t));
		$slug .= substr($key, $a, 1);
		$id -= ($a * pow($base, $t));
	}

	return $slug;
}

function kickpress_slug2id($slug, $key=false) {
	// Custom URL Slug to post->ID
	$id = 0;
	$key = kickpress_shortener_key($key);
	$base = strlen($key);
	$len = strlen($slug)-1;

	for ( $t=0; $t<=$len; $t++ )
		$id += strpos($key, substr($slug, $t, 1)) * pow($base, $len-$t);

	return $id;
}

function kickpress_shortener_key($key) {
	// Remove characters that might be visually confusing to users: 01Ol
	if ( ! $key )
		return '23456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
	else
		return $key;
}

/**
 * Query Stack Section
 *
 * This section holds functions for managing nested queries
 */

function kickpress_query($query) {
	global $kickpress_query_stack;

	// push current query object onto stack
	$kickpress_query_stack[] =& $GLOBALS['wp_query'];

	// nullify global query object pointer
	unset($GLOBALS['wp_query']);

	// assign global pointer to new query object
	$GLOBALS['wp_query'] =& new WP_Query();

	// initialize new global query state
	$results = $GLOBALS['wp_query']->query($query);

	return $results;
}

function kickpress_reset_query() {
	global $kickpress_query_stack;

	// nullfiy global query object pointer
	unset($GLOBALS['wp_query']);

	// pop previous query object from stack
	// and assign global pointer to it
	if ( ! empty($kickpress_query_stack) ) {
		$index = (count($kickpress_query_stack) - 1);

		$GLOBALS['wp_query'] =& $kickpress_query_stack[$index];

		// pop previous query object from stack
		array_pop($kickpress_query_stack);
	}

	// restore previous global query state
	wp_reset_postdata();
}

/**
 * Extending KickPress Plugin Section
 *
 * This section holds functions for extending KickPress' form elements and modules
 */

function kickpress_get_form_element( $element = 'text', $args = array() ) {
	$file  = '/elements/class-' . str_replace( '_', '-', $element ) . '.php';
	$class = 'kickpress_' . str_replace( '-', '_', $element );

	// Look in the child theme, parent theme, and plugin
	if ( file_exists( STYLESHEETPATH . $file) )
		require_once STYLESHEETPATH . $file;
	elseif ( file_exists( TEMPLATEPATH . $file ) )
		require_once TEMPLATEPATH . $file;
	elseif ( file_exists( KICKPRESSPATH . $file ) )
		require_once KICKPRESSPATH . $file;

	if ( class_exists( $class ) )
		return new $class( $args );
	else
		return new kickpress_form_elements( $args );

	/* save for now
	if ( ! class_exists( $class ) && ! empty( $class ) ) {
		// make a dynamic class that extends the API on the fly
		$dynamic_class = sprintf('
			class %1$s extends kickpress_form_elements {
				public function __construct($params) {
					parent::__construct($params);
				}
			}',
			$classname
		);

		eval( $dynamic_class );
	}
	*/
}

function kickpress_the_form_element( $element = 'text', $args = array(), $params = array() ) {
	$form_element = kickpress_get_form_element( $element, $args );
	echo $form_element->element( $params );
}

/**
 * Theme/Template Section
 *
 * This section holds functions for KickPress's advanced themeing framework
 */

function kickpress_post_type_toolbar( $post_type = '' ) {
	if ( empty( $post_type ) ) $post_type = get_post_type();

	if ( ! dynamic_sidebar( $post_type . '-toolbar' ) && $api = kickpress_init_api( $post_type ) ) {
		if ( 'true' == $api->params['view_toolbar'] ) {
			$before_widget = apply_filters( 'kickpress_before_widget', '<aside id="%1$s" class="widget %2$s">' );

			$class_name = 'toolbar ' . kickpress_viewbar_widget::$classname;

			the_widget( 'kickpress_viewbar_widget', array(
				'post_type' => $post_type
			), array(
				'before_widget' => sprintf( $before_widget, 'kickpress_viewbar_widget-0', $class_name ),
				'after_widget'  => apply_filters( 'kickpress_after_widget', '</aside>' ),
				'before_title'  => apply_filters( 'kickpress_before_title', '<h3 class="widget-title">' ),
				'after_title'   => apply_filters( 'kickpress_after_title', '</h3>' )
			) );
		}

		if ( 'true' == $api->params['categories_toolbar'] ) {
			$before_widget = apply_filters( 'kickpress_before_widget', '<aside id="%1$s" class="widget %2$s">' );

			$class_name = 'toolbar ' . kickpress_termsbar_widget::$classname;

			the_widget( 'kickpress_termsbar_widget', array(
				'taxonomy' => $post_type . '-category'
			), array(
				'before_widget' => sprintf( $before_widget, 'kickpress_termsbar_widget-0', $class_name ),
				'after_widget'  => apply_filters( 'kickpress_after_widget', '</aside>' ),
				'before_title'  => apply_filters( 'kickpress_before_title', '<h3 class="widget-title">' ),
				'after_title'   => apply_filters( 'kickpress_after_title', '</h3>' )
			) );
		}

		if ( 'false' != $api->params['alphabar'] ) {
			$before_widget = apply_filters( 'kickpress_before_widget', '<aside id="%1$s" class="widget %2$s">' );

			$class_name = 'toolbar ' . kickpress_alphabar_widget::$classname;

			the_widget( 'kickpress_alphabar_widget', array(
				'post_type' => $post_type
			), array(
				'before_widget' => sprintf( $before_widget, 'kickpress_alphabar_widget-0', $class_name ),
				'after_widget'  => apply_filters( 'kickpress_after_widget', '</aside>' ),
				'before_title'  => apply_filters( 'kickpress_before_title', '<h3 class="widget-title">' ),
				'after_title'   => apply_filters( 'kickpress_after_title', '</h3>' )
			) );
		}
	}
}

function kickpress_loop_template( $args = array() ) {
	return kickpress_get_loop_template( $args, true );
}

function kickpress_get_loop_template( $args = array(), $load = false ) {
	$default_args = array(
		'post_type' => '',
		'view'      => ''
	);

	$args = wp_parse_args( $args, $default_args );

	extract( $args );

	$post_type = 'any' == $post_type ? null : $post_type;

	if ( kickpress_is_single( $args ) ) {
		unset( $GLOBALS['post'] );
		$args['excerpt'] = false;
		return kickpress_content_template( $args, $load );
	}

	//if ( 'post' == $post_type ) $post_type = '';

	$template_names = array();

	if ( ! empty( $post_type ) ) {
		if ( ! empty( $view ) ) {
			$template_names[] = 'loop-' . $post_type . '-' . $view . '.php';
			$template_names[] = 'loop-' . $post_type . '.php';
			$template_names[] = 'loop-' . $view . '.php';
		} else {
			$template_names[] = 'loop-' . $post_type . '.php';
		}
	} elseif ( ! empty( $view ) ) {
		$template_names[] = 'loop-' . $view . '.php';
	}

	$template_names[] = 'loop.php';

	return kickpress_locate_template( $template_names, $load, false );
}

function kickpress_excerpt_template( $args = array() ) {
	return kickpress_get_excerpt_template( $args, true );
}

function kickpress_get_excerpt_template( $args = array(), $load = false ) {
	$args['excerpt'] = true;
	return kickpress_get_content_template( $args, $load );
}

function kickpress_content_template( $args = array(), $load = true ) {
	return kickpress_get_content_template( $args, $load );
}

function kickpress_get_content_template( $args = array(), $load = false ) {
	// Order: 1. excerpt/content - 2. post_type - 3. view - 4. post_format
	$default_args = array(
		'post_type' => '',
		'view' => '',
		'post_format' => '',
		'excerpt' => false
	);

	$args = wp_parse_args( $args, $default_args );

	extract( $args );

	$post_type = 'any' == $post_type ? null : $post_type;

	if ( 'form' == $view && $api = kickpress_init_api( $post_type, $args ) ) {
		if ( $load ) echo $api->form();
		return $load ? true : $api->form();
	} elseif ( 'excerpt' == $view ) {
		$excerpt = true;
	}

	//if ( 'post' == $post_type ) $post_type = '';

	$content = $excerpt ? 'excerpt' : 'content';

	$template_names = array();

	if ( ! empty( $post_type ) ) {
		if ( ! empty( $view ) ) {
			if ( ! empty( $post_format ) ) {
				$template_names[] = $content . '-' . $post_type . '-' . $view . '-' . $post_format . '.php';
				$template_names[] = $content . '-' . $post_type . '-' . $view . '.php';
				$template_names[] = $content . '-' . $post_type . '-' . $post_format . '.php';
				$template_names[] = $content . '-' . $post_type . '.php';
				$template_names[] = $content . '-' . $view . '-' . $post_format . '.php';
				$template_names[] = $content . '-' . $view . '.php';
				$template_names[] = $content . '-' . $post_format . '.php';
			} else {
				$template_names[] = $content . '-' . $post_type . '-' . $view . '.php';
				$template_names[] = $content . '-' . $post_type . '.php';
				$template_names[] = $content . '-' . $view . '.php';
			}
		} elseif ( ! empty( $post_format ) ) {
			$template_names[] = $content . '-' . $post_type . '-' . $post_format . '.php';
			$template_names[] = $content . '-' . $post_type . '.php';
			$template_names[] = $content . '-' . $post_format . '.php';
		} else {
			$template_names[] = $content . '-' . $post_type . '.php';
		}
	} elseif ( ! empty( $view ) ) {
		if ( ! empty( $post_format ) ) {
			$template_names[] = $content . '-' . $view . '-' . $post_format . '.php';
			$template_names[] = $content . '-' . $view . '.php';
			$template_names[] = $content . '-' . $post_format . '.php';
		} else {
			$template_names[] = $content . '-' . $view . '.php';
		}
	} elseif ( ! empty( $post_format ) ) {
		$template_names[] = $content . '-' . $post_format . '.php';
	}

	$template_names[] = $content . '.php';

	return kickpress_locate_template( $template_names, $load, false );
}

function kickpress_bookmarks_template( $file = 'bookmarks.php' ) {
	return kickpress_get_bookmarks_template( $file, true );
}

function kickpress_get_bookmarks_template( $file = 'bookmarks.php', $load = false ) {
	if ( empty( $file ) ) $file = 'bookmarks.php';
	return kickpress_locate_template( $file, $load );
}

function kickpress_notes_template( $file = 'notes.php' ) {
	return kickpress_get_notes_template( $file, true );
}

function kickpress_get_notes_template( $file = 'notes.php', $load = false ) {
	if ( empty( $file ) ) $file = 'notes.php';
	return kickpress_locate_template( $file, $load );
}

function kickpress_locate_template( $template_names, $load = false, $require_once = true ) {
	$template_file = '';

	foreach ( (array) $template_names as $template_name ) {
		if ( empty( $template_name ) ) continue;

		if ( file_exists( STYLESHEETPATH . '/' . $template_name ) ) {
			$template_file = STYLESHEETPATH . '/' . $template_name;
			break;
		}

		if ( file_exists( TEMPLATEPATH . '/' . $template_name ) ) {
			$template_file = TEMPLATEPATH . '/' . $template_name;
			break;
		}

		if ( file_exists( KICKPRESSPATH . '/templates/' . $template_name ) ) {
			$template_file = KICKPRESSPATH . '/templates/' . $template_name;
			break;
		}
	}

	if ( '' != $template_file ) {
		if ( ! $load ) ob_start();

		load_template( $template_file, $require_once );

		if ( ! $load ) {
			$html = ob_get_contents();
			ob_end_clean();

			return ! empty( $html ) ? $html : false;
		}
	}

	return $template_file;
}

/**
 * Takes a set of terms and returns a standardized array of terms.
 * @param  Array $term  A term that may or may not be in the standard Kickpress format
 * @return Array        A normalized Kickpress formatted term array.
 */
function kickpress_normalize_term( $term ) {
	if ( isset( $term ) && is_array( $term ) ) {
		foreach ( $term as $taxonomy_name => $taxonomy_value ) {
			$terms = array();

			//If string, explode, set operator
			if ( is_string( $taxonomy_value ) ) {
				$terms['in'] = explode( ",", $taxonomy_value );
			} elseif( is_array( $taxonomy_value ) ) { //If array, numerically indexed (iterate), add operator, merge
				foreach ( $taxonomy_value as $key => $value ) {
					if ( is_numeric($key) ) {
						if ( !isset( $terms['in'] ) ) $terms['in'] = array();
						$terms['in'] = array_merge( $terms['in'], explode( ",", $value ) );
					} elseif ( is_string( $key ) && in_array( $key, array('in','not-in') ) ) {
						if (is_string($value)) {
							if ( !isset( $terms[$key] ) ) $terms[$key] = array();
							$terms[$key] = array_merge( $terms[$key], explode( ",", $value ) );
						} elseif ( is_array( $value ) ) {
							foreach ( $value as $term_value ) {
								if ( !isset( $terms[$key] ) ) $terms[$key] = array();
								$terms[$key] = array_merge( $terms[$key], explode( ",", $term_value ) );
							}
						}
					}
				}
			}
			$term[$taxonomy_name] = $terms;
		}
	}
	return $term;
}

?>