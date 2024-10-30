<?php

/**
 * This file handles all custom post types
 *
 * Post type relationships moved to kickpress-relationships.php
 */

function kickpress_init_post_types() {
	global $wpdb, $kickpress_post_types, $kickpress_builtin_post_types, $kickpress_plugin_options;
/*
	register_post_type( 'post', array(
		'labels' => array(
			'name_admin_bar' => _x( 'Post', 'add new on admin bar' ),
		),
		'public'           => true,
		'show_ui'          => false,
		'show_in_menu'     => false,
		'_builtin'         => false,
		'_edit_link'       => 'post.php?post=%d',
		'capability_type'  => 'post',
		'map_meta_cap'     => true,
		'hierarchical'     => false,
		'rewrite'          => array( 'slug' => 'post', 'with_front' => false ),
		'query_var'        => false,
		'has_archive'      => true,
		'supports'         => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'post-formats' ),
	) );
*/
	// Start "Post Types"
	register_post_type( 'custom-post-types', array(
		'label'                => __( 'Post Types', 'kickpress' ),
		'public'               => true,
		'show_ui'              => true,
		'exclude_from_search'  => true,
		'register_meta_box_cb' => 'kickpress_init_admin_meta_boxes'
	) );

	// Custom Post Type Categories determine what categories show up in the
	// add/edit screens of the custom post entries
	$cat_slug = 'custom-post-type-categories';

	$show_in_menu = current_user_can( 'manage_options' );

	register_taxonomy( $cat_slug, 'custom-post-types', array(
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => $show_in_menu,
			'taxonomies'         => array( 'category' ),
			'hierarchical'       => true,
			'label'              => __( 'Post Type Categories', 'kickpress' ),
			'query_var'          => $cat_slug,
			'rewrite'            => array( 'slug' => $cat_slug )
	) );
	// End "Post Types"

	global $wp_post_types, $wp_post_statuses;

	if ( 'enable' == $kickpress_plugin_options['future_comments']['value']  &&
		'wp-comments-post.php' == basename( $_SERVER['SCRIPT_NAME'] ) )
		$wp_post_statuses['future']->public = true;

	foreach ( array( 'post', 'page' ) as $post_type ) {
		if ( $api = kickpress_init_api( $post_type ) ) {
			$valid_actions = $api->get_valid_actions();

			$post_type_object =& $wp_post_types[ $post_type ];

			$caps = get_object_vars( $post_type_object->cap );

			foreach( $valid_actions as $action => $action_params ) {
				$action_cap = $action_params['capability'];

				if ( ! empty( $action_cap ) && ! in_array( $action_cap, array_keys( $caps ) ) )
					$caps[$action_cap] = $action_cap;
			}

			$post_type_object->cap = (object) $caps;
		}
	}

	$valid_post_types = array();

	if(count($kickpress_post_types)){
		foreach ( $kickpress_post_types as $key=>$post_type ) {
			if ( ! isset($post_type['post_type']) || in_array($post_type['post_type'], $kickpress_builtin_post_types) )
				continue;
			else
				$this_post_type = (string) $post_type['post_type'];

			$valid_post_types[] = $this_post_type;

			if ( isset($post_type['post_type_title']) )
				$post_type_title = $post_type['post_type_title'];
			else
				$post_type_title = ucwords(str_replace(array('-','_'), ' ', $this_post_type));

			if ( $api = kickpress_init_api($this_post_type, $post_type) ) {
				//$api->init_post_type_options();

				$user_cap = array(
					'read_post'              => 'meta_read_' . $this_post_type,
					'edit_post'              => 'meta_edit_' . $this_post_type,
					'create_post'            => 'meta_create_' . $this_post_type,
					'delete_post'            => 'meta_delete_' . $this_post_type,
					'read_private_posts'     => 'read_private_' . $this_post_type,
					'edit_posts'             => 'edit_' . $this_post_type,
					'edit_private_posts'     => 'edit_private_' . $this_post_type,
					'edit_published_posts'   => 'edit_published_' . $this_post_type,
					'edit_others_posts'      => 'edit_others_' . $this_post_type,
					'delete_posts'           => 'delete_' . $this_post_type,
					'delete_private_posts'   => 'delete_private_' . $this_post_type,
					'delete_published_posts' => 'delete_published_' . $this_post_type,
					'delete_others_posts'    => 'delete_others_' . $this_post_type,
					'publish_posts'          => 'publish_' . $this_post_type
				);

				// Assign custom capabilities to a filter
				$valid_actions = $api->get_valid_actions();

				foreach( $valid_actions as $action => $action_params ) {
					$action_cap = $action_params['capability'];

					if ( ! in_array( $action_cap, array_keys( $user_cap ) ) ) {
						$user_cap[$action_cap] = $action_cap;
					}
				}

				// Use: get_post_type_labels( $post_type_object );
				$labels = array( 'name' => _x( $post_type_title, 'post type general name' ) );

				if ( ! empty( $api->params['singular_name'] ) )
					$labels['singular_name'] = _x( $api->params['singular_name'], 'post type singular name' );

				if ( ! empty( $api->params['add_new'] ) )
					$labels['add_new'] = _x( $api->params['add_new'], $this_post_type );

				if ( ! empty( $api->params['add_new_item'] ) )
					$labels['add_new_item'] = __( $api->params['add_new_item'], 'kickpress' );

				if ( ! empty( $api->params['new_item'] ) )
					$labels['new_item'] = __( $api->params['new_item'], 'kickpress' );

				if ( ! empty( $api->params['edit_item'] ) )
					$labels['edit_item'] = __( $api->params['edit_item'], 'kickpress' );

				if ( ! empty( $api->params['view_item'] ) )
					$labels['view_item'] = __( $api->params['view_item'], 'kickpress' );

				if ( ! empty( $api->params['all_items'] ) )
					$labels['all_items'] = __( $api->params['all_items'], 'kickpress' );

				if ( ! empty( $api->params['search_items'] ) )
					$labels['search_items'] = __( $api->params['search_items'], 'kickpress' );

				if ( ! empty( $api->params['not_found'] ) )
					$labels['not_found'] = __( $api->params['not_found'], 'kickpress' );

				if ( ! empty( $api->params['not_found_in_trash'] ) )
					$labels['not_found_in_trash'] = __( $api->params['not_found_in_trash'], 'kickpress' );

				if ( ! empty( $api->params['menu_name'] ) )
					$labels['menu_name'] = __( $api->params['menu_name'], 'kickpress' );

				//echo '<pre>' . str_repeat( PHP_EOL, 3 ) . var_export( $labels, true ) . '</pre>';

				$post_type_args = array(
					'taxonomies'           => array('post_tag', 'category'),
					'labels'               => $labels,
					/*'label'                => __($post_type_title, 'kickpress'),*/
					/*'singular_label'     => __('Property', 'kickpress'),*/
					'public'               => kickpress_boolean($api->params['public'], true),
					'publicly_queryable'   => kickpress_boolean($api->params['publicly_queryable'], true),
					'exclude_from_search'  => kickpress_boolean($api->params['exclude_from_search'], false),
					'show_ui'              => kickpress_boolean($api->params['show_ui'], true),
					'show_in_menu'         => kickpress_boolean($api->params['show_in_menu'], true),
					'hierarchical'         => kickpress_boolean($api->params['hierarchical'], false),
					'_builtin'             => kickpress_boolean($api->params['builtin'], false),
					'has_archive'          => true,
					'_edit_link'           => 'post.php?post=%d',
					'register_meta_box_cb' => 'kickpress_init_meta_boxes',
					'rewrite'              => array(
						'slug'                 => $this_post_type,
						'with_front'           => false
					),
					'map_meta_cap'         => true,
					'capabilities'         => $user_cap,
					'capability_type'      => $this_post_type,
					'supports'             => array('title', 'editor', 'thumbnail', 'excerpt', 'trackbacks', 'custom-fields', 'comments', 'author', 'revisions', 'post-formats')
				);

				register_post_type( $this_post_type, $post_type_args );

				if ( class_exists( 'WP_Roles' ) ) {
					global $wp_roles, $wp_post_types;

					if ( is_null( $wp_roles ) ) $wp_roles = new WP_Roles();

					$role = $wp_roles->role_objects['administrator'];
					$cap  = $wp_post_types[$this_post_type]->cap;

					if ( ! $role->has_cap( $cap->edit_posts ) ) {
						foreach ( $wp_roles->role_objects as $role_name => &$role ) {
							foreach ( (array) $cap as $cap_key => $cap_value ) {
								if ( $role->has_cap( $cap_key ) ) {
									$role->add_cap( $cap_value );
								}
							}

							$role->add_cap( 'create_' . $this_post_type, $role->has_cap( 'edit_posts' ) );
						}
					}
				}

				if ( isset($api->params['terms']) ) {
					foreach ( $api->params['terms'] as $key=>$term ) {
						if ( ! empty($term->slug) && 'category' != trim($term->slug) ) {
							//(string) $post_type['post_type_title']
							register_taxonomy((string) $term->slug, $this_post_type,
								array(
									'hierarchical' => true,
									'label'        => __((string) $term->name . ' Category', 'kickpress'),
									'query_var'    => (string) $term->slug,
									'rewrite'      => array('slug' => (string) $term->slug) // 'term['.(string)$term->slug.']'
								)
							);
						}
					}
					/*
					// User bookmarks
					if ( is_user_logged_in() ) {
						global $user_ID;
						$user_term_slug = 'user-'.$user_ID.'-'.$this_post_type;

						register_taxonomy($user_term_slug, $this_post_type,
							array(
								'hierarchical' => true,
								'label'        => __('Manage Your '.$post_type_title.' Lists', 'kickpress'),
								'query_var'    => $user_term_slug,
								'rewrite'      => array('slug' => $user_term_slug)
							)
						);
					}
					*/
				}

				$cat_slug = $this_post_type.'-category';
				register_taxonomy($cat_slug, $this_post_type,
					array(
						'hierarchical' => true,
						'label'        => __($post_type_title . ' Category', 'kickpress'),
						'query_var'    => $cat_slug,
						'rewrite'      => array('slug' => $cat_slug)
					)
				);
			}
		}
	}

	if ( count($valid_post_types) ) {
		register_taxonomy('language', $valid_post_types,
			array(
				'hierarchical'      => true,
				'labels'            => array(
					'name'              => __( 'Languages', 'kickpress' ),
					'singular_name'     => __( 'Language', 'kickpress' ),
					'search_items'      => __( 'Search Languages', 'kickpress' ),
					'all_items'         => __( 'All Languages', 'kickpress' ),
					'parent_item'       => __( 'Parent Language', 'kickpress' ),
					'parent_item_colon' => __( 'Parent Language:', 'kickpress' ),
					'edit_item'         => __( 'Edit Language', 'kickpress' ),
					'update_item'       => __( 'Update Language', 'kickpress' ),
					'add_new_item'      => __( 'Add New Language', 'kickpress' ),
					'new_item_name'     => __( 'New Language Name', 'kickpress' ),
				),
				'show_ui'           => true,
				'query_var'         => 'language',
				'rewrite'           => array('slug' => 'language')
			)
		);

		register_taxonomy('country', $valid_post_types,
			array(
				'hierarchical'      => true,
				'labels' => array(
					'name'              => __( 'Countries', 'kickpress' ),
					'singular_name'     => __( 'Country', 'kickpress' ),
					'search_items'      => __( 'Search Countries', 'kickpress' ),
					'all_items'         => __( 'All Countries', 'kickpress' ),
					'parent_item'       => __( 'Parent Country', 'kickpress' ),
					'parent_item_colon' => __( 'Parent Country:', 'kickpress' ),
					'edit_item'         => __( 'Edit Country', 'kickpress' ),
					'update_item'       => __( 'Update Country', 'kickpress' ),
					'add_new_item'      => __( 'Add New Country', 'kickpress' ),
					'new_item_name'     => __( 'New Country Name', 'kickpress' ),
				),
				'query_var'         => 'country',
				'rewrite'           => array('slug' => 'country')
			)
		);
	}
	// End kickpress_init_post_types
}

function kickpress_set_post_types( &$query ) {
	global $kickpress_api, $kickpress_post_types, $wp_query;

	if ( $query != $GLOBALS['wp_the_query'] ) return;

	$post_type = kickpress_get_post_type( get_query_var( 'post_type' ) );

	if ( is_array($post_type) )
		return;

	// Wordpress does not limit the 'pre_get_posts' filter to post queries, it also applies to nav_menu_item
	if ( in_array($post_type, array('page','nav_menu_item','attachment')) )
		return;

	if ( ! is_object($kickpress_api) || empty($kickpress_api->params) ) {
		$init_post_type = ( $post_type );
		$kickpress_api = kickpress_init_api( $init_post_type );
	}

	if ( ! empty($kickpress_api->params) )
		extract($kickpress_api->params);

/*
'p' => 27 - use the post ID to show that post
'name' => 'about-my-life' - query for a particular post that has this Post Slug
'page_id' => 7 - query for just Page ID 7
'pagename' => 'about' - note that this is not the page's title, but the page's path
'posts_per_page' => 1 - use 'posts_per_page' => 3 to show 3 posts. Use 'posts_per_page' => -1 to show all posts
'post__in' => array(5,12,2,14,7) - inclusion, lets you specify the post IDs to retrieve
'post__not_in' => array(6,2,8) - exclusion, lets you specify the post IDs NOT to retrieve
'post_type' => 'page' - returns Pages; defaults to value of post; can be any, attachment, page, post, or revision. any retrieves any type except revisions. Also can designate custom post types (e.g. movies).
'post_status' => 'publish' - returns publish works. Also could use pending, draft, future, private, trash. For inherit see get_children. Status of trash added with Version 2.9.
'post_parent' => 93 - return just the child Pages of Page 93.
'caller_get_posts' => 1
'author' => 1
'orderby' => title
'order' => ASC
'cat' => 22
'year' => $current_year
'monthnum' => $current_month

meta_compare= - operator to test the meta_value=, default is '=', with other possible values of '!=', '>', '>=', '<', or '<='

query_posts('meta_key=color&meta_value=blue');
query_posts('meta_key=miles&meta_compare=<=&meta_value=22');
query_posts('post_type=any&meta_key=color&meta_compare=!=&meta_value=blue');
query_posts('post_type=page&meta_value=green');

add_filter:
	posts_search
	posts_where
	posts_join
	posts_where_paged
	posts_groupby
	posts_join_paged
	posts_orderby
	posts_distinct
	post_limits
	posts_fields
	posts_request
	posts_results

*/
	if ( isset($post_id) && ! isset($id) ) {
		$id = $post_id;
		$is_list = false;
	}

	// Determine is this is a repeating list element of a single post view
	if ( isset($id) ) {
		$query->set('p', $id);
	} elseif ( ! isset($is_list) ) {
		if ( is_archive() || is_search() || is_paged() || is_home() )
			$is_list = true;
		else
			$is_list = false;

		$kickpress_api->params['is_list'] = $is_list;
	}

	// Tags, Categories, Authors, and Search need to include all valid post types
	// Make sure that 'suppress_filters' is not set as to avoid modifying the
	// query on post types like 'nav_menu_item' (otherwise the custom menus break)
	if ( $is_list && empty( $query->query_vars['suppress_filters'] ) ) {
		// http://wordpress.org/support/topic/custom-post-type-tagscategories-archive-page

		// Set the post types
		if ( '' == $query->get( 'post_type' ) )
			$query->set( 'post_type', $post_type );

		// Catch wp_query params
		$wp_query_params = array(
			'posts_per_page' => 'posts_per_page',
			'year'           => 'year',
			'month'          => 'monthnum',
			'monthnum'       => 'monthnum',
			'day'            => 'day',
			'category'       => 'category_name',
			'category_name'  => 'category_name',
			'cat'            => 'cat',
			'term'           => 'term',
			'tag'            => 'tag'
		);

		foreach ( $wp_query_params as $query_key=>$query_param ) {
			if ( isset($kickpress_api->params[$query_key]) ) {
				$query->set($query_param, $kickpress_api->params[$query_key]);
			}
		}
/*
		// Set the posts per page
		if ( ! isset($id) && ! isset($posts_per_page) ) {
			if ( isset($_posts_per_page) )
				$posts_per_page = $_posts_per_page;
			else
				$posts_per_page = 10;
		}
		// Might cause problems
		$query->set('posts_per_page', $posts_per_page);

		// Set the current page
		if ( get_query_var('paged') )
			$paged = get_query_var('paged');
		elseif ( get_query_var('page') )
			$paged = get_query_var('page');
		elseif ( isset($page) )
			$paged = $page;
		else
			$paged = 1;
		$kickpress_api->params['page'] = $paged;

		//$paged = isset($page)?($page-1):0;

		// Set the offset
		//$query->set('offset', (floor(($paged-1) * $posts_per_page)));
		// End might cause problems
*/

		// Set the category
		if ( isset($category) ) {
			if ( $cat_obj = get_category_by_slug((string) $category) ) {
				$wp_query->is_category = true;

				$query->set('cat', $cat_obj->cat_ID);
				$query->set('category_name', $cat_obj->slug);
			}
		}

		// Set the tag
		if ( isset($tag) ) {
			$wp_query->is_tag = true;
			$query->set('tag', $tag);
		}

		// Set the search term
		if ( isset($search) ) {
			$wp_query->is_search = true;
			$query->set('s', $search);
		} elseif ( isset($_GET['s']) ) {
			$search = esc_attr( $_GET['s'] );
			$kickpress_api->params['search'] = $search;
			$query->set('s', $search);
		}
	}

	return $query;
}

function kickpress_init_api( $post_type=null, $args=array() ) {
	global $kickpress_post_types;

	if ( empty($post_type) )
		$post_type = get_query_var( 'post_type' );

	if ( empty($post_type) || is_array($post_type) )
		return;

	$post_type = str_replace(array('%20',' '), "_", $post_type);

	if ( in_array($post_type, array('page', 'nav_menu_item', 'attachment')) )
		return;

	if ( ! isset($kickpress_post_types[$post_type]['api']) ) {
		if ( ! count($args) ) {
			$args['post_type'] = $post_type;
		}

		$filename = 'class-'.str_replace('_', '-', $post_type).'.php';
		$classname = 'kickpress_'.str_replace('-', '_', $post_type);

		//if ( 'media' == $post_type )
		//	require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-media-functions.php');

		// Look in the child theme, parent theme, and plugin
		if ( file_exists(STYLESHEETPATH.'/modules/'.$filename) ) {
			require_once(STYLESHEETPATH.'/modules/'.$filename);
		} elseif ( file_exists(TEMPLATEPATH.'/modules/'.$filename) ) {
			require_once(TEMPLATEPATH.'/modules/'.$filename);
		} elseif ( file_exists(WP_PLUGIN_DIR.'/kickpress/modules/'.$filename) ) {
			require_once(WP_PLUGIN_DIR.'/kickpress/modules/'.$filename);
		}

		if ( ! class_exists($classname) && ! empty($classname) ) {
			// make a dynamic class that extends the API on the fly
			$dynamic_class = sprintf('
				class %1$s extends kickpress_api {
					public function __construct($params) {
						parent::__construct($params);
					}
				}',
				$classname
			);

			eval($dynamic_class);
		}

		$kickpress_post_types[$post_type]['api'] = new $classname($args);
	} else {
		if ( is_array($args) && count($args) ) {
			//$kickpress_post_types[$post_type]['api']->params = $args;
			$kickpress_post_types[$post_type]['api']->params = array_merge($kickpress_post_types[$post_type]['api']->params, $args);
		}
	}

	return $kickpress_post_types[$post_type]['api'];
}

function kickpress_init_admin_meta_boxes($args=array()) {
	add_meta_box('kickpressadminmeta', __('Attributes', 'kickpress'), 'kickpress_set_admin_meta_boxes', $args->post_type, 'normal', 'high');
}

function kickpress_init_meta_boxes($args=array()) {
	// Adds an "Attributes" section to the edit screen
	add_meta_box('kickpresscustommeta', __('Attributes', 'kickpress'), 'kickpress_set_meta_boxes', $args->post_type, 'normal', 'high');
}

function kickpress_init_builtin_meta_boxes($args=array()) {
	// Adds an "Attributes" section to the edit screen
	add_meta_box('kickpresscustommeta', __('Attributes', 'kickpress'), 'kickpress_set_meta_boxes', 'post', 'normal', 'high');
	add_meta_box('kickpresscustommeta', __('Attributes', 'kickpress'), 'kickpress_set_meta_boxes', 'page', 'normal', 'high');
	//add_meta_box('kickpresscustomusermeta', __('Attributes', 'kickpress'), 'kickpress_set_user_meta_boxes', 'link', 'normal', 'high');
}

function kickpress_get_form_data( $post, $post_data = array() ) {
	if ( $api = kickpress_init_api( $post->post_type ) ) {
		// fetch the cusotm fields
		if ( 'custom-post-types' == $post->post_type ) {
			if ( ! empty( $post->post_name ) && $child_api = kickpress_init_api( $post->post_name ) ) {
				$form_data = $child_api->get_post_type_options(true);
			} else {
				$form_data = $api->get_post_type_options(true);
			}
		} else {
			$form_data = $api->get_custom_fields(true);
		}

		foreach ( $form_data as $field_name=>$field_meta ) {
			if ( isset( $field_meta['name'] ) ) {
				if ( isset( $post->{$field_meta['name']} ) ) {
					$option_value = $post->{$field_meta['name']};
				} elseif ( 'post_format' == $field_name ) {
					if ( ! empty( $post->ID ) )
						$option_value = get_post_format( $post->ID );
				} elseif ( '_sticky' == $field_name ) {
					$stickies = get_option('sticky_posts');
					$option_value = in_array( $post->ID, $stickies ) ? 'enable' : 'disable';
				} else {
					$option_value = get_post_meta( $post->ID, $field_meta['name'], true );
				}


				if ( isset( $post_data[$field_name] ) ) {
					// If this is a post back, use the submitted form data.
					$form_data[$field_name]['value'] = $post_data[$field_name];
				} elseif ( '' == $option_value && ! empty( $field_meta['default'] ) ) {
					// If this is a new record, use the default value.
					$form_data[$field_name]['value'] = $field_meta['default'];
				} else {
					// If this is an existing record use the database value.
					$form_data[$field_name]['value'] = $option_value;
				}
			}
		}
		return $form_data;
	} else {
		return array();
	}
}

function kickpress_load_form_data( $post ) {
	$post_id   = (string) $post->ID;
	$post_type = (string) $post->post_type;

	if ( $api = kickpress_init_api( $post->post_type ) ) {
		// fetch the form data
		$form_data = kickpress_get_form_data( $post );

		printf('
			%1$s
			<table class="form-table">
				%2$s
			</table>
			%3$s',
			wp_nonce_field( plugin_basename( __FILE__ ), 'kickpress_meta_fields_wpnonce', false, false ),
			$api->get_form_fields( $form_data, $post, true ),
			$api->form_footer()
		);
	}
}

function kickpress_set_admin_meta_boxes( $post ) {
	kickpress_load_form_data( $post );
}

function kickpress_set_meta_boxes($post) {
	kickpress_load_form_data( $post );
}

function kickpress_save_meta_fields($post_id) {
	global $post;
	$post_type = (string) $post->post_type;

	// Verify this came from the our screen and with proper authorization
	if ( ! wp_verify_nonce($_POST['kickpress_meta_fields_wpnonce'], plugin_basename(__FILE__)) )
		return $post_id;

	// Check permissions
	if ( ! current_user_can('edit_post', $post_id) )
		return $post_id;

	if ( 'page' == $_POST['post_type'] ) {
		if ( ! current_user_can('edit_page', $post_id) )
			return $post_id;
	} else {
		if ( ! current_user_can('edit_post', $post_id) )
			return $post_id;
	}

	if ( isset($_POST['data'][$post_type][$post_id]) ) {
		$post_data = $_POST['data'][$post_type][$post_id];
		kickpress_process_custom_fields($post, $post_data);

		if ( $api = kickpress_init_api( $post_type ) ) {
			$relations = $api->get_post_type_relations();

			foreach ( (array) $relations as $relation ) {
				$meta_name = $relation['name'];

				if ( isset( $post_data[$meta_name] ) && '_thumbnail_id' != $meta_name )
					update_post_meta( $post_id, $meta_name, (int) $post_data[$meta_name] );
			}
		}
	}
}

function kickpress_flush_rewrites () {
	global $post;

	if ( is_object( $post ) ) {
		$post_type_slug = (string) $post->post_name;

		if (
			'custom-post-types' == (string) $post->post_type
			&& current_user_can( 'manage_options' )
			&& 'edit' == esc_attr( $_GET['action'] )
			&& ! empty( $_GET['message'] )
		) {
			// Force permalinks to be rewritten for every new post type
			flush_rewrite_rules( );
		}
	}
}

function kickpress_process_custom_fields( $post, $post_data ) {
	// When the post is saved, saves our custom data
	$skip_post_vars = array('ID','post_author','post_date','post_date_gmt','post_content','post_title','post_category','post_excerpt','post_status','comment_status','ping_status','post_password','post_name','to_ping','pinged','post_modified','post_modified_gmt','post_content_filtered','post_parent','guid','menu_order','post_type','post_mime_type','comment_count','tags_input','categories_input', '_thumbnail_id');
	$post_type = (string) $post->post_type;
	$post_id = (integer) $post->ID;
	$updates = array();

	// Verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
		return $post_id;

	if ( $api = kickpress_init_api($post_type) ) {
		$form_data = kickpress_get_form_data($post);
		foreach ( $post_data as $meta_name=>$meta_value ) {
			if ( isset($meta_value) && $meta_value != '' )
				$api->params[$meta_name] = $post_data[$meta_name];
		}

		// hook into local update specific to post_type
		$post_data = $api->update_meta_fields($post, $post_data, $form_data);

		// Add the custom fields with item attributes
		foreach ( $form_data as $key=>$field ) {
			if ( !isset( $field['name'] ) )
				continue;

			$field_name = $field['name'];
			if ( in_array($field_name, $skip_post_vars) )
				continue;

			if ( !isset( $field['type'] ) || empty( $field['type'] ) )
				$field['type'] = 'text';

			if ( 'checkbox' == $field['type'] ) {
				if ( isset($post_data[$field_name]) ) {
					if ( '_sticky' == $field_name )
						stick_post(	$post_id	);
					else
						update_post_meta($post_id, $field_name, 'enable');
				} else {
					if ( '_sticky' == $field_name )
						unstick_post( $post_id );
					else
						update_post_meta($post_id, $field_name, 'disable');
				}
			} elseif ( 'post_type' == $field['type'] ) {
				$data = $post_data[$field_name];
				if ( $post_type != $data ) {
					$sql = "UPDATE SET $wpdb->posts.post_type = $data";
				}
			} elseif ( 'field_sort_order' == $field['type'] ) {
				$sort_order = array();

				if ( isset($post_data[$field_name]) ) {
					foreach ( $post_data[$field_name] as $sort_key=>$sort_value ) {
						if ( isset($sort_value) && $sort_value != '' )
							$sort_order[] = $sort_value;
					}
				}

				if ( count($sort_order) == 1 )
					$data = $sort_order[0];
				elseif ( count($sort_order) > 1 )
					$data = implode(',', $sort_order);

				if ( get_post_meta($post_id, $field_name) == '' )
					add_post_meta($post_id, $field_name, $data, true);
				elseif ( $data != get_post_meta($post_id, $field_name, true) )
					update_post_meta($post_id, $field_name, $data);
				elseif ( $data == "" )
					delete_post_meta($post_id, $field_name, get_post_meta($post_id, $field_name, true));
			} elseif ( 'term_search_options' == $field['type'] ) {
				if ( $post_api = kickpress_init_api((string) $post->post_name) ) {
					foreach ( $post_api->params['terms'] as $key=>$term ) {
						$term_filter_id = '_term_filter_'.str_replace('-', '_', $term->slug);
						$term_filter_name = 'term['.$term->slug.']';

						if ( ! $term_filter_value = get_post_meta($post_id, $term_filter_id, true) )
							$term_filter_value = '';

						$data = $_POST[$term_filter_id];

						if ( get_post_meta($post_id, $term_filter_id) == "" )
							add_post_meta($post_id, $term_filter_id, urldecode($data), true);
						elseif ( $data != get_post_meta($post_id, $term_filter_id, true) )
							update_post_meta($post_id, $term_filter_id, urldecode($data));
						elseif ( $data == '' )
							delete_post_meta($post_id, $term_filter_id, get_post_meta($post_id, $term_filter_id, true));
					}
				}
			} else {
				if ( 'time' == $field['type'] ) {
					$time = sprintf("%s:%s:00",
						$post_data["hour".$field_name],
						$post_data["minute".$field_name]
					);
				} elseif ( 'date' == $field['type'] && 'repeat_until' == $field_name && '0000-00-00' == $post_data['repeat_until'] ) {
					$data = $field['default'];
				} elseif ( 'timestamp' == $field['type'] && 'created_on' == $field_name ) {
					$data = "NOW()";
				} else {
					$data = isset( $post_data[$field_name] ) ? $post_data[$field_name] : null;
				}

				if ( isset( $post_data[$field_name] ) ) {
					if ( '' == get_post_meta($post_id, $field_name) )
						add_post_meta($post_id, $field_name, $data, true);
					elseif ( $data != get_post_meta($post_id, $field_name, true) )
						update_post_meta($post_id, $field_name, $data);
					elseif ( empty( $data ) )
						delete_post_meta($post_id, $field_name, get_post_meta($post_id, $field_name, true));
				}
			}
		}
	}
}

?>