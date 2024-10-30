<?php
/**
 * This file holds all of the logic for managing API
 * calls from the URL's query string
 */

add_filter( 'redirect_canonical', 'kickpress_redirect_canonical', 10, 2 );

function kickpress_redirect_canonical( $redirect_url, $request_url ) {
	$api_trigger = kickpress_get_api_trigger();

	if ( strpos( REQUEST_URI, '/' . $api_trigger . '/') !== false )
		return $request_url;

	return $redirect_url;
}

function kickpress_init_redirects() {
	$url = stripslashes( REQUEST_URI );
	// $url = urldecode( stripslashes( REQUEST_URI ) );

	// First, don't try and redirect if wordpress owns this process
	if ( strpos($url, 'wp-admin' )
		|| strpos($url, 'index.php')
		|| strpos($url, 'login.php')
		) {
		return;
	}

	// Next, check to see if there is a 'p=N' or 'page_id=N' to match against
	if ( preg_match('#[?&](p|page_id|attachment_id)=(\d+)#', $url, $values) )
		return;

	// Finally, start evaluating for KickPress redirects
	global $wpdb, $kickpress_api, $kickpress_post_types, $kickpress_builtin_post_types;
	$valid_post_type = false;
	$api_trigger = kickpress_get_api_trigger();

	// Get the ?query=string
	$url_split = explode('?', $url);
	$url = urldecode($url_split[0]);
	$query_params = ( isset($url_split[1]) ? urldecode( $url_split[1] ) : null );

	// Stop processing for schema pages
	if ( strpos($url, '/schema' ) !== false ) {
		require_once( WP_PLUGIN_DIR.'/kickpress/kickpress-schema.php' );
		exit();
	}

	// Resolve WP installs in sub-directories
	$blogurl = ((get_option('home')) ? get_option('home') : get_option('siteurl'));
	preg_match('#^[hpst]+://.*?(/.*)$#', $blogurl, $subdir);

	kickpress_oauth_authenticate();

	if ( isset($subdir[1]) ) {
		$match_str = '#^'.$subdir[1].'/(.*?)([\?/].*?)?$#';
	} else {
		$match_str = '#^/(.*?)([\?/].*?)?$#';
	}

	if ( preg_match($match_str, $url, $match_val) ) {
		// Remove leading and trailing slash
		//$url = trim($match_val[0], '/');
		$eval_post_type = $match_val[1];
		if ( ! isset($match_val[2]) )
			$match_val[2] = null;

		// Find if there are api variables in the url
		if ( strpos($url, '/' . $api_trigger . '/') !== false ) {
			$api_params = explode('/' . $api_trigger . '/', $url);

			// Remove leading and trailing slash
			$url_params = trim($api_params[1], '/');

			if ( ! empty($url_params) ) {
				$query_parts = explode('/', $url_params);

				if ( count($query_parts) ) {
					// See if we have to capture the post type from the query parts
					foreach( $query_parts as $qp_index => $qp_value ) {
						if ( 'post_type' == $qp_value && isset( $query_parts[$qp_index+1] ) ) {
							$eval_post_type = $query_parts[ $qp_index+1 ];
							break;
						}
					}
				}
			}
		}

		// Determine if there is an API call to handle
		if ( isset($kickpress_post_types[$eval_post_type]) && ! in_array( $eval_post_type, array( 'any', 'page', 'nav_menu_item', 'attachment' ) ) ) {
			$valid_post_type = true;
			$post_type = $eval_post_type;
		} else {
			$post_type = kickpress_get_post_type( );
		}

		if ( $kickpress_api = kickpress_init_api($post_type) ) {
			if ( empty($kickpress_api->params) )
				$kickpress_api->params = array();

			if ( count($_GET) ) {
				foreach ( $_GET as $get_key=>$get_value ) {
					$get_key = esc_attr($get_key);
					$get_key = esc_attr($get_value);

					// Grab the API's filter array for this post type
					$filter_array = $kickpress_api->get_filter_array();

					if ( in_array($get_key, $filter_array) )
						$kickpress_api->params[$get_key] = $get_value;
				}
			}

			// Looks for "/api/" by default unless admin changes the default api trigger
			if ( !empty( $api_params ) ) {
				if ( kickpress_api_handler( $api_params, $query_params ) ) {
					// This is a valid API call, return to WordPress
					return true;
				}
			}
		}

		//if ( $post_type == "any" ) return;

		// Still here, maybe there is something else we can try...
		if ( $valid_post_type ) {
			// This is valid post type, but check to see if it is a slug redirect
			kickpress_slug_redirect($post_type, $match_val[2]);
		} else {
			// This is not a valid post type, check to see if it is a slug redirect
			kickpress_slug_redirect($match_val[1], $match_val[2]);

			// Match nested slugs (sub-directory nesting)
			$sql = "
				SELECT
					ls.meta_value AS link_slug,
					lu.meta_value AS link_url,
					lp.post_title
				FROM
					$wpdb->postmeta ls
				INNER JOIN $wpdb->postmeta lu
					ON lu.post_id = ls.post_id
					AND lu.meta_key = '_link_url'
				INNER JOIN $wpdb->posts lp
					ON lp.ID = ls.post_id
				WHERE
					ls.meta_key = '_link_slug'
					AND ls.meta_value LIKE %s";

			$nested_slug = (string) $match_val[1].'/%';

			if ( $possible_links = $wpdb->get_col($wpdb->prepare($sql, $nested_slug)) ) {
				foreach ( $possible_links as $possible_link ) {
					// Try to match the full link against the URI
					if ( preg_match('#^'.$subdir[1].'/('.$possible_link.')([\?/].*?)?$#', $url, $match_val) ) {
						kickpress_slug_redirect($possible_link, $match_val[2]);
					}
				}
			}
		}
	}
}

function kickpress_api_handler( $api_params = array(), $query_params = null ) {
	global $kickpress_api, $kickpress_post_types;
	$base_url = '';
	$query_string = array();

	$first_index = 0;
	$base_url = $api_params[0];
	$base_params = explode( '/', trim( $base_url, '/' ) );

	// Remove leading and trailing slash
	$url_params = trim($api_params[1], '/');

	if ( ! empty($url_params) ) {
		$query_parts = explode('/', $url_params);

		if ( count($query_parts) ) {
			// See if we have to capture the post type from the query parts
			foreach( $query_parts as $qp_index => $qp_value ) {
				if ( 'post_type' == $qp_value && isset( $query_parts[$qp_index+1] ) ) {
					if ( $try_kickpress_api = kickpress_init_api( $query_parts[ $qp_index+1 ] ) )
						$kickpress_api = $try_kickpress_api;
					break;
				}
			}

			// Check whether or not the first parameter is in the filter array,
			// if it is not, we will evaluate if it is a valid post_name to
			// determine if it needs to be loaded as a post
			$first_index = 0;
			$first_param = $query_parts[0];

			// Determine if the return format is passed as part of an action
			// eg: toggle-term[category].json/featured/
			if ( ( $pos = strrpos( $first_param, '.' ) ) !== false ) {
				$format      = substr( $first_param, $pos + 1 );
				$first_param = substr( $first_param, 0, $pos );
			} elseif ( isset( $_REQUEST['format'] ) ) {
				if ( ( $pos = strpos( $_REQUEST['format'], '/' ) ) !== false )
					$format = substr( $_REQUEST['format'], $pos + 1 );
				else
					$format = $_REQUEST['format'];
			} else {
				$format = null;
			}

			// Determine if an action modifier is passed as part of an action
			// eg: toggle-term[category]/featured/
			//     toggle-term~category/featured/
			if ( ( $pos = strpos( $first_param, '[' ) ) !== false ) {
				define('KICKPRESS_ACTION_SLUG', '%s[%s]');
				$first_param_key = substr( $first_param, $pos + 1, -1 );
				$first_param     = substr( $first_param, 0, $pos );
			} elseif ( ( $pos = strpos( $first_param, '~' ) ) !== false ) {
				define('KICKPRESS_ACTION_SLUG', '%s~%s');
				$first_param_key = substr( $first_param, $pos + 1 );
				$first_param     = substr( $first_param, 0, $pos );
			} else {
				// Supply a default definition
				define('KICKPRESS_ACTION_SLUG', '%s[%s]');
				$first_param_key = null;
			}

			$other_params = array_slice( $query_parts, 1 );

			$id_index = array_search( 'post_id', $other_params );

			if ( $id_index !== false && $id_index + 1 < count( $other_params ) ) {
				$kickpress_api->params['id'] = $kickpress_api->params['post_id'] = $other_params[ $id_index + 1 ];
			}

			// See if the first parameter following the post type is an action or view
			// valid actions include: add, etc.
			if ( $action = $kickpress_api->is_valid_action( $first_param ) ) {
				$first_index = 1;
				$query_string['action'] = $action;

				if ( ! empty( $format ) )
					$query_string['format'] = $format;

				$kickpress_api->params['action'] = $action;
				$kickpress_api->params['format'] = $format;

				if ( ! empty( $first_param_key ) )
					$kickpress_api->params['action_key'] = $first_param_key;

				if ( count( $base_params ) >= 2 )
					$kickpress_api->params['post_name'] = $base_params[count( $base_params ) - 1];

				if ( ! empty( $other_params ) )
					$kickpress_api->params['extra'] = $other_params;
			} else {
				$view = false;
				if ( 'any' != $kickpress_api->params['post_type'] ) {
					$view = $kickpress_api->is_valid_view( $first_param );
				} else {
					$api_valid_views = array();
					foreach( $kickpress_post_types as $post_type_name=>$post_type_params ) {
						if ( 'any' != $post_type_name ) {
							if ( $tmp_api = kickpress_init_api( $post_type_name ) ) {
								if ( $view = $tmp_api->is_valid_view( $first_param ) )
									break;
							}
						}
					}
				}

				if ( ! empty ( $view ) ) {
					$first_index = 'search' == $view ? 0 : 1;

					$query_string['view'] = $view;

					$kickpress_api->params['view'] = $view;
					$kickpress_api->params['view_alias'] = $first_param;
					$kickpress_api->params['format'] = $format;

					if ( ! empty( $other_params ) )
						$kickpress_api->params['extra'] = $other_params;
				}
			}

			if ( count( $query_parts ) > $first_index ) {
				// Grab the API's filter array for this post type
				$filter_array = $kickpress_api->get_filter_array();

				foreach ( $query_parts as $query_part_index => $query_part_value ) {
					if ( $query_part_index < $first_index ) continue;

					// Overwrite the defaults if needed with the query string
					$eval_decoded = urldecode( $query_part_value );

					// evaluate if an array existes in the query string like: term[category]
					if ( ( $pos = strpos( $eval_decoded, '[' ) ) !== false )
						$eval_query_part = substr( $eval_decoded, 0, $pos );
					else
						$eval_query_part = $eval_decoded;

					// Rebuild the query string with key=value&key=value pairs
					if ( in_array( $eval_query_part, $filter_array ) &&
						isset( $query_parts[$query_part_index + 1] ) ) {
						if ( 'search' == $query_part_value ) {
							$_GET['s'] = $query_parts[$query_part_index + 1];
							$query_part_value = 's';
						}

						$query_string[$eval_decoded] = $query_parts[$query_part_index + 1];
					}
				}
			}

			// If this is an API call, check to see if it requires authentication
			if ( count($query_string) ) {
				// Check to see if this API call requires authentication
				/* if ( ! kickpress_authentication() ) {
					$kickpress_api->action_results[ 'messages' ][ 'note' ] = 'Sorry, you do not have the permissions to perform this action.';
					$kickpress_api->serialize_results();
					exit;

					//return new WP_Error('invalid_key', __('Invalid Key', 'kickpress'));
				} */

				// Merge the api parameters with the $_GET parameters and
				// remove the signature and token parameters
				$query_string_parts = kickpress_normalized_query_parameters( $query_params, $query_string, array('token', 'signature') );

				// Build the new query string from it's parts
				$new_query_string = implode('&', $query_string_parts);

				// Generate the new REQUEST URI
				// $new_request_uri = "{$base_url}/?{$new_query_string}";
				$new_request_uri = "{$base_url}/";

				// Set the query string server vars so that WordPress will know what to do
				$_SERVER['QUERY_STRING'] = $new_query_string;
				$_SERVER['REQUEST_URI']  = $new_request_uri;

				// Parse the query string and turn it into usable kickpress parameters
				parse_str($new_query_string, $query_string_array);
				$kickpress_api->params = array_merge($kickpress_api->params, $query_string_array);

				// Update the GET paremeters so that WordPress can process the information
				foreach ( $query_string_array as $eval_query_key => $eval_query_value ) {
					$_GET[$eval_query_key] = $eval_query_value;
				}
			}

			// kickpress_api::do_action will perform the action and redirect the HTTP request
			// Delay action until remote users can be authenticated
			if ( ! empty ( $kickpress_api->params['action'] ) ) {
				// Testing removal of wp_cron
				remove_action('init', 'wp_cron');

				add_action( 'wp', 'kickpress_do_action' );
			}
		}
	}
}

/**
 * After Wordpress parses the URL, grab the post name and insert it into the KickPress
 * Params. Wordpress is permalink aware and does a better job.
 * @param  Object $wp The Wordpress Object
 * @return
 */
function kickpress_parse_wp_request( $wp ) {
	global $kickpress_api;

	if( isset($wp->query_vars['name'] ) ) {
		$kickpress_api->params['post_name'] = $wp->query_vars['name'];
	}
}

function kickpress_do_action() {
	global $kickpress_api;
	$kickpress_api->do_action();
}

function kickpress_slug_redirect($slug, $param_str = '', $test_post_id = 0) {
	global $wpdb;

	// convert slug to number and see if it is a post id
	//$test_post_id = base_convert($slug, 36, 10);
	if ( $test_post_id == 0 ) {
		if ( $test_post_id = kickpress_slug_hooks($slug) )
			$is_slug_redirect = true;
		elseif ( !is_numeric($slug) && $test_post_id = kickpress_slug2id($slug) )
			$is_slug_redirect = true;
		else
			$is_slug_redirect = false;
	} else {
		$is_slug_redirect = false;
	}

	if ( $test_post_id == 0 || ! is_int($test_post_id) )
		return false;

/*
$short_url = base_convert($slug, 10, 36);
To convert short URL to it's original you will need:
$test_post_id = base_convert($slug, 36, 10);
*/

	$sql = "
		SELECT
			content_post.ID AS link_post_id,
			content_post.post_title AS link_title,
			content_link.meta_value AS link_url,
			content_source.meta_value AS bookmark
		FROM
			$wpdb->posts content_post
		LEFT JOIN $wpdb->postmeta content_link
			ON content_link.post_id = content_post.ID
			AND content_link.meta_key = '_content_link'
		LEFT JOIN $wpdb->postmeta content_source
			ON content_source.post_id = content_post.ID
			AND content_source.meta_key = '_content_source'
		WHERE
			content_post.ID = %d";

	if ( $link = $wpdb->get_row($wpdb->prepare($sql, $test_post_id)) ) {
		//kickpress_valid_slug_link($link);

		if ( $stats = get_post_meta($test_post_id, '_content_stats', true) )
			update_post_meta($test_post_id, '_content_stats', ++$stats);
		else
			add_post_meta($test_post_id, '_content_stats', 1, true);
		if( $content_link = get_post_meta($test_post_id, '_content_link') ){
			$site_link = $content_link[0];
		} else {
			$site_link = get_permalink($test_post_id);
		}

		header('HTTP/1.1 301 Moved Permanently');
		header('Location: '.$site_link);
		exit;
/*
		if ( isset($link->link_url) && ! empty($link->link_url) ) {
			kickpress_visit_link($slug, (array)$link);
		} elseif ( $is_slug_redirect ) {
			if ( $post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND ID = %d", $test_post_id)) ) {
				$site_link = get_permalink($post_id);

				header('HTTP/1.1 301 Moved Permanently');
				header('Location: '.$site_link);
				exit;
			}
		}
*/
	}

	$sql = "
		SELECT
			lp.ID AS link_post_id,
			lp.post_title AS link_title,
			lu.meta_value AS link_url,
			ls.meta_value AS link_slug,
			cs.meta_value AS bookmark
		FROM
			$wpdb->postmeta ls
		INNER JOIN $wpdb->postmeta lu
			ON lu.post_id = ls.post_id
			AND lu.meta_key = '_link_url'
		INNER JOIN $wpdb->posts lp
			ON lp.ID = ls.post_id
		LEFT JOIN $wpdb->postmeta cs
			ON cs.post_id = ls.post_id
			AND cs.meta_key = '_content_source'
		WHERE
			ls.meta_key = '_link_slug'
			AND ls.meta_value = '%s'";

	if ( $link = $wpdb->get_row($wpdb->prepare($sql, $slug)) ) {
		if ( isset($link->link_url) && ! empty($link->link_url) ) {
			$custom_get = $_GET;

			if ( isset($link->param_forwarding) and $link->param_forwarding == 'custom' ) {
				// Get the structure matches (param names)
				preg_match_all('#%(.*?)%#', $link->param_struct, $struct_matches);

				// Get the uri matches (param values)
				$match_str = preg_replace('#%.*?%#','(.*?)',$link->param_struct);
				$match_str = '#'.preg_replace('#\(\.\*\?\)$#','(.*)',$match_str).'#'; // replace the last one with a greedy operator

				preg_match($match_str, $param_str, $uri_matches);

				for ( $i = 0; $i < count($struct_matches[1]); $i++ )
					$custom_get[$struct_matches[1][$i]] = $uri_matches[$i+1];
			}

			// Reformat Parameters
			$param_string = '';

			if ( isset($link->param_forwarding) and $link->param_forwarding and isset($custom_get) and count($custom_get) >= 1 ) {
				$first_param = true;
				foreach ( $custom_get as $key => $value ) {
					if ( $first_param ) {
						$param_string = ( preg_match("#\?#", $link->_link_url) ? '&' : '?');
						$first_param = false;
					} else {
						$param_string .= '&';
					}

					$param_string .= esc_attr($key).'='.esc_attr($value);
				}
			}

			//Redirect
			if ( isset($link->nofollow) and $link->nofollow )
				header('X-Robots-Tag: noindex, nofollow');

			// If we're using the link shortner bar then don't redirect
			// otherwise load kickpress_link_shortner_bar
			if ( isset($link->bookmark) ) {
				if ( ! empty($link->link_url) ) {
					kickpress_visit_link($slug, (array)$link, $param_string);
				}
			} else {
				if ( (integer) $link->redirect_type == 301 ) {
					header('HTTP/1.1 301 Moved Permanently');
				} elseif ( (integer) $link->redirect_type == 307 ) {
					if ( $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0' ) {
						header('HTTP/1.1 302 Found');
					} else {
						header('HTTP/1.1 307 Temporary Redirect');
					}
				}

				header('Location: '.$link->link_url.$param_string);
			}
			exit;
		}
	}
}

function kickpress_visit_link($slug, $link=array(), $param_string='') {
	global $wpdb;

	if ( ! empty($link['link_post_id']) ) {
		if ( $stats = get_post_meta($link['link_post_id'], '_content_stats', true) )
			update_post_meta($link['link_post_id'], '_content_stats', ++$stats);
		else
			add_post_meta($link['link_post_id'], '_content_stats', 1, true);
	}

	$sql = "
		SELECT
			$wpdb->links.link_id
		FROM
			$wpdb->links
		WHERE
			$wpdb->links.link_visible = 'Y'
			AND $wpdb->links.link_rel = 'me'
			AND $wpdb->links.link_name = %s";

	$link_shortner_bar = $wpdb->get_var($wpdb->prepare($sql, $link['bookmark']));

	if ( kickpress_boolean((string) $link_shortner_bar, false) ) {
		$site_url = (string)$link['link_url'].$param_string;
		$blogurl = ((get_option('home')) ? get_option('home') : get_option('siteurl'));

		if ( ! empty($link['link_slug']) )
			$link_slug = $blogurl.'/'.(string) $link['link_slug'];
		else
			$link_slug = $site_url;

		if ( ! empty($link['link_title']) )
			$link_title = (string) $link['link_title'];
		else
			$link_title = (string) $link['link_url'];

		if ( file_exists(TEMPLATEPATH.'/link_shortner_bar.php') ) {
			require_once(TEMPLATEPATH.'/link_shortner_bar.php');
		}
	}
	else
	{
		header('HTTP/1.1 301 Moved Permanently');
		header('Location: '.(string) $link['link_url']);
	}
	exit;
}

?>