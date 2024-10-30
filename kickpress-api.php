<?php
/**
 *
 * kickpress-api class
 * This file holds all of the public API actions that are callable throught the URL
 *
 */

class kickpress_api {
	private $_handler = null;
	public $params;
	public $elements;
	public $post_id;
	public $validation;

	// action_results
	public $action_results = array(
		'status'   => '',
		'messages' => array(),
		'data'     => array()
	);

	/**
	 * A full list of allowable API actions for any particular post type
	 */
	protected $_valid_actions = array(
		'login'          => array(
			'slug'             => 'login',
			'method'           => 'login',
			'callback'         => '',
			'label'            => 'Login',
			'capability'       => ''
		),
		'oauth'          => array(
			'slug'             => 'oauth',
			'method'           => 'oauth',
			'callback'         => '',
			'label'            => 'OAuth 1.0a',
			'capability'       => ''
		),
		'register'       => array(
			'slug'             => 'register',
			'method'           => 'register',
			'callback'         => '',
			'label'            => 'Register User',
			'capability'       => ''
		),
		'save'           => array(
			'slug'             => 'save',
			'method'           => 'save',
			'callback'         => 'form',
			'label'            => 'Save',
			'capability'       => 'edit_posts'
		),
		'delete'         => array(
			'slug'             => 'delete',
			'method'           => 'delete',
			'callback'         => 'archive',
			'label'            => 'Delete',
			'capability'       => 'delete_posts'
		),
		'data'           => array(
			'slug'             => 'data',
			'method'           => 'data',
			'callback'         => '',
			'label'            => 'Data',
			'capability'       => 'edit_posts'
		),
		'import'         => array(
			'slug'             => 'import',
			'method'           => 'import',
			'callback'         => '',
			'label'            => 'Import',
			'capability'       => 'edit_posts'
		),
		'export'         => array(
			'slug'             => 'export',
			'method'           => 'export',
			'callback'         => '',
			'label'            => 'Export',
			'capability'       => 'read'
		),
		'export-comments' => array(
			'slug'             => 'export-comments',
			'method'           => 'export_comments',
			'callback'         => '',
			'label'            => 'Export Comments',
			'capability'       => 'read'
		),
		'import-comments' => array(
			'slug'             => 'import-comments',
			'method'           => 'import_comments',
			'callback'         => '',
			'label'            => 'Import Comments',
			'capability'       => 'read'
		),
		'add-terms'      => array(
			'slug'             => 'add-terms',
			'method'           => 'add_terms',
			'callback'         => '',
			'default_format'   => 'json',
			'label'            => 'Add Term',
			'capability'       => 'edit_terms'
		),
		'remove-terms'   => array(
			'slug'             => 'remove-terms',
			'method'           => 'remove_terms',
			'callback'         => '',
			'default_format'   => 'json',
			'label'            => 'Remove Term',
			'capability'       => 'edit_terms'
		),
		'toggle-term'      => array(
			'slug'             => 'toggle-term',
			'method'           => 'toggle_term',
			'callback'         => '',
			'default_format'   => 'json',
			'label'            => 'Toggle Term',
			'capability'       => 'edit_terms'
		),
		'add-bookmark'     => array(
			'slug'             => 'add-bookmark',
			'method'           => 'add_bookmark',
			'callback'         => '',
			'default_format'   => 'json',
			'label'            => 'Add Bookmark',
			'capability'       => 'edit_bookmarks'
		),
		'remove-bookmark'  => array(
			'slug'             => 'remove-bookmark',
			'method'           => 'remove_bookmark',
			'callback'         => '',
			'default_format'   => 'json',
			'label'            => 'Remove Bookmark',
			'capability'       => 'edit_bookmarks'
		),
		'toggle-bookmark'  => array(
			'slug'             => 'toggle-bookmark',
			'method'           => 'toggle_bookmark',
			'callback'         => '',
			'default_format'   => 'json',
			'label'            => 'Toggle Bookmark',
			'capability'       => 'edit_bookmarks'
		),
		'add-note'         => array(
			'slug'             => 'add-note',
			'method'           => 'add_note',
			'callback'         => 'single',
			'label'            => 'Add Note',
			'capability'       => 'edit_notes'
		),
		'update-note'      => array(
			'slug'             => 'update-note',
			'method'           => 'update_note',
			'callback'         => 'single',
			'label'            => 'Update Note',
			'capability'       => 'edit_notes'
		),
		'remove-note'      => array(
			'slug'             => 'remove-note',
			'method'           => 'remove_note',
			'callback'         => 'single',
			'label'            => 'Remove Note',
			'capability'       => 'edit_notes'
		),
		/* 'add-task'         => array(
			'slug'             => 'add-task',
			'method'           => 'add_task',
			'callback'         => 'single',
			'label'            => 'Add Task',
			'capability'       => 'edit_tasks'
		),
		'update-task'      => array(
			'slug'             => 'update-task',
			'method'           => 'update_task',
			'callback'         => 'single',
			'label'            => 'Update Task',
			'capability'       => 'edit_tasks'
		),
		'remove-task'      => array(
			'slug'             => 'remove-task',
			'method'           => 'remove_task',
			'callback'         => 'single',
			'label'            => 'Remove Task',
			'capability'       => 'edit_tasks'
		), */
		'check-task'      => array(
			'slug'             => 'check-task',
			'method'           => 'check_task',
			'callback'         => 'single',
			'label'            => 'Check Task',
			'capability'       => 'edit_tasks'
		),
		'uncheck-task'      => array(
			'slug'             => 'uncheck-task',
			'method'           => 'uncheck_task',
			'callback'         => 'single',
			'label'            => 'Uncheck Task',
			'capability'       => 'edit_tasks'
		),
		'toggle-task'      => array(
			'slug'             => 'toggle-task',
			'method'           => 'toggle_task',
			'callback'         => 'single',
			'label'            => 'Toggle Task',
			'capability'       => 'edit_tasks'
		),
		'add-vote'         => array(
			'slug'             => 'add-vote',
			'method'           => 'add_vote',
			'callback'         => 'single',
			'label'            => 'Add Vote',
			'capability'       => 'edit_votes'
		),
		'add-rating'         => array(
			'slug'             => 'add-rating',
			'method'           => 'add_rating',
			'callback'         => 'single',
			'label'            => 'Add Rating',
			'capability'       => 'edit_ratings'
		),
		'file'           => array(
			'slug'             => 'file',
			'method'           => 'file',
			'label'            => 'File',
			'capability'       => 'edit_posts'
		)
	);

	/**
	 * A full list of allowable API views for any particular post type
	 */
	protected $_valid_views = array(
		'archive' => array(
			'label'  => 'List',
			'slug'   => 'archive',
			'order'  => 0,
			'single' => false,
			'hidden' => false
		),
		'search' => array(
			'label'  => 'Search',
			'slug'   => 'search',
			'order'  => 0,
			'single' => false,
			'hidden' => true
		),
		'single' => array(
			'label'  => 'View',
			'slug'   => 'single',
			'order'  => 0,
			'single' => true,
			'hidden' => true
		),
		'excerpt' => array(
			'label'   => 'Excerpt',
			'slug'    => 'excerpt',
			'order'   => 0,
			'single'  => true,
			'hidden'  => true
		),
		'table' => array(
			'label'  => 'Table',
			'slug'   => 'table',
			'order'  => 0,
			'single' => false,
			'hidden' => false
		),
		'form' => array(
			'label'   => 'Form',
			'slug'    => 'form',
			'aliases' => array( 'add', 'edit', 'quick-edit' ),
			'order'   => 0,
			'single'  => true,
			'hidden'  => true
		),
		'tax' => array(
			'label'   => 'Taxonomy',
			'slug'    => 'tax',
			'aliases' => array(),
			'order'   => 0,
			'single'  => true,
			'hidden'  => true
		)
	);

	public function __construct( $params=array() ) {
		global $kickpress_post_types;

		$default_params = array(
			'post_type' => null,
			'action'    => null
		);

		$this->params = array_merge($default_params, $params);

		if ( isset($kickpress_post_types[$this->params['post_type']]['post_type_id']) ) {
			$meta_type = get_post_meta($kickpress_post_types[$this->params['post_type']]['post_type_id'], '_meta_type', true);

			if ( is_file( dirname( __FILE__ ) . '/handlers/class-' . $meta_type . '.php' ) ) {
				require_once dirname( __FILE__ ) . '/kickpress-api-handler.php';
				require_once dirname( __FILE__ ) . '/handlers/class-' . $meta_type . '.php';

				$handler_class = 'kickpress_' . $meta_type . '_handler';

				$this->_handler = new $handler_class( $this );
			}
		}

		// Initialize custom post type options
		$this->init_post_type_options();
	}

	public function __get( $key ) {
		return @$this->params[$key];
	}

	public function __isset( $key ) {
		return isset( $this->params[$key] );
	}

	/* Post Type Options */

	public function init_post_type_options() {
		// Grab the $_POST and append to it as needed
		if ( isset($_POST['data']) && ! isset($this->params['data']) ) {
			$this->params['data'] = $_POST['data'];
		}

		$post_type = $this->params['post_type'];
		if ( empty($post_type) || is_array($post_type) )
			return false;

		$post_type_name = str_replace(array('%20',' ','-'), "_", $post_type);
		$prefix = '_kickpress_'.$post_type_name.'_';

		//$admin_options = kickpress_get_admin_options();
		$post_type_options = $this->get_post_type_options();
		$post_type_options = $this->set_post_type_options($post_type_options);

		$validateFirst = 0;

		// Set the defualt params for crud if params are null
		if ( ! isset($this->params['id']) )
			$post_id = 0;
		else
			$post_id = $this->params['id'];

		// Check that data is not a JSON string, a la import-comments
		if ( !empty( $this->params['data'] ) && is_array( $this->params['data'] ) ) {
			$custom_fields = $this->get_custom_fields();
			foreach ( $custom_fields as $field_name => $custom_fields ) {
				// TODO: add incoming data values to the tables array
				// Shortcut the complex data array from incoming forms and pass it into the universal params array

				if ( isset( $this->params['data'][$post_type][$post_id] ) ) {
					$formdata = $this->params['data'][$post_type][$post_id];
					$this->params = array_merge($formdata, $this->params);
				}

				if ( ! $validateFirst++ ) {
					if ( ! isset($this->params['categories_toolbar']) )
						$this->params['categories_toolbar'] = true;
				}
			}
		}

		if ( isset($post_type) ) {
			require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-validation.php');
			$this->validation = new validation($this->params, $post_id);
		}

		$post_type = $this->params['post_type'];
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! $taxonomy->_builtin ) {
				$this->add_view_alias( 'tax', $taxonomy->name );
			}
		}
	}

	public function get_post_type_options($merge=true) {
		if ( $merge ) {
			if ( isset($this->_valid_views) && is_array($this->_valid_views) ) {
				foreach ( $this->_valid_views as $key=>$view_values )
					$views[$key] = $view_values['label'];
			}
/*
			if ( isset( $api->params['not_found'] ) )
				$labels['not_found'] = __( $api->params['not_found'], 'kickpress' );

			if ( isset( $api->params['not_found_in_trash'] ) )
				$labels['not_found_in_trash'] = __( $api->params['not_found_in_trash'], 'kickpress' );

			if ( isset( $api->params['menu_name'] ) )
				$labels['menu_name'] = __( $api->params['menu_name'], 'kickpress' );

*/
			$post_type_options = array(
				'general_options'        => array(
					'caption'                => __('Enter Options', 'kickpress'),
					'type'                   => 'title'
				),
				'singular_name'          => array(
					'caption'                => __('Name (Singular)', 'kickpress'),
					'name'                   => '_singular_name',
					'type'                   => 'text',
					'notes'                  => __('The caption that you want to show when refering to a single instance of this post type.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'menu_name'          => array(
					'caption'                => __('Name (Menu)', 'kickpress'),
					'name'                   => '_menu_name',
					'type'                   => 'text',
					'notes'                  => __('The caption that you want to show in the admin menu.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'add_new'          => array(
					'caption'                => __('"Add New" Caption', 'kickpress'),
					'name'                   => '_add_new',
					'type'                   => 'text',
					'notes'                  => __('The name that you want to show when creating a new post of this post type.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'add_new_item'          => array(
					'caption'                => __('"Add New Item" Caption', 'kickpress'),
					'name'                   => '_add_new_item',
					'type'                   => 'text',
					'notes'                  => __('The name that you want to show when creating a new post of this post type.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'new_item'          => array(
					'caption'                => __('"New Item" Caption', 'kickpress'),
					'name'                   => '_new_item',
					'type'                   => 'text',
					'notes'                  => __('The name that you want to show when creating a new post of this post type.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'edit_item'          => array(
					'caption'                => __('"Edit Item" Caption', 'kickpress'),
					'name'                   => '_edit_item',
					'type'                   => 'text',
					'notes'                  => __('The caption that you want to show when editing a new post of this post type.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'view_item'          => array(
					'caption'                => __('"View Item" Caption', 'kickpress'),
					'name'                   => '_view_item',
					'type'                   => 'text',
					'notes'                  => __('The caption that you want to link to a post of this post type.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'all_items'          => array(
					'caption'                => __('"All Items" Caption', 'kickpress'),
					'name'                   => '_all_items',
					'type'                   => 'text',
					'notes'                  => __('The caption that you want to link to all posts of this post type.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'search_items'          => array(
					'caption'                => __('"Search Items" Caption', 'kickpress'),
					'name'                   => '_search_items',
					'type'                   => 'text',
					'notes'                  => __('The caption that you want to search posts of this post type.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'not_found'          => array(
					'caption'                => __('"Not Found" Caption', 'kickpress'),
					'name'                   => '_not_found',
					'type'                   => 'text',
					'notes'                  => __('The caption that you want to when no search results are found.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'not_found_in_trash'          => array(
					'caption'                => __('"Not Found in Trash" Caption', 'kickpress'),
					'name'                   => '_not_found_in_trash',
					'type'                   => 'text',
					'notes'                  => __('The caption that you want to when no search results are found in the trash.', 'kickpress'),
					'class'                  => 'regular-text'
				),
				'meta_type'              => array(
					'caption'                => __('Data Type', 'kickpress'),
					'name'                   => '_meta_type',
					'type'                   => 'select',
					'default'                => 'none',
					'options'                => array(
						'none'                   => __('None', 'kickpress'),
						'people'                 => __('People', 'kickpress'),
						'items'                  => __('Items', 'kickpress'),
						'locations'              => __('Locations', 'kickpress'),
						'events'                 => __('Events', 'kickpress'),
						'series'                 => __('Series', 'kickpress')
					),
					'notes'                  => __('Determine what the meta type of this new post type is.', 'kickpress')
				),
				'builtin'                => array(
					'caption'                => __('Built In', 'kickpress'),
					'name'                   => '_builtin',
					'type'                   => 'checkbox',
					'default'                => 'disable'
				),
				'merge_base_fields'    => array(
					'caption'                => __('Merge Base Fields', 'kickpress'),
					'name'                   => '_merge_base_fields',
					'type'                   => 'checkbox',
					'default'                => 'disable',
					'notes'                  => __('Include inherited fields from base post type in custom form.', 'kickpress')
				),
				'alphabar'               => array(
					'caption'                => __('Alphabar', 'kickpress'),
					'name'                   => '_alphabar',
					'type'                   => 'post_type_fields',
					'default'                => ''
				),
				'categories_toolbar'     => array(
					'caption'                => __('Category Toolbar', 'kickpress'),
					'name'                   => '_categories_toolbar',
					'type'                   => 'checkbox',
					'default'                => 'disable'
				),
				'scan'                   => array(
					'caption'                => __('Scan', 'kickpress'),
					'name'                   => '_scan',
					'type'                   => 'post_type_fields',
					'default'                => 'ID'
				),
				'selector'               => array(
					'caption'                => __('Selector', 'kickpress'),
					'name'                   => '_selector',
					'type'                   => 'post_type_fields',
					'default'                => ''
				),
				'default_sort_field'     => array(
					'caption'                => __('Default Sort Order', 'kickpress'),
					'name'                   => '_default_sort_field',
					'type'                   => 'field_sort_order',
					'default'                => 'post_date'
				),
				'default_sort_direction' => array(
					'caption'                => __('Default Sort Direction', 'kickpress'),
					'name'                   => '_default_sort_direction',
					'type'                   => 'field_sort_direction',
					'default'                => 'DESC',
					'options'                => array(
						'ASC'                   => __('Ascending Order (A-Z)', 'kickpress'),
						'DESC'                  => __('Descending Order (Z-A)', 'kickpress')
					)
				),
				'default_view'           => array(
					'caption'                => __('Default View', 'kickpress'),
					'name'                   => '_default_view',
					'type'                   => 'select',
					'default'                => 'archive',
					'options'                => $views
				),
				'posts_per_page'         => array(
					'caption'                => __('Posts Per Page', 'kickpress'),
					'name'                   => '_posts_per_page',
					'type'                   => 'text',
					//'default'                => 10
				),
				'top_pagination_type'         => array(
					'caption'                => __('Top Pagination Type', 'kickpress'),
					'name'                   => '_top_pagination_type',
					'type'                   => 'select',
					'default'                => 'none',
					'options'                => array(
						'none'                   => __('None', 'kickpress'),
						'default'                => __('Standard', 'kickpress'),
						'wp'                     => __('Previous/Next', 'kickpress'),
						'ajax'                   => __('Live', 'kickpress')
					)
				),
				'bottom_pagination_type'      => array(
					'caption'                => __('Bottom Pagination Type', 'kickpress'),
					'name'                   => '_bottom_pagination_type',
					'type'                   => 'select',
					'default'                => 'default',
					'options'                => array(
						'none'                   => __('None', 'kickpress'),
						'default'                => __('Standard', 'kickpress'),
						'wp'                     => __('Previous/Next', 'kickpress'),
						'more'                   => __('Load More', 'kickpress')
					)
				),
				'public'                 => array(
					'caption'                => __('Public', 'kickpress'),
					'name'                   => '_public',
					'type'                   => 'checkbox',
					'default'                => 'enable'
				),
				'publicly_queryable'     => array(
					'caption'                => __('Publicly Queryable', 'kickpress'),
					'name'                   => '_publicly_queryable',
					'type'                   => 'checkbox',
					'default'                => 'enable'
				),
				'hierarchical'     => array(
					'caption'                => __('Hierarchical', 'kickpress'),
					'name'                   => '_hierarchical',
					'type'                   => 'checkbox',
					'default'                => 'enable'
				),
				'exclude_from_search'    => array(
					'caption'                => __('Exclude From Search', 'kickpress'),
					'name'                   => '_exclude_from_search',
					'type'                   => 'checkbox',
					'default'                => 'disable'
				),
				'show_ui'                => array(
					'caption'                => __('Show Admin UI', 'kickpress'),
					'name'                   => '_show_ui',
					'type'                   => 'checkbox',
					'default'                => 'enable'
				),
				'show_in_menu'           => array(
					'caption'                => __('Show in Admin Menu', 'kickpress'),
					'name'                   => '_show_in_menu',
					'type'                   => 'checkbox',
					'default'                => 'enable'
				),
				'term_search_options'    => array(
					'caption'                => __('Category Search Options', 'kickpress'),
					'name'                   => '_term_search_options',
					'type'                   => 'term_search_options',
					'default'                => '',
					'notes'                  => __('Determine how users can search individual categories.', 'kickpress')
				)
			);

			if ( ! is_null( $this->_handler ) )
				$post_type_options = array_merge( $post_type_options,
					$this->_handler->get_post_type_options() );

			return $post_type_options;
		} else {
			return array();
		}
	}

	public function set_post_type_options($post_type_options=array()) {
		global $default_view, $kickpress_post_types;

		$post_type = $this->params['post_type'];

		if ( isset($this->params['id']) && $this->params['id'] > 0 )
			$post_id = $this->params['id'];
		elseif ( isset($kickpress_post_types[$post_type]['post_type_id']) )
			$post_id = $kickpress_post_types[$post_type]['post_type_id'];
		else
			$post_id = 0;

		foreach ( $post_type_options as $option_name=>$value ) {
			if ( 'term_search_options' == $value['type'] ) {
				if ( isset($kickpress_post_types[$post_type]['post_type_id']) ) {
					if ( $custom_terms = wp_get_object_terms($kickpress_post_types[$post_type]['post_type_id'], 'custom-post-type-categories') )
						$this->params['terms'] = $custom_terms;
				}

				$this->params['terms'][] = (object) array(
					'name'=>'Category',
					'slug'=>'category'
				);

				if ( $post_type_term = term_exists($post_type.'-category') ) {
					$this->params['terms'][] = $post_type_term;
				} else {
					if ( isset($kickpress_post_types[$post_type]['post_type_id']) ) {
						$this->params['terms'][] = (object)array(
							'name'=>$kickpress_post_types[$post_type]['post_type_title'],
							'slug'=>$post_type.'-category'
						);
					} else {
						$this->params['terms'][] = (object)array(
							'name'=>$post_type,
							'slug'=>$post_type.'-category'
						);
					}
				}

				$post_type_options['terms'] = $this->params['terms'];

				foreach ( (array)$this->params['terms'] as $key=>$term ) {
					$term_filter_id = '_term_filter_'.str_replace('-', '_', $term->slug);
					if ( ! $option_value = get_post_meta($post_id, $term_filter_id, true) )
						$option_value = null;

					$this->params['terms'][$key]->filter_type = $option_value;
					$post_type_options['terms'][$key]->filter_type = $option_value;
				}
			} elseif ( isset($value['name']) ) {
				//if ( !$option_value = get_option($value['name']) )
				if ( ! $option_value = get_post_meta($post_id, $value['name'], true) ) {
					if ( isset($value['default']) )
						$option_value = $value['default'];
				} else {
					// Make this backwards compatable with versions of kickpress that used 'true' and 'false'
					if ( 'checkbox' == $value['type'] ) {
						$option_value = ( kickpress_boolean( $option_value, isset( $value['default'] ) ? $value['default'] : null ) ? 'enable' : 'disable' );
					}
				}

				$this->params[$option_name] = $option_value;
				$post_type_options[$option_name]['value'] = $option_value;
			}
		}

		return $post_type_options;
	}

	public function get_caption($field, $field_name) {
		if ( isset($field['caption']) )
			$caption = (string) $field['caption'];
		else
			$caption = ucwords(str_replace("_", " ", (string) $field_name));

		return $caption;
	}

	/**
	 * A full list of allowable API parameters for any particular post type
	 */
	public function get_filter_array() {
		$filter_array = array(
			// just for flexibility
			'view',
			'action',
			'post_type',      // for "add new" forms (prevents 404 on empty archives)
			'post_types',     // post_types/people,locations - allow for multiple post types in query
			'post_format',

			// keep as shortcuts for date[<min|max>]
			'year',
			'monthnum',
			'month',
			'day',

			// url query filters
			'rel',            // rel[<relationship>]/<rel_ID>
			'date',           // date[<min|max>]/<YYYY-mm-dd>/
			'term',           // term[<taxonomy>]/<term>
			'filter',         // filter[<field>][<operator>]/<value>
			'status',
			'author',
			'search',
			'sort',           // sort[field_name]/<asc|desc>
			'first_letter',
			'posts_per_page',

			// not developed yet
			'callback',
			'offset_by_id',    // offset for live data
			'locator',
			'target'           // persist the target id for pagination
		);

		$post_type = $this->params['post_type'];

		$filter_array = apply_filters( "kickpress_filter_array", $filter_array );
		$filter_array = apply_filters( "kickpress_filter_array_{$post_type}", $filter_array );

		return $filter_array;
	}

	public function slug_hook($slug) {
		// return an array of slugs and post ids that work for this post type
		// return array('valid-slug'=>'[post->ID]');
		return false;
	}

	public function slug_hook_test($slug) {
		global $wpdb;

		$sql = "
			SELECT
				$wpdb->posts.ID
			FROM
				$wpdb->posts
			WHERE
				$wpdb->posts.post_type = 'live-feed'
				AND $wpdb->posts.post_status = 'publish'
				AND $wpdb->posts.ID = %d";

		if ( $post = $wpdb->get_row($wpdb->prepare($sql, $slug)) )
			return get_permalink($post->ID);
		else
			return false;
	}

	/* Post Type Fields */

	public static function get_default_fields() {
		$default_fields = array(
			'enter_attributes' => array(
				'caption'          => 'Enter Attributes',
				'type'             => 'title'
			),
			'ID'               => array(
				'name'             => 'ID',
				'required'         => true,
				'type'             => 'barcode',
				'grid_element'     => 'hidden',
				'disabled'         => true
			),
			'post_title'       => array(
				'name'             => 'post_title',
				'caption'          => 'Title',
				'required'         => true,
				'exportable'       => true,
				'filterable'       => true,
				'searchable'       => true,
				'sortable'         => true
			),
			'post_content'     => array(
				'name'             => 'post_content',
				'caption'          => 'Description',
				'type'             => 'textarea',
				'grid_element'     => 'hidden',
				'exportable'       => true,
				'searchable'       => true
			),
			'post_status'      =>array(
				'name'             => 'post_status',
				'required'         => true,
				'type'             => 'radio',
				'default'          => 'publish',
				'options'          => array(
					'publish'          => 'Published',
					'private'          => 'Private',
					'pending'          => 'Pending',
					'draft'            => 'Draft'
				),
				'style'            => 'width:150px',
				'grid_element'     => 'hidden'
			),
			'_thumbnail_id'    =>array(
				'name'             => '_thumbnail_id',
				'caption'          => 'Post Thumbnail',
				'foreignkey'       => true,
				'type'             => 'file',
				'default'          => 'Select an existing picture...',
				'parent_term'      => 'media',
				'connect_table'    => 'people',
				'file_types'       => 'images',
				'show_in_admin'    => false
			),
			'tags_input'       =>array(
				'name'             => 'tags_input',
				'caption'          => 'Tags',
				'type'             => 'tags'
			),
			'categories_input' => array(
				'name'             => 'categories_input',
				'caption'          => 'Categories',
				'type'             => 'categories'
			)
		);

		return $default_fields;
	}

	public function get_handler_fields() {
		if ( ! is_null( $this->_handler ) )
			return $this->_handler->get_custom_fields();

		return array();
	}

	public function get_custom_fields( $merge = true ) {
		$default_fields = self::get_default_fields();

		if ( $merge ) {
			$handler_fields = $this->get_handler_fields();

			return array_merge( $default_fields, $handler_fields );
		} else {
			return $default_fields;
		}
	}

	/* Used exclusively for exporting purposes - NOT TRUE */
	public function get_custom_field_values( $post_id, $merge = true ) {
		$skip_post_vars = array('ID','post_author','post_date','post_date_gmt','post_content','post_title','post_category','post_excerpt','post_status','comment_status','ping_status','post_password','post_name','to_ping','pinged','post_modified','post_modified_gmt','post_content_filtered','post_parent','guid','menu_order','post_type','post_mime_type','comment_count','tags_input','categories_input','_thumbnail_id');
		$values = array();

		foreach ( $this->get_custom_fields( $merge ) as $key => $field ) {
			if ( empty( $field[ 'name' ] ) || in_array( $field[ 'name' ], $skip_post_vars ) ) {
				//( isset( $field[ 'exportable' ] ) && ! $field[ 'exportable' ] ) ) {
				continue;
			}

			if ( ! empty( $field[ 'single' ] ) )
				$is_single = kickpress_boolean( $field[ 'single' ], true );
			else
				$is_single = true;

			$values[ $key ] = get_post_meta( $post_id, $field[ 'name' ], $is_single );
		}
		return $values;
	}

	/* API Actions */

	public function get_valid_actions() {
		return $this->_valid_actions;
	}

	public function is_valid_action( $action_slug = null ) {
		foreach ( $this->_valid_actions as $action ) {
			if ( $action['slug'] == $action_slug ) return $action['slug'];
		}

		return false;
	}

	public function add_action( $args = null ) {
		if (is_null($args)) return;

		// Revisit default permission level
		$defaults = array(
			'slug'             => '',
			'method'           => '',
			'callback'         => '',
			'label'            => '',
			'capability'       => null,
			'default_format'   => null
		);

		if ( is_scalar($args) ) {
			$action = $defaults;
			$action['slug']     = $this->sanitize_slug($args);
			$action['method']   = $this->sanitize_method($args);
			$action['callback'] = '';
			$action['label']    = $this->sanitize_label($args);
		} elseif ( is_array($args) ) {
			$action = array_merge($defaults, $args);

			if ( !empty($action['slug']) ) {
				if (empty($action['method'])) $action['method'] = $this->sanitize_method($action['slug']);
				if (empty($action['label']))  $action['label']  = $this->sanitize_label($action['slug']);
			} elseif ( !empty($action['method']) ) {
				if (empty($action['slug']))   $action['slug']   = $this->sanitize_slug($action['method']);
				if (empty($action['label']))  $action['label']  = $this->sanitize_label($action['method']);
			} elseif ( !empty($action['label']) ) {
				if (empty($action['slug']))   $action['slug']   = $this->sanitize_slug($action['label']);
				if (empty($action['method'])) $action['method'] = $this->sanitize_method($action['label']);
			} else {
				return;
			}
		}

		if ( isset( $action ) )
			$this->_valid_actions[ $action['slug'] ] = $action;
	}

	public function add_actions( $actions = array() ) {
		if (is_array($actions)) {
			foreach ($actions as $action) {
				$this->add_action($action);
			}
		}
	}

	public function remove_action( $args = null ) {
		if (is_null($args)) return;

		if (is_scalar($args)) {
			$slug = strtolower(str_replace(array(' ', '%20'), '-', $args));
		} elseif (is_array($args)) {
			if (empty($args['slug'])) return;

			$slug = strtolower(str_replace(array(' ', '%20'), '-', $view['slug']));
		}

		if ( isset($slug) )
			unset( $this->_valid_actions[$slug] );
	}

	public function remove_actions( $actions = array() ) {
		if (is_array($actions)) {
			foreach ( $actions as $action ) {
				$this->remove_action( $action );
			}
		}
	}

	public function do_action( $action = null ) {
		if ( isset( $this->params[ 'post_name' ] ) && ! isset( $this->params[ 'id' ] ) ) {
			global $wp_post_statuses;
			$wp_post_statuses['future']->public = true;

			$post = array_shift( get_posts( array(
				'post_status' => array( 'publish', 'future' ),
				'post_type'   => $this->params[ 'post_type' ],
				'name'        => $this->params[ 'post_name' ]
			) ) );

			$wp_post_statuses['future']->public = false;

			$this->params[ 'id' ] = $post->ID;
		}

		if ( is_null( $action ) ) $action = $this->params[ 'action' ];

		if ( $current_action = $this->get_current_action( $action ) ) {
			extract( $current_action );

			$format = !empty( $this->params[ 'format' ] ) ? $this->params[ 'format' ] : $default_format;

			if ( $post_type = get_post_type_object( $this->params[ 'post_type' ] ) ) {
				if ( isset( $post_type->cap->$capability ) )
					$capability = $post_type->cap->$capability;
			}

			if ( strpos( $capability, 'edit_' ) === 0 ){
				if ( empty( $this->params['id'] ) ){
					$capability = substr_replace($capability, 'create_', 0, 5);
				}
			}

			if ( ! empty( $this->params['callback'] ) )
				$callback = $this->params['callback'];
			else
				$this->params['callback'] = $callback;

			if ( is_user_logged_in() && ! is_user_member_of_blog() )
				add_user_to_blog( get_current_blog_id(), get_current_user_id(), 'subscriber' );

			if ( is_user_logged_in() )
				$authorized = current_user_can( $capability );
			elseif ( kickpress_is_remote_app() )
				$authorized = get_role( 'app' )->has_cap( $capability );
			else
				$authorized = kickpress_anonymous_user_can( $capability );

			// Validate that the user has permission to perform this action
			if ( ! empty( $capability ) && ! $authorized ) {
				$this->action_results[ 'data' ][ 'post_id' ] = $this->params[ 'id' ];
				$this->action_results[ 'status' ] = 'failure';
				$this->action_results[ 'messages' ][ 'note' ] = __( 'Sorry, you do not have the permissions to perform this action.', 'kickpress' );

				if ( isset( $_SERVER['HTTP_X_SOURCE'] ) ) {
					$logger = new mysqli( LOGGING_DB_HOST, LOGGING_DB_USER, LOGGING_DB_PASSWORD, LOGGING_DB_NAME );

					if ( $logger->connect_error )
						error_log( $logger->connect_error );

					$sql = 'INSERT INTO `permission_denied` (`request_id`, `source`, `user_id`, `capability`, `action`, `uri`, `header`) VALUES (?, ?, ?, ?, ?, ?, ?)';

					$user_id = get_current_user_id();

					$stmt = $logger->prepare($sql);
					$stmt->bind_param('ssissss', $_SERVER['HTTP_X_REQUEST_ID'], $_SERVER['HTTP_X_SOURCE'],
						$user_id, $capability, $action, $_SERVER['REQUEST_URI'], $_SERVER['HTTP_AUTHORIZATION'] );

					if ( ! $stmt->execute() )
						error_log( $stmt->error );

					$logger->close();
				}
			} elseif ( method_exists( $this, $method ) ) {
				call_user_func( array( $this, $method ), $this->params );
			} elseif ( is_object( $this->_handler ) && method_exists( $this->_handler, $method ) ) {
				call_user_func( array( $this->_handler, $method ), $this->params );
			}
		}

		// Return a response with the results of action to the ajax request
		if ( kickpress_is_remote_app() || 'json' == $format ) {
			ob_clean();
			$this->serialize_results();
			exit;
		} else {
			kickpress_set_session_var( 'action_results', $this->action_results );
			$this->redirect( $callback );
		}
	}

	public function get_current_action( $action=null, $merge=true ) {
		$valid_actions = $this->get_valid_actions();
		$action = null != $action ? $action : $this->params['action'];

		$default_action_vars = array(
			'slug'           => str_replace( '_', '-', $action ),
			'method'         => $action,
			'callback'       => '',
			'default_format' => '',
			'label'          => ucwords( str_replace( '_', ' ', $action ) ),
			'capability'     => 'edit_posts'
		);

		if ( ! array_key_exists( $action, $valid_actions ) ) {
			$this->action_results[ 'messages' ][ 'note' ] = __( 'The requested action is invalid.', 'kickpress' );
			return false;
		} else {
			if ( $merge )
				return array_merge( $default_action_vars, $valid_actions[$action] );
			else
				return $valid_actions[$action];
		}
	}

	public function merge_results_data( $key, $data = array() ) {
		if ( !isset($this->action_results[ 'data' ][ $key ]) )
			$this->action_results[ 'data' ][ $key ] = array();

		$this->action_results[ 'data' ][ $key ] = array_merge( $this->action_results[ 'data' ][ $key ], $data );
		return $this->action_results;
	}

	public function serialize_results( $format = null ) {
		echo $this->get_serialized_results( $format );
	}

	public function get_serialized_results( $format = null ) {
		if ( is_null( $format ) ) $format = $this->params['format'];

		if ( ( $pos = strpos( $format, '/' ) ) !== false )
			$format = substr( $format, $pos + 1 );

		switch ( $format ) {
		case 'xml':
			$doc = new DOMDocument();
			$doc->formatOutput = true;
			$doc->appendChild( $root = $doc->createElement( 'result' ) );

			$root->appendChild( $doc->createElement( 'status', $this->action_results['status'] ) );
			$root->appendChild( $messages = $doc->createElement( 'messages' ) );
			$root->appendChild( $data = $doc->createElement( 'data' ) );

			foreach ( $this->action_results['messages'] as $type => $message ) {
				$messages->appendChild( $doc->createElement( $type, $message ) );
			}

			// TODO write data XML

			return $doc->saveXML();
		case 'json':
		default:
			$output = json_encode( $this->action_results );

			if ( isset( $_REQUEST['callback'] ) )
				$output .= $_REQUEST['callback'] . '(' . $output . ');';

			return $output;
		}
	}

	public function get_action_results( $delete = true ) {
		if ( $action_results = kickpress_get_session_var( 'action_results' ) ) {
			//$form_notes = json_decode( $form_notes, true );
			if ( $delete )
				kickpress_delete_session_var( 'action_results' );

			return $action_results;
		} else {
			return false;
		}
	}

	public function generate_system_notes( $action_results = null ) {
		if ( is_null( $action_results ) ) $action_results = $this->action_results;

		$systemNotes = array();
		if ( isset( $action_results['messages'] ) ) {
			if ( !empty($action_results['messages'][ $this->params['post_type'] ] ) )
				$messages = $action_results['messages'][ $this->params['post_type'] ][ $this->params['id'] ];
			else
				$messages = $action_results['messages'];

			foreach ( $messages as $type=>$notes ) {

				// There were errors while submitting the form
				if ( 'error' == $type ) {
					$formErrors = '<div class="error"><h3>'.__('Errors', 'kickpress').':</h3>';
					$errorCount = 1;

					// Output errors as JSON to screen
					//echo '<pre>' . htmlspecialchars( print_r( $action_results, true ) ) . '</pre>';

					// There might be multiple errors, so they are in an array.
					foreach ( $notes['error'] as $key => $value ) {
						$formErrors .= sprintf('
							<p class="form-error%3$s"><span class="error-number">%1$s.</span> %2$s</p>',
							($errorCount++),
							$value,
							($errorCount % 2 ? "": " alt")
						);
					}
					$formErrors .= '</div>';

					// Add the form errors to the system notes array
					$systemNotes[] = $formErrors;
				} elseif ( 'note' == $type ) {
					// There was a system message while submitting the form
					$systemNotes[] = sprintf( '<p class="system-message">%1$s</p>', $notes );
				} else {

					// There were notes while submitting the form
					if ( is_array($notes) ) {
						// Plan for some notes to come in the form of an array
						foreach ( $notes as $key=>$value ) {
							$systemNotes[] = sprintf('<p class="%1$s">%2$s</p>', $type, $value);
						}
					} else {
						$systemNotes[] = sprintf('<p class="%1$s">%2$s</p>', $type, $notes);
					}
				}
			}
		}

		if ( count($systemNotes) )
			return '<div class="site-notes">'.implode('', $systemNotes).'</div>';
		else
			return '';
	}

	/* Extended Views */

	public function add_view( $args=null ) {
		if (is_null($args)) return;

		$defaults = array(
			'slug'    => '',
			'label'   => '',
			'aliases' => array(),
			'order'   => 0,
			'single'  => false,
			'hidden'  => false
		);

		if (is_scalar($args)) {
			$view = $defaults;
			$view['slug']  = $this->sanitize_slug($args);
			$view['label'] = $this->sanitize_label($args);
		} elseif (is_array($args)) {
			$view = array_merge($defaults, $args);

			if (!empty($view['slug'])) {
				if (empty($view['label'])) $view['label'] = $this->sanitize_label($view['slug']);
			} elseif (!empty($view['label'])) {
				if (empty($view['slug']))  $view['slug']  = $this->sanitize_slug($view['label']);
			} else {
				return;
			}
		}

		if (isset($view)) $this->_valid_views[$view['slug']] = $view;
	}

	public function add_views( $views=array() ) {
		if (is_array($views)) {
			foreach ($views as $view) {
				$this->add_view($view);
			}
		}
	}

	public function remove_view( $args=null ) {
		if (is_null($args)) return;

		if (is_scalar($args)) {
			$slug = $this->sanitize_slug($args);
		} elseif (is_array($args)) {
			if (empty($args['slug'])) return;

			$slug = $this->sanitize_slug($view['slug']);
		}

		if (isset($slug)) unset($this->_valid_views[$slug]);
	}

	public function remove_views( $views=array() ) {
		if (is_array($views)) {
			foreach ($views as $view) {
				$this->remove_view($view);
			}
		}
	}

	public function add_view_alias( $view, $alias ) {
		$view_slug  = $this->sanitize_slug( $view );
		$alias_slug = $this->sanitize_slug( $alias );

		if ( isset( $this->_valid_views[$view_slug] ) )
			$this->_valid_views[$view_slug]['aliases'][] = $alias_slug;
	}

	public function get_valid_views() {
		return $this->_valid_views;
	}

	public function is_valid_view( $view_slug = null ) {
		foreach ( $this->_valid_views as $view ) {
			if ( $view['slug'] == $view_slug ) return $view['slug'];

			foreach ( (array) @$view['aliases'] as $view_alias ) {
				if ( $view_alias == $view_slug ) return $view['slug'];
			}
		}

		return false;
	}

	/* Helper methods for Actions and Views */

	private function sanitize_slug($string) {
		return strtolower(str_replace(array(' ', '%20'), '-', $string));
	}

	private function sanitize_label($string) {
		return ucwords(str_replace(array('-', '_'), ' ', $string));
	}

	private function sanitize_method($string) {
		return strtolower(str_replace(array(' ', '%20', '-'), '_', $string));
	}

	/* Meta Type Hooks */

	public function join_filter( $join ) {
		if ( is_object( $this->_handler ) )
			$join = $this->_handler->join_filter( $join );

		return $join;
	}

	public function where_filter( $where ) {
		if ( is_object( $this->_handler ) )
			$where = $this->_handler->where_filter( $where );

		return $where;
	}

	public function form_footer() {
		if ( is_object( $this->_handler) ) {
			return $this->_handler->form_footer();
		}

		return null;
	}

	/* API Action Handlers */

	public function toggle_term() {
		global $post;

		$post = array_shift( get_posts( array(
			'post_type' => $this->params['post_type'],
			'name' => $this->params['post_name']
		) ) );

		$taxonomy = $this->params['action_key'];

		$terms = is_array( $this->params['extra'] ) ? explode( ',', $this->params['extra'][0] ) : array();

		foreach ( $terms as $term_key => $term ) {
			if ( is_string( $term ) ) {
				if ( ! $this_term = term_exists( $term, $taxonomy ) ) {
					// $caption = ucwords(str_replace("_", " ", (string) $field_name));
					wp_insert_term( ucwords( str_replace( array( "_", "-" ), " ", $term ) ), $taxonomy, array( 'slug' => $term ) );
					$this_term = get_term_by( 'slug', $term, $taxonomy, ARRAY_A );
				}

				$term_id = $this_term['term_id'];
			} else {
				$term_id = $term;
			}

			$current_action = $this->get_current_action();
			if ( has_term( $term_id, $taxonomy, $post->ID ) ) {
				$existing_terms_array = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

				// Remove the current term form the terms array
				foreach ( $existing_terms_array as $term_index => $existing_term ) {
					if ( $term_id == $existing_term ) {
						unset( $existing_terms_array[$term_index] );
					}
				}

				$this->action_results[ 'data' ][ 'post_id' ] = $post->ID;

				// Remove the term, the fourth parameter 'false' relaces the term array
				if ( $result = wp_set_post_terms( $post->ID, $existing_terms_array, $taxonomy, false ) ) {
					$action_status = 'success';
					$this->action_results[ 'messages' ][ 'note' ] = __( 'Term Removed', 'kickpress' );
					$this->action_results[ 'data' ][ 'terms' ] = $result;
				} else {
					if ( false === $result ) {
						$action_status = 'failure';
						$this->action_results[ 'messages' ][ 'note' ] = __( 'Failed to Remove Term', 'kickpress' );
					} else {
						$action_status = 'success';
						$this->action_results[ 'messages' ][ 'note' ] = __( 'Term Removed', 'kickpress' );
					}
				}
			} else {
				// Add the term, the fourth parameter 'true' appends the term to the term array
				if ( $result = wp_set_post_terms( $post->ID, array( intval( $term_id ) ), $taxonomy, true ) ) {
					$this->action_results[ 'data' ][ 'terms' ] = $result;
					$action_status = 'success';
					$this->action_results[ 'messages' ][ 'note' ] = __( 'Term Added', 'kickpress' );
				} else {
					$action_status = 'failure';
					$this->action_results[ 'messages' ][ 'note' ] = __( 'Failed to Remove Term', 'kickpress' );
				}
			}

			$this->action_results[ 'status' ] = $action_status;
			$this->action_results[ 'data' ][ 'terms' ] = wp_get_post_terms( $post->ID, $taxonomy );
		}
	}

	public function add_terms() {
		$post = array_shift( get_posts( array(
			'post_type' => $this->params['post_type'],
			'name' => $this->params['post_name']
		) ) );

		$taxonomy = $this->params['action_key'];

		$terms = is_array( $this->params['extra'] ) ? explode( ',', $this->params['extra'][0] ) : array();

		foreach ( $terms as $term_key => $term ) {
			if ( is_string( $term ) ) {
				if ( ! $this_term = term_exists( $term, $taxonomy) ) {
					wp_insert_term( ucwords( str_replace( array( "_", "-" ), " ", $term ) ), $taxonomy, array( 'slug' => $term ) );
					//wp_insert_term( $term, $taxonomy, array( 'slug' => $term ) );
					$this_term = get_term_by( 'slug', $term, $taxonomy, ARRAY_A );
				}
				$terms[$term_key] = $this_term['term_id'];
			}
		}

		$this->action_results[ 'data' ][ 'post_id' ] = $post->ID;

		if ( $result = wp_set_post_terms( $post->ID, $terms, $taxonomy, true ) ) {
			$this->action_results[ 'data' ][ 'terms' ] = $result;
			$action_status = 'success';
		} else {
			$action_status = 'failure';
		}

		$this->action_results[ 'status' ] = $action_status;
		$status_messages = array(
			'success' => 'Success',
			'failure' => 'Failure'
		);
		$current_action = $this->get_current_action();
		if ( isset( $status_messages[ $action_status ] ) )
			$this->action_results[ 'messages' ][ 'note' ] = $status_messages[$action_status];
		else
			$this->action_results[ 'messages' ][ 'note' ] = $action_status;
	} // add_terms

	public function remove_terms() {
		$post = array_shift( get_posts( array(
			'post_type' => $this->params['post_type'],
			'name' => $this->params['post_name']
		) ) );

		$taxonomy = $this->params['action_key'];
		$remove_terms = is_array( $this->params['extra'] ) ? explode( ',', $this->params['extra'][0] ) : array();

		foreach ( $remove_terms as $term_key => $term ) {
			if ( is_string( $term ) ) {
				$this_term = get_term_by( 'slug', $term, $taxonomy, ARRAY_A );
				$terms[$term_key] = $this_term['term_id'];
			}
		}

		$terms = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'names' ) );

		foreach ( $terms as $index => $term ) {
			if ( in_array( $term, $remove_terms ) ) {
				unset( $terms[$index] );
			}
		}

		$this->action_results[ 'data' ][ 'post_id' ] = $post->ID;

		if ( $result = wp_set_post_terms( $post->ID, $terms, $taxonomy, false ) ) {
			$this->action_results[ 'data' ][ 'terms' ] = $result;
			$action_status = 'success';
		} else {
			$action_status = 'failure';
		}

		$this->action_results[ 'status' ] = $action_status;
		$status_messages = array(
			'success' => 'Success',
			'failure' => 'Failure'
		);
		$current_action = $this->get_current_action();
		if ( isset( $status_messages[ $action_status ] ) )
			$this->action_results[ 'messages' ][ 'note' ] = $status_messages[$action_status];
		else
			$this->action_results[ 'messages' ][ 'note' ] = $action_status;
	} // remove_terms

	public function add_bookmark() {
		kickpress_insert_bookmark( $this->params['id'] );
	}

	public function remove_bookmark() {
		kickpress_delete_bookmark( $this->params['id'] );
	}

	public function toggle_bookmark() {
		if ( empty( $this->params['id'] ) ) {
			global $post;
			if ( ! $this->params['id'] = $post->ID ) {
				return false;
			}
		}

		if ( get_post( $this->params['id'] ) )
			$this->action_results[ 'data' ][ 'post_id' ] = $this->params['id'];
		else
			return false;

		$bookmarks = kickpress_get_bookmarks( array(
			'post_id' => $this->params['id']
		) );

		if ( empty( $bookmarks ) ) {
			kickpress_insert_bookmark( $this->params['id'] );
			$this->action_results[ 'status' ] = 'success';
			$this->action_results[ 'messages' ][ 'note' ] = __( 'Bookmark Added', 'kickpress' );
			// return true;
		} else {
			kickpress_delete_bookmark( $this->params['id'] );
			$this->action_results[ 'status' ] = 'success';
			$this->action_results[ 'messages' ][ 'note' ] = __( 'Bookmark Removed', 'kickpress' );
			// return true;
		}
		//$this->action_results[ 'status' ] = 'failure';
		//kickpress_toggle_bookmark( $this->params['id'] );
	}

	public function add_note() {
		kickpress_insert_note( $this->params['id'], $_REQUEST['note']['title'], $_REQUEST['note']['content'] );
	}

	public function update_note() {
		kickpress_update_note( $this->params['action_key'], $_REQUEST['note']['title'], $_REQUEST['note']['content'] );
	}

	public function remove_note() {
		kickpress_delete_note( $this->params['action_key'] );
	}

	public function add_task() {
		kickpress_add_task( $this->params['id'], $_REQUEST['task']['content'] );
	}

	public function update_task() {
		kickpress_update_task( $this->params['action_key'], $_REQUEST['task']['content'] );
	}

	public function delete_task() {
		kickpress_delete_task( $this->params['action_key'] );
	}

	public function check_task() {
		kickpress_update_task( $this->params['action_key'], null, 1 );
	}

	public function uncheck_task() {
		kickpress_update_task( $this->params['action_key'], null, 0 );
	}

	public function toggle_task() {
		kickpress_toggle_task( $this->params['action_key'] );
	}

	public function add_vote() {
		if ( is_numeric( $this->params['action_key'] ) )
			kickpress_insert_vote( $this->params['id'], $this->params['action_key'] );
	}

	public function add_rating() {
		if ( is_numeric( $this->params['action_key'] ) )
			kickpress_insert_rating( $this->params['id'], $this->params['action_key'] );
	}

	public function login() {
		$proxy = strtolower( $this->params['action_key'] );

		if ( empty( $proxy ) ) {
			// Twitter won't redirect to urls with square brackets
			// check for oauth verifier parameter
			if ( isset( $_REQUEST['oauth_verifier'] ) )
				$proxy = 'twitter';
			else return;
		}

		switch ( $proxy ) {
		case 'facebook':
			$this->login_facebook();
			break;
		case 'twitter':
			$this->login_twitter();
			break;
		default:
			$this->action_results[ 'status' ] = 'failure';
			$this->action_results[ 'messages' ][] = __( 'Unknown login proxy.', 'kickpress' );
			$this->action_results[ 'data' ][] = $proxy;
			break;
		}
	}

	private function login_facebook() {
		global $kickpress_plugin_options;

		$client_id     = $kickpress_plugin_options['facebook_app_id']['value'];
		$client_secret = $kickpress_plugin_options['facebook_app_secret']['value'];

		$redirect_uri = rtrim( kickpress_api_url( array(
			'action'     => 'login',
			'action_key' => 'facebook'
		) ), '/' );

		$facebook_state = kickpress_get_session_var( 'facebook_state' );
		$facebook_token = kickpress_get_session_var( 'facebook_token' );

		if ( empty( $facebook_token ) ) {
			$code  = $_REQUEST['code'];
			$state = $_REQUEST['state'];

			if ( empty( $code ) || empty( $state ) ) {
				$state = md5( uniqid( rand(), true ) );

				kickpress_set_session_var( 'facebook_state', $state );

				$dialog_query = http_build_query( array(
					'client_id'    => $client_id,
					'redirect_uri' => $redirect_uri,
					'state'        => $state
				) );

				$dialog_url = "https://www.facebook.com/dialog/oauth?" . $dialog_query;

				header( 'Location: ' . $dialog_url );
				exit;
			}

			if ( $facebook_state == $state ) {
				$token_query = http_build_query( array(
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
					'redirect_uri'  => $redirect_uri,
					'code'          => $code
				) );

				$token_url = "https://graph.facebook.com/oauth/access_token?" . $token_query;

				$response = file_get_contents( $token_url );

				parse_str( $response, $params );

				$facebook_token = $params['access_token'];

				kickpress_set_session_var( 'facebook_token', $facebook_token );
			}

			if ( empty( $facebook_token ) ) {
				$this->action_results[ 'status' ] = 'failure';
				$this->action_results[ 'messages' ][] = __( 'Could not get facebook access token.', 'kickpress' );
				$this->action_results[ 'data' ][] = array(
					'app_id'     => $client_id,
					'app_secret' => $client_secret
				);

				return;
			}
		}

		$graph_url = "https://graph.facebook.com/me?access_token=" . $facebook_token;

		$fb_user_data = json_decode( file_get_contents( $graph_url ) );

		$users = get_users( array(
			'meta_key'   => '_facebook_id',
			'meta_value' => $fb_user_data->id
		) );

		if ( empty( $users ) ) {
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
			} else {
				$user_id = wp_insert_user( array(
					'user_email'   => $fb_user_data->id . '@facebook.com',
					'user_login'   => md5( uniqid( rand(), true ) ),
					'user_pass'    => md5( uniqid( rand(), true ) ),
					'user_url'     => $fb_user_data->link,
					'display_name' => $fb_user_data->name,
					'first_name'   => $fb_user_data->first_name,
					'last_name'    => $fb_user_data->last_name,
					'description'  => $fb_user_data->bio,
					'role'         => 'subscriber'
				) );

				if ( is_wp_error( $user_id ) ) {
					die( '<pre>Error: ' . print_r( $user_id, true ) . '</pre>' );
					// TODO registration error, now what?
				}

				$data = array(
					'post_type'    => 'profiles',
					'post_title'   => $fb_user_data->name,
					'post_content' => '',
					'post_status'  => 'publish',
					'post_author'  => $user_id
				);

				if ( $post_id = wp_insert_post( $data ) )
					add_post_meta( $post_id, 'user_id', $user_id );
			}

			add_user_meta( $user_id, '_facebook_id', $fb_user_data->id );

			$user = get_userdata( $user_id );
		} else {
			$user = array_shift( $users );
		}

		if ( ! is_user_logged_in() ) {
			wp_set_current_user( $user->ID );
			wp_set_auth_cookie( $user->ID );

			do_action( 'wp_login', $user->user_login, $user );
		}

		die( '<pre>User: ' . print_r( $user, true ) . '</pre>' );
		// TODO logged in, now what?
	}

	private function login_twitter() {
		$log_file = dirname( __FILE__ ) . '/kickpress-twitter.log';

		global $kickpress_plugin_options;

		$consumer_key    = $kickpress_plugin_options['twitter_consumer_key']['value'];
		$consumer_secret = $kickpress_plugin_options['twitter_consumer_secret']['value'];

		$callback_url = rtrim( kickpress_api_url( array(
			'action'     => 'login',
			'action_key' => null
		) ), '/' );

		$token = $_REQUEST['oauth_token'];

		if ( empty( $token ) ) {
			$request_url = "https://api.twitter.com/oauth/request_token";

			$params = array(
				'oauth_callback' => $callback_url
			);

			$query = http_build_query( $params );
			$oauth = kickpress_get_oauth_signature( $request_url, $params, array(
				'method'          => "POST",
				'consumer_key'    => $consumer_key,
				'consumer_secret' => $consumer_secret
			) );

			$curl = curl_init( $request_url );

			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
				'Authorization: ' . $oauth
			) );

			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $query );

			$response = curl_exec( $curl );

			curl_close( $curl );

			parse_str( $response, $params );

			extract( $params );

			kickpress_set_session_var( 'twitter_oauth_token',        $oauth_token );
			kickpress_set_session_var( 'twitter_oauth_token_secret', $oauth_token_secret );

			header( 'Location: https://api.twitter.com/oauth/authenticate?oauth_token=' . $oauth_token );
			exit;
		}

		if ( isset( $_REQUEST['oauth_verifier'] ) ) {
			$request_url = "https://api.twitter.com/oauth/access_token";

			$access_token        = kickpress_get_session_var( 'twitter_oauth_token' );
			$access_token_secret = kickpress_get_session_var( 'twitter_oauth_token_secret' );

			$params = array(
				'oauth_verifier' => $_REQUEST['oauth_verifier']
			);

			$query = http_build_query( $params );
			$oauth = kickpress_get_oauth_signature( $request_url, $params, array(
				'method'              => "POST",
				'consumer_key'        => $consumer_key,
				'consumer_secret'     => $consumer_secret,
				'access_token'        => $access_token,
				'access_token_secret' => $access_token_secret
			) );

			$curl = curl_init( $request_url );

			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
				'Authorization: ' . $oauth
			) );

			curl_setopt( $curl, CURLOPT_POST, true );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $query );

			$response = curl_exec( $curl );

			curl_close( $curl );

			parse_str( $response, $params );

			extract( $params );

			kickpress_set_session_var( 'twitter_oauth_token',        $oauth_token );
			kickpress_set_session_var( 'twitter_oauth_token_secret', $oauth_token_secret );
			kickpress_set_session_var( 'twitter_user_id',            $user_id );
			kickpress_set_session_var( 'twitter_screen_name',        $screen_name );
		}

		$user_id     = kickpress_get_session_var( 'twitter_user_id', false );
		$screen_name = kickpress_get_session_var( 'twitter_screen_name', false );

		if ( $user_id && $screen_name ) {
			$request_url = "https://api.twitter.com/users/show.json?" . http_build_query( array(
				'user_id'     => $user_id,
				'screen_name' => $screen_name
			) );

			$access_token        = kickpress_get_session_var( 'twitter_oauth_token' );
			$access_token_secret = kickpress_get_session_var( 'twitter_oauth_token_secret' );

			$oauth = kickpress_get_oauth_signature( $request_url, array(), array(
				'method'              => 'GET',
				'consumer_key'        => $consumer_key,
				'consumer_secret'     => $consumer_secret,
				'access_token'        => $access_token,
				'access_token_secret' => $access_token_secret
			) );

			$curl = curl_init( $request_url );

			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array(
				'Authorization: ' . $oauth
			) );

			$response = curl_exec( $curl );

			curl_close( $curl );

			$tw_user_data = json_decode( $response );

			$users = get_users( array(
				'meta_key' => '_twitter_id',
				'meta_value' => $tw_user_data->id
			) );

			if ( empty( $users ) ) {
				if ( is_user_logged_in() ) {
					$user_id = get_current_user_id();
				} else {
					$user_id = wp_insert_user( array(
						'user_email'   => $tw_user_data->id . '@twitter.com',
						'user_login'   => md5( uniqid( rand(), true ) ),
						'user_pass'    => md5( uniqid( rand(), true ) ),
						'user_url'     => $tw_user_data->url,
						'display_name' => $tw_user_data->name,
						'description'  => $tw_user_data->description,
						'role'         => 'subscriber'
					) );

					if ( is_wp_error( $user_id ) ) {
						die( '<pre>Error: ' . print_r( $user_id, true ) . '</pre>' );
						// TODO registration error, now what?
					}

					$data = array(
						'post_type'    => 'profiles',
						'post_title'   => $tw_user_data->name,
						'post_content' => '',
						'post_status'  => 'publish',
						'post_author'  => $user_id
					);

					if ( $post_id = wp_insert_post( $data ) )
						add_post_meta( $post_id, 'user_id', $user_id );
				}

				add_user_meta( $user_id, '_twitter_id', $tw_user_data->id );

				$user = get_userdata( $user_id );
			} else {
				$user = array_shift( $users );
			}

			if ( ! is_user_logged_in() ) {
				wp_set_current_user( $user->ID );
				wp_set_auth_cookie( $user->ID );

				do_action( 'wp_login', $user->user_login, $user );
			}

			die( '<pre>User: ' . print_r( $user, true ) . '</pre>' );

			// TODO logged in, now what?
			// TODO add access token/secret to user meta
		}
	}

	public function oauth() {
		$method = strtoupper( $_SERVER['REQUEST_METHOD'] );

		$uri = implode( '/', array(
			get_bloginfo( 'url' ),
			kickpress_get_api_trigger(),
			sprintf( KICKPRESS_ACTION_SLUG . '/',
				$this->params['action'],
				$this->params['action_key']
			)
		) );

		$query = 'POST' == $method ? $_POST : $_GET;

		unset( $query['action'], $query['q'] );

		$meta = isset( $query['meta'] ) ? $query['meta'] : array();

		$args = compact( 'method', 'uri', 'query' );

		switch ( strtolower( $this->params['action_key'] ) ) {
		case 'request-token':
			kickpress_oauth_provider::request_token( $args );
			break;
		case 'access-token':
			kickpress_oauth_provider::access_token( $args );
			break;
		case 'authorize':
			kickpress_oauth_provider::authorize( $args );
			break;
		case 'register':
			global $wpdb, $wp_filter;

			if ( kickpress_is_remote_app() ) {
				$wp_filter['wpmu_signup_user_notification'] = array();
				add_filter( 'wpmu_signup_user_notification', '__return_false' );

				$user_login    = $_REQUEST['login'];
				$user_email    = $_REQUEST['email'];
				$user_password = $_REQUEST['password'];

				if ( empty( $user_login ) ) $user_login = $user_email;

				if ( empty( $user_password ) )
					die( kickpress_oauth::normalize_params( array(
						'oauth_error'      => __( 'Please enter a password.', 'kickpress' ),
						'oauth_error_type' => 'user_pass'
					) ) );

				$user_data = wpmu_validate_user_signup( $user_login, $user_email );
				// unset( $user_data['errors']->errors['user_email_used'] );

				if ( ! empty( $user_data['errors']->errors ) ) {
					$code    = $user_data['errors']->get_error_code();
					$message = $user_data['errors']->get_error_message();

					die( kickpress_oauth::normalize_params( array(
						'oauth_error'      => $message,
						'oauth_error_type' => $code
					) ) );
				}

				$user_login = apply_filters( 'pre_user_login',
					sanitize_user( stripslashes( $user_login ), true ) );

				$wp_filter['wpmu_welcome_user_notification'] = array();
				add_filter( 'wpmu_welcome_user_notification', '__return_false' );

				wpmu_signup_user( $user_login, $user_email, array(
					'add_to_blog' => $wpdb->blogid,
					'new_role'    => 'subscriber'
				) );

				$key = $wpdb->get_var( $wpdb->prepare(
					"SELECT activation_key " .
					"FROM {$wpdb->signups} " .
					"WHERE user_login = %s " .
					"AND user_email = %s",
					$user_login,
					$user_email
				) );

				// action hook handles first/last name?
				$user_data = wpmu_activate_signup( $key );

				if ( is_wp_error( $user_data ) ){
					$code    = $user_data['errors']->get_error_code();
					$message = $user_data['errors']->get_error_message();

					die( kickpress_oauth::normalize_params( array(
						'oauth_error'      => $message,
						'oauth_error_type' => $code
					) ) );
				}

				// change password
				wp_set_password( $user_password, $user_data['user_id'] );

				wp_set_current_user( $user_data['user_id'] );

				foreach ( $meta as $meta_key => $meta_value ) {
					add_user_meta( $user_data['user_id'], $meta_key, $meta_value );
				}

				if ( $app = kickpress_get_app() ) {
					do_action( 'kickpress_user_registered',
						$user_data['user_id'], $app->consumer_key );
				}

				$args['query']['authorize'] = 'yes';

				kickpress_oauth_provider::authorize( $args );
			}

			break;
		}

		exit;
	}

	public function update_meta_fields( $post, $post_data, $form_data ) {
		if ( ! is_null( $this->_handler ) ) {
			return $this->_handler->update_meta_fields( $post, $post_data, $form_data );
		} else {
			return $post_data;
		}
	}

	public function process_form_data( $form_data ) {
		$post_data = array();
		if ( is_array( $form_data ) ) {
			foreach ( $form_data as $post_type => $post_type_data ) {
				$post_data['post_type'] = $post_type;
				$this->params['post_type'] = $post_type;

				// Set the post type
				if ( !isset( $this->params['post_type'] ) )
					$this->params['post_type'] = $post_type;

				foreach ( $post_type_data as $post_id => $postdata ) {
					$post_data = array_merge($post_data, $postdata);
					// TODO: allow for an array of post ids
					// Set the post id
					if ( !isset( $this->params['id'] ) )
						$this->params['id'] = $post_id;

					// this returns custom fields from the original API,
					// not the current post type
					$fields = $this->get_custom_fields( true );

					//$this->validation->validate( 'post_title',   $fields['post_title'],   $post_data['post_title'] );
					//$this->validation->validate( 'post_content', $fields['post_content'], $post_data['post_content'] );

					$skip_post_vars = kickpress_post_vars();

					foreach ( $fields as $key => $field ) {
						$field_name = isset( $field['name'] ) ? $field['name'] : $key;

						if ( in_array( $field_name, $skip_post_vars ) ) continue;

						if ( isset( $post_data[$field_name] ) )
							$this->validation->validate( $field_name, $fields[$key], $post_data[$field_name] );
					}
				}
			}
		}

		return $post_data;
	}

	/**
	 * depends on the init_post_type_options in kickpress-core.php to have been run
	 *
	 * @uses	categories_input_to_array
	 * @uses	check_category
	 * @uses	tags_input_to_string
	 * @uses	insert_categories
	 * @uses	update_meta_fields
	 * @uses	process_form_data
	 */
	public function save( $redirect = true, $post_data = array() ) {
		global $wpdb, $post, $user_ID;
		$postdata = array();

		// Must process form data to retrieve the post_type and post id
		if ( empty( $post_data ) ) {
			$post_data = $this->process_form_data( $_POST['data'] );
		}

		// Determine whether action is adding a new post or updating an existing post
		if ( isset( $this->params['id'] ) && $this->params['id'] > 0 ) {
			$save_type = 'update';
			$post_id = $this->params['id'];
			$post = get_post($post_id);
			$post_type = $post->post_type;
		} else {
			$save_type = 'add';
			$post_id = 0;
			$post = (object)$post_data;
			$post_type = $post_data['post_type'];
		}

		// Remote API calls have their own way of validating nonce
		if ( ! kickpress_is_remote_app() ) {
			// Have to check the nonce after the post_type is set
			//if ( ! wp_verify_nonce( $_POST['kickpress_' . $post_type . '_wpnonce'], plugin_basename( __FILE__ ) ) ) return false;
		}

		// Set validation notes for postbacks
		$this->action_results[ 'messages' ][ $post_type ][ $post_id ] = array_merge( $this->action_results['messages'], $this->validation->notes );
		// $this->action_results[ 'data' ][ 'post_id' ] = $post_id;
// $this->action_results['data'][$post->ID]['source_id'] = $remote_post->source_id;
		// If there are errors on the submitted form, send the user the form again
		if ( isset( $this->validation->notes['error'] ) && count( $this->validation->notes['error'] ) ) {
			$this->action_results[ 'data' ] = $wpdb->escape( $_POST['data'] );
			$this->action_results[ 'status' ] = 'failure';

			return false;
		}

		// Go ahead and save the post
		require_once (ABSPATH . 'wp-admin/includes/taxonomy.php');

		// TODO: process nested loops

		$version_control = false;

		// Set post_title
		if ( isset( $post_data['post_title'] ) )
			$postdata['post_title'] = $post_data['post_title'];

		// Set post_content
		if ( isset( $post_data['post_content'] ) ) {
			$post_content = $post_data['post_content'];
			$post_content = preg_replace('|<(/?[A-Z]+)|e', "'<' . strtolower('$1')", $post_content);
			$post_content = str_replace('<br>', '<br />', $post_content);
			$post_content = str_replace('<hr>', '<hr />', $post_content);
			$postdata['post_content'] = $post_content;
		}

		// Set post_status
		if ( 'attachment' == $post_type ) {
			$postdata['post_status'] = 'inherit';
		} elseif ( isset( $post_data['post_status'] ) ) {
			$postdata['post_status'] = $post_data['post_status'];
		} elseif ( ! isset( $post->post_status ) ) {
			$postdata['post_status'] = 'publish';
		}

		// Set post_date
		if ( isset( $post_data['post_date'] ) ) {
			$postdata['post_date']      = date( 'Y-m-d H:i:s', strtotime( $post_data['post_date'] ) );
			$postdata['post_date_gmt']  = get_gmt_from_date( $postdata['post_date'] );
		} elseif ( ! isset( $post->post_date ) ) {
			$postdata['post_date']      = date( 'Y-m-d H:i:s' );
			$postdata['post_date_gmt']  = get_gmt_from_date( $postdata['post_date'] );
		}

		// Set post_category
		if ( isset( $post_data['post_category'] ) ) {
			$postdata['post_category'] = $post_data['post_category'];
		} else {
			$postdata['post_category'] = array();
		}

		// Merge categories_input with post_category
		if ( isset( $post_data['categories_input'] ) ) {
			$categories_input = $this->categories_input_to_array( $post_data['categories_input'] );
			$postdata['post_category'] = array_merge($postdata['post_category'], $categories_input);
		}

		// Set tags_input
		if ( isset( $post_data['tags_input'] ) )
			$postdata['tags_input'] = $this->tags_input_to_string( $post_data['tags_input'] );

		// Set tax_input
		if ( isset( $post_data['tax_input'] ) )
			$postdata['tax_input'] = $post_data['tax_input'];

		// Set post_type
		$postdata['post_type'] = $post_type;

		if ( $post_id == 0 ) {
			$postdata['post_name'] = sanitize_title_with_dashes($post_title);
			$postdata['post_excerpt'] = (isset($post_data['post_excerpt'])?$post_data['post_excerpt']:$post_content);
			$postdata['post_author'] = (isset($post_data['post_author'])?$post_data['post_author']:$user_ID);
			$postdata['post_parent'] = (isset($post_data['post_parent'])?$post_data['post_parent']:'0');
			$postdata['menu_order'] = (isset($post_data['menu_order'])?$post_data['menu_order']:'0');
			$postdata['post_password'] = (isset($post_data['post_password'])?$post_data['post_password']:'');
			$postdata['page_template'] = (isset($post_data['page_template'])?$post_data['page_template']:'');
			$postdata['comment_status'] = (isset($post_data['comment_status'])?$post_data['comment_status']:get_option('default_comment_status'));
		} else {
			if ( isset($post_data['post_name']) )
				$postdata['post_name'] = sanitize_title_with_dashes($post_data['post_name']);
			if ( isset($post_data['post_excerpt']) )
				$postdata['post_excerpt'] = $post_data['post_excerpt'];
			if ( isset($post_data['post_author']) )
				$postdata['post_author'] = $post_data['post_author'];
			if ( isset($post_data['post_parent']) )
				$postdata['post_parent'] = $post_data['post_parent'];
			if ( isset($post_data['menu_order']) )
				$postdata['menu_order'] = $post_data['menu_order'];
			if ( isset($post_data['post_password']) )
				$postdata['post_password'] = $post_data['post_password'];
			if ( isset($post_data['page_template']) )
				$postdata['page_template'] = $post_data['page_template'];
			if ( isset($post_data['comment_status']) )
				$postdata['comment_status'] = $post_data['comment_status'];
		}

		// determine correct capabilities for this post type
		if ( 'disable' == get_option( 'kickpress_use_post_type_cap', 'enable' ) ) {
			$create_posts  = 'create_posts';
			$edit_posts = 'edit_posts';
		} elseif ( in_array( $post_type, array( 'post', 'page' ) ) ) {
			$create_posts  = 'create_' . $post_type . 's';
			$edit_posts = 'edit_' . $post_type . 's';
		} else {
			$create_posts  = 'create_' . $post_type;
			$edit_posts = 'edit_' . $post_type;
		}

		// Check to see if this has already been added to the site.
		if ( (current_user_can( $create_posts ) || kickpress_anonymous_user_can( $create_posts )) && $post_id == null ) {
			if ( isset($post_status) && 'private' == $post_status ) {
				$postdata[ 'post_password' ] = md5(uniqid(mt_rand(), true));
				$postdata[ 'post_status' ] = 'publish';
			}
		} elseif ( current_user_can( $edit_posts ) || kickpress_anonymous_user_can ( $edit_posts ) ) {
			$postdata[ 'ID' ] = $post_id;
		} else {
			$this->action_results[ 'data' ][ 'post_id' ] = $post_id;
			$this->action_results[ 'status' ] = 'failure';
			return false;
		}

		if ( $post_id == 0 ) {
			$post_id = wp_insert_post( $postdata, true );
			$this->params['id'] = $post_id;
		} else {
			$post_id = wp_update_post( $postdata );
		}

		// Post Formats
		if ( isset( $post_data['post_format'] ) ) {
			if ( current_theme_supports( 'post-formats', $post_data['post_format'] ) )
				set_post_format( $post_id, $post_data['post_format'] );
		}

		if ( $post = get_post( $post_id ) ) {
			kickpress_process_custom_fields( $post, $post_data );

			if ( @is_array( $post_data['rel_input'] ) ) {
				foreach ( $post_data['rel_input'] as $slug => $ids ) {
					$rel_posts = kickpress_get_related_posts( $post_id, $slug );

					$skip_ids = array();

					foreach ( $rel_posts as $rel_post ) {
						//var_dump( $rel_post->ID );
						if ( in_array( $rel_post->ID, $ids ) )
							$skip_ids[] = $rel_post->ID;
						else
							kickpress_remove_related_post( $post_id, $slug, $rel_post );
					}

					foreach ( $ids as $rel_id ) {
						if ( ! in_array( $rel_id, $skip_ids ) )
							kickpress_add_related_post( $post_id, $slug, $rel_id );
					}

				}
			}

			// Set the featured image, if specified
			//if ( ! is_admin() ) {
			//	if ( isset( $data['_thumbnail_id'] ) && post_type_supports( $post_type, 'thumbnail' ) ) {
			//		$thumbnail = is_numeric( $data['_thumbnail_id'] ) ? (int) $data['_thumbnail_id'] : -1;
			//		update_post_meta( $post_id, '_thumbnail_id', $thumbnail );
			//	}
			//}
		}

		if ( ! empty( $_FILES['data'] ) ) {
			$files = array();

			foreach ( $_FILES['data'] as $file_attr => $file_array ) {
				foreach ( $file_array as $post_type => $post_array ) {
					foreach ( $post_array as $post_id => $field_array ) {
						foreach ( $field_array as $field_name => $field_value ) {
							$files[$post_type][$post_id][$field_name][$file_attr] = $field_value;
						}
					}
				}
			}

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';

			foreach ( $files as $post_type => $post_type_files ) {
				foreach ( $post_type_files as $post_id => $post_files ) {
					foreach ( $post_files as $field_name => $file_data ) {
						if ( in_array( $post_id, array( 0, $this->params['id'] ) ) && $file_data['error'] === UPLOAD_ERR_OK ) {
							$_FILES[$field_name] = $file_data;

							$attach_id = media_handle_upload( $field_name, $this->params['id'] );

							update_post_meta( $this->params['id'], $field_name, $attach_id );
						}
					}
				}
			}
		}

		unset( $_POST['data'], $_FILES['data'] );

		// redirect to form view w/ success message
		$current_action = $this->get_current_action();
		$this->action_results[ 'status' ] = 'success';
		$this->action_results[ 'messages' ][ $post->post_type ][ $post->ID ][ 'note' ] = __( 'The information has been saved', 'kickpress' );
		// $this->action_results[ 'data' ][ 'post_id' ] = $post->ID;
		$this->action_results[ 'data' ][ $post->post_type ][ $post->ID ] = (array)$post;
	}

	public function categories_input_to_array( $categories_input = array() ) {
		global $wpdb;
		$categories = array();
		$parent_cat_id = 0;
/*
		if ( $parent_cat_id = $wpdb->get_var($wpdb->prepare("SELECT term_id FROM $wpdb->terms WHERE slug = %s", $this->params['post_type'])) ) {
			if ( ! in_array($parent_cat_id, $categories) )
				$categories[] = $parent_cat_id;
		}

		if ( $post_type_cat_id = $this->check_category($this->params['post_type'], 0, $parent_cat_id) ) {
			if ( ! in_array($post_type_cat_id, $categories) )
				$categories[] = $post_type_cat_id;
		}
*/

		if ( count($categories_input) ) {

			if(!empty($categories_input['new'])){

				$new_categories = explode(',', $categories_input['new']);

				foreach ( $new_categories as $new_cat_name ) {
					$new_cat_name = $wpdb->escape(trim($new_cat_name));
					$cat_id = $this->check_category($new_cat_name, 0, $parent_cat_id);
					$categories_input[] = $cat_id;
				}
			}

			foreach ( $categories_input as $cat_name=>$cat_id ) {
				if ( ($cat_id != 0) && ! in_array($cat_id, $categories) )
					$categories[] = $cat_id;
			}
		}
		return $categories;
	}

	public function check_category($cat_name='', $cat_id=0, $parent_cat_id=0) {
		global $wpdb;

		//terms = term_id, name, slug, term_group
		//term_taxonomy = term_taxonomy_id, term_id, taxonomy, description, parent, count
		//term_relationships = object_id, term_taxonomy_id, term_order
		if ( $cat_id == 0 ) {
			$sql = "
				SELECT
					{$wpdb->prefix}terms.term_id
				FROM
					{$wpdb->prefix}terms
				INNER JOIN {$wpdb->prefix}term_taxonomy
					ON {$wpdb->prefix}term_taxonomy.taxonomy = 'category'
					AND {$wpdb->prefix}term_taxonomy.term_id = {$wpdb->prefix}terms.term_id
				WHERE
					{$wpdb->prefix}terms.slug = '$cat_name'";

			$cat_id = (integer) $wpdb->get_var($sql);
		}

		// Create a new category if one does not exist
		if ( $cat_id == 0 ) {
			$category_nicename = sanitize_title_with_dashes($cat_name);
			$cat_name = kickpress_make_readable($cat_name);
			$category_parent = $parent_cat_id;
			$posts_private = '0';
			$links_private = '0';
			$catarr = compact('category_nicename', 'category_parent', 'posts_private', 'links_private', 'posts_private', 'cat_name');
			$cat_id = wp_insert_category($catarr);
		}
		return $cat_id;
	}

	public function tags_input_to_string( $tags_input = array() ) {
		global $wpdb;
		$tags = array();

		if ( count($tags_input) ) {
			foreach ( $tags_input as $tag_name=>$tag_value ) {
				if ( 'new' == $tag_name ) {
					$new_tags = explode(',', $tag_value);
					foreach ( $new_tags as $new_tag_name ) {
						$new_tag_name = $wpdb->escape(trim($new_tag_name));
						if ( ! in_array($new_tag_name, $tags) )
							$tags[] = $new_tag_name;
					}
				} else {
					$tag_value = $wpdb->escape(trim($tag_value));
					if ( ! in_array($tag_value, $tags) )
						$tags[] = $tag_value;
				}
			}
		}

		return join( ',', array_map( 'trim', $tags ) );
	}

	public function insert_categories($post_id, $categories=array()) {
		// Add categories.
		$post_cats = array();
		if ( count($categories) ) {
			foreach ( $categories as $cat_name=>$cat_id ) {
				$post_cats[] = $this->check_category($cat_name, $cat_id);
			}
			wp_set_post_categories($post_id, $post_cats);
		}
	}

	public function redirect( $callback = null ) {
		global $kickpress_builtin_post_types;

		if ( empty( $callback ) ) return;

		$api_trigger = kickpress_get_api_trigger();

		if ( is_string( $callback ) ) $callback = trim( $callback );

		if ( preg_match( '|^http(s)?://.+|i', $callback, $match ) ) {
			$location = 'Location: ' . $callback;
		} else {
			$location = 'Location: ';
			$permalink = $this->params['id'] > 0 ? get_permalink( $this->params['id'] ) : null;
			$post_type = $this->params['post_type'];

			if ( 'single' == $callback && !empty( $permalink ) ) {
				$location .= $permalink;
			} elseif ( 'excerpt' == $callback && !empty( $permalink ) ) {
				if ( !empty( $this->params['view'] ) ) {
					$location .= $permalink . $api_trigger . '/' . $this->params['view'] . '/';
				} else {
					$location .= $permalink . $api_trigger . '/excerpt/';
				}
			} elseif ( 'form' == $callback ) {
				if ( !empty( $permalink ) ) {
					$location .= $permalink . $api_trigger . '/form/';
				} else {
					if ( 'post' == $post_type || 'any' == $post_type )
						$location .=  '/' . $api_trigger . '/form/post_type/post/';
					else
						$location .= '/' . $post_type . '/' . $api_trigger . '/form/';
				}
			} else {
				if ( ! in_array( $post_type, $kickpress_builtin_post_types ) && 'any' != $post_type ) {
					$location .= get_bloginfo( 'wpurl' ) . '/' . $post_type . '/';
				} elseif ( 'archive' != $callback ) {
					if ( isset( $this->_valid_views[$callback] ) ) {
						extract( $this->_valid_views[$callback] );

						if ( $single && !empty( $permalink ) )
							$location .= $permalink;
						else
							$location .= get_bloginfo( 'wpurl' ) . '/';

						$location .= $api_trigger . '/' . $slug . '/';
					}
				}
			}
		}

		if ( 'Location: ' == $location )
			$location .= get_bloginfo( 'wpurl' ) . '/';

		ob_clean();
		header( $location );
		exit();
	}

	// TODO: Cascading delete similar to cascading insert
	public function delete() {
		$query = new WP_Query( array(
			'name'      => $this->params['post_name'],
			'post_type' => $this->params['post_type']
		) );

		$post = array_shift( $query->posts );

		// die('<pre>'.var_export($post,true).'</pre>');

		if ( $post ) {
			$current_action = $this->get_current_action();
			$this->action_results[ 'status' ] = 'success';
			$this->action_results[ 'messages' ][ $post->post_type ][ $post->ID ][ 'note' ] = __( 'The information has been deleted', 'kickpress' );
			$this->action_results[ 'data' ][ 'post_id' ] = $post->ID;

			wp_trash_post( $post->ID );
		}
	}

	public function scan() {
		$this->params['quick_edit'] = false;
		//$this->params['action'] = 'edit';

		$args = array('view'=>'single', 'post_type'=>$this->params['post_type']);
		kickpress_loop_template( $args );
	}

	public function file() {
		$post = $this->select();
		ob_clean();
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Cache-control: private');
		header('Content-type: '.$post['Type']);
		header('Content-Disposition: attachment; filename='.$post['Name']);

		echo file_get_contents($post['Path']);
		exit;
	}

	public function init_api_version() {
		$api_version = 0;

		if ( isset( $_SERVER['HTTP_X_API_VERSION'] ) )
			$api_version = intval( $_SERVER['HTTP_X_API_VERSION'] );

		$this->params['api_version'] = $api_version > 0 ? $api_version : 1;
		$this->params['date_format'] = $api_version >= 2 ? 'Y-m-d H:i:s O' : DATE_RSS;
	}

	public function import() {
		$action = $this->params['action'];
		$action_key = $this->params['action_key'];
		$format = isset( $this->params['format'] ) ? $this->params['format'] : 'json';

		if ( empty( $action_key ) && is_user_logged_in() ) {
			$func = array( $this, "import_{$format}" );
			$args = $_REQUEST['data'];

			if ( is_callable( $func ) )
				$posts = call_user_func( $func, $args );
			else return;

			$post_type = $this->params['post_type'];
			$this->action_results['data'][$post_type] = array();
			$this->action_results['messages'][$post_type] = array();

			foreach ( $posts as &$remote_post ) {
				if ( 0 < $remote_post->ID ) {
					if ( $old_post = get_post( $remote_post->ID ) ) {
						$old_mod_date = strtotime( $old_post->post_modified );
						$new_mod_date = strtotime( $remote_post->post_modified );

						// keep old post, skip ahead
						if ( $new_mod_date <= $old_mod_date ) {
							$this->action_results['messages'][$post_type][$remote_post->ID] = __( 'Post not updated, local modified date is newer than remote modified date.', 'kickpress' );
							$this->action_results['data'][$post_type][$remote_post->ID] = array(
								'ID'                    => $remote_post->ID,
								'local_post_modified'   => $old_post->post_modified,
								'remote_post_modified'  => $remote_post->post_modified
							);
							continue;
						}
					}
				}

				if ( 'trash' == $remote_post->post_status ) {
					wp_trash_post( $remote_post->ID );

					$this->action_results['messages'][$post_type][$remote_post->ID] = __( 'Post Deleted', 'kickpress' );
					$this->action_results['data'][$post_type][$remote_post->ID] = array(
						'ID'           => $remote_post->ID,
						'post_status'  => 'deleted'
					);

					continue;
				}

				$this->params['id'] = $remote_post->ID;

				if ( !empty( $remote_post->remote_id ) )
					$this->action_results['data'][$post_type][$remote_post->ID]['remote_id'] = $remote_post->remote_id;

				if ( !empty( $remote_post->mobile_id ) )
					$this->action_results['data'][$post_type][$remote_post->ID]['mobile_id'] = $remote_post->mobile_id;

				if ( !empty( $remote_post->source_id ) )
					$this->action_results['data'][$post_type][$remote_post->ID]['source_id'] = $remote_post->source_id;

				$post_data = (array)$remote_post;

				if ( empty( $post_data['post_type'] ) )
					$post_data['post_type'] = $post_type;

				if ( empty( $post_data['post_date'] ) )
					$post_data['post_date'] = gmdate( DATE_RSS );

				if ( empty( $post_data['post_modified'] ) )
					$post_data['post_modified'] = $post_data['post_date'];

				// this returns custom fields from the original API,
				// not the current post type
				$fields = $this->get_custom_fields( true );
				$skip_post_vars = kickpress_post_vars();

				foreach ( $fields as $key => $field ) {
					$field_name = isset( $field['name'] ) ? $field['name'] : $key;

					if ( in_array( $field_name, $skip_post_vars ) ) continue;

					if ( isset( $post_data[$field_name] ) )
						$this->validation->validate( $field_name, $fields[$key], $post_data[$field_name] );
				}

				$this->save( false, $post_data );
			}
		}
	}

	private function import_json( $json ) {
		header( 'Content-type: application/json' );

		return json_decode( stripslashes( $json ) );
	}

	private function import_xml( $xml ) {
		header( 'Content-Type: text/xml; charset=UTF-8' );

		$sxe = simplexml_load_string( stripslashes( $xml ) );

		$post = array();

		// 'ID','post_author','post_date','post_date_gmt','post_content','post_title','post_category','post_excerpt','post_status','comment_status','ping_status','post_password','post_name','to_ping','pinged','post_modified','post_modified_gmt','post_content_filtered','post_parent','guid','menu_order','post_type','post_mime_type','comment_count','tags_input','categories_input','_thumbnail_id'

		foreach ( $sxe->post as $node ) {
			$post[] = (object) array(
				'ID'             => intval( $node['post_id'] ),
				'post_author'    => (string) $node->post_author,
				'post_date'      => (string) $node->post_date,
				'post_content'   => trim( (string) $node->post_content ),
				'post_title'     => trim( (string) $node->post_title ),
				'post_status'    => (string) $node->post_status,
				'post_name'      => (string) $node->post_name,
				'post_type'      => (string) $node->post_type
			);
		}

		return $post;
	}

	public function data() {
		if ( isset( $this->params['action_key'] ) )
			$data_action = $this->params['action_key'];
		else
			$data_action = 'export';
		$method = strtolower( $data_action ) . '_data';

		if ( method_exists( $this, $method ) && is_user_logged_in() ) {
			call_user_func( array( $this, $method ) );
		}
	}

	public function export_data() {
		global $post, $wp_query;
		// error_log('QUERY: ' . print_r($wp_query, TRUE), 0);
		$page = get_query_var( 'paged' );
		$page = ! empty( $page ) ? intval( $page ) : 1;

		$post_data = array(
			'total_posts' => $wp_query->found_posts,
			'page' => $page,
			'data' => array()
		);
		$meta_fields = array();
		$taxonomies = array();
		while ( have_posts() ) {
			the_post();
			$post_type = $post->post_type;
		    // Get post type taxonomies
			if ( empty( $taxonomies[$post_type] ) ) {
				$taxonomies[$post_type] = get_object_taxonomies( $post_type, 'objects' );
				/*echo '<pre>';
				print_r($taxonomies[$post_type]);
				echo '</pre>';*/
			}
			if ( empty( $post_data['data'][$post_type] ) ) {
				$post_data['data'][$post_type] = array();
			}
			$post_array = (array)$post;
			unset( $post_array['comment_status'], $post_array['ping_status'], $post_array['post_password'], $post_array['to_ping'], $post_array['pinged'], $post_array['post_content_filtered'], $post_array['post_parent'], $post_array['menu_order'], $post_array['post_mime_type'], $post_array['comment_count'], $post_array['filter'] );
			$post_array['author_meta'] = array(
				'ID'           => get_the_author_meta( 'ID' ),
				'display_name' => get_the_author_meta( 'display_name' ),
				'user_email'   => get_the_author_meta( 'user_email' )
			);

			if ( $image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ), 'large' ) ) {
				$post_array['featured_image'] = $image[0];
			}
			if ( $post_format = get_post_format( $post->ID ) ) {
				$post_array['post_format'] = $post_format;
			}
			if ( empty( $meta_fields[ $post_type ] ) ) {
				if ( $post_type != $this->params['post_type'] ) {
					$temp_api = kickpress_init_api( $post_type );
					$meta_fields[ $post_type ] = $temp_api->get_custom_fields( false );
					unset($temp_api);
				} else {
					$meta_fields[ $post_type ] = $this->get_custom_fields( false );
				}
			}

			foreach ( $meta_fields[ $post_type ] as $field_name => $field_meta ) {
				$meta_value = get_post_meta( get_the_ID(), $field_meta['name'], true );
				if ( is_string( $meta_value ) && $field_meta['exportable'] )
					$post_array[$field_meta['name']] = $meta_value;
			}
		    if ( !empty( $taxonomies[$post_type] ) ) {
			    $post_array['tax_input'] = array();
			    foreach ( $taxonomies[$post_type] as $taxonomy_slug => $taxonomy ) {
					$protected = array( 'post_format' );
					if ( !in_array( $taxonomy_slug, $protected ) ) {
						// Get the terms related to post
						if ( $terms = get_the_terms( $post->ID, $taxonomy_slug ) ) {
							if ( ! isset( $post_array['taxonomy'] ) ) {
								$post_array['taxonomy'] = array();
							}
							$post_array['taxonomy'][$taxonomy_slug] = array(
								'taxonomy_id' => null,
								'taxonomy_slug' => $taxonomy_slug,
								'taxonomy_label' => $taxonomy->label,
								'terms' => array()
							);
							foreach ( $terms as $term ) {
								$post_array['taxonomy'][$taxonomy_slug]['taxonomy_id'] = $term->term_taxonomy_id;
								$post_array['taxonomy'][$taxonomy_slug]['terms'][$term->slug] = array(
									'term_id'          => $term->term_id,
									'name'             => $term->name,
									'slug'             => $term->slug
								);
								if ( 'category' == $taxonomy_slug ) {
									if ( ! isset( $post_array['post_category'] ) ) {
										$post_array['post_category'] = array();
									}
									$post_array['post_category'][$term->term_id] = $term->name;
								} elseif ( 'post_tag' == $taxonomy_slug ) {
									if ( ! isset( $post_array['tax_input']['post_tag'] ) ) {
										$post_array['tax_input']['post_tag'] = '';
									}
									// Convert tax_input into a comma delimited string for WordPress to handle
									$post_array['tax_input']['post_tag'] .= ', ' . $term->name;
								} else {
									if ( ! isset( $post_array['tax_input'][$taxonomy_slug] ) ) {
										$post_array['tax_input'][$taxonomy_slug] = array();
									}

									$post_array['tax_input'][$taxonomy_slug][$term->term_id] = $term->name;
								}

							} // End foreach
							// Trim the end and beginning commas
							$post_array['tax_input']['post_tag'] = trim($post_array['tax_input']['post_tag'], ', ');
						}
					}
			    }
		    }

			$post_data['data'][$post_type][$post->ID] = $post_array;
		}
		ob_clean();
		header( 'Content-type: application/json' );
		if ( isset( $_GET['callback'] ) )
			echo $_GET['callback'] . '(';
		echo json_encode( $post_data );
		if ( isset( $_GET['callback'] ) )
			echo ');';

		exit;
	}

	public function import_data() {
		if ( (current_user_can( 'create_posts' ) || kickpress_anonymous_user_can( 'create_posts' )) ||
			(current_user_can( 'edit_posts' ) || kickpress_anonymous_user_can( 'edit_posts' )) ) {

			$incoming_posts = json_decode( stripslashes( $_REQUEST['data'] ), true );

			foreach ( $incoming_posts as $post_type => $posts ) {
				if ( ! isset( $this->action_results['data'][$post_type] ) ) {
					$this->action_results['data'][$post_type] = array();
					$this->action_results['messages'][$post_type] = array();
				}

				foreach ( $posts as $post_id => $remote_post ) {
					if ( empty( $remote_post['ID'] ) && 0 < $post_id ) {
						$remote_post['ID'] = $post_id;
					}

					if ( empty( $remote_post['post_type'] ) ) {
						$remote_post['post_type'] = $post_type;
					}

					if ( empty( $remote_post['post_date'] ) ) {
						$remote_post['post_date'] = gmdate('Y-m-d H:i:s');
					}

					if ( empty( $remote_post['post_modified'] ) ) {
						$remote_post['post_modified'] = $remote_post['post_date'];
					}

					if ( 'trash' == $remote_post['post_status'] ) {
						wp_trash_post( $remote_post['ID'] );
						$this->action_results['messages'][$post_type][$remote_post['ID']] = __( 'Post Deleted', 'kickpress' );
						$this->action_results['data'][$post_type][$remote_post['ID']] = array(
							'ID'           => $remote_post['ID'],
							'post_status'  => 'deleted'
						);
						continue;
					}

					if ( isset( $remote_post['ID'] ) ) {
						$this->params['id'] = $remote_post['ID'];

						if ( !empty( $remote_post['remote_id'] ) )
							$this->action_results['data'][$post_type][$remote_post['ID']]['remote_id'] = $remote_post['remote_id'];

						if ( !empty( $remote_post['mobile_id'] ) )
							$this->action_results['data'][$post_type][$remote_post['ID']]['mobile_id'] = $remote_post['mobile_id'];

						if ( !empty( $remote_post['source_id'] ) )
							$this->action_results['data'][$post_type][$remote_post['ID']]['source_id'] = $remote_post['source_id'];
					}

					$post_data = (array) $remote_post;

					if ( ! empty( $remote_post['post_category'] ) ) {
						// If slug is passed in, convert slug to term_id
						if ( is_array( $post_data['post_category'] ) ) {
							foreach ( $post_data['post_category'] as $term_key => $term_value ) {
								if ( is_string( $term_value ) ) {
									if ( $temp_term = get_term_by( 'slug', $term_value, 'category' ) ) {
										$post_data['post_category'][$term_key] = $temp_term->term_id;
									} elseif ( $temp_term = get_term_by( 'slug', sanitize_title_with_dashes( $term_value ), 'category') ) {
										$post_data['post_category'][$term_key] = $temp_term->term_id;
									}
								}
							}
						}
						//<input value="5289" type="checkbox" name="tax_input[country][]" id="in-country-5289" checked="checked">
					}

					if ( ! empty( $remote_post['tax_input'] ) ) {
						foreach ( $remote_post['tax_input'] as $taxonomy_name => $taxonomy ) {
							if ( 'post_tag' != $taxonomy_name ) {
								// If slug is passed in, convert slug to term_id
								if ( is_array( $post_data['tax_input'][$taxonomy_name] ) ) {
									foreach ( $post_data['tax_input'][$taxonomy_name] as $term_key => $term_value ) {
										if ( is_string( $term_value ) ) {
											if ( $temp_term = get_term_by( 'slug', $term_value, $taxonomy_name ) ) {
												$post_data['tax_input'][$taxonomy_name][$term_key] = $temp_term->term_id;
											}
										}
									}
								}
								//<input value="5289" type="checkbox" name="tax_input[country][]" id="in-country-5289" checked="checked">
							}
						} // end foreach
					}

					$this->save( false, $post_data );
				}
			}

			ob_clean();
			header( 'Content-type: application/json' );
			$this->serialize_results();
			exit;
		}
	}

	public function taxonomy_data() {
		global $kickpress_post_types;
		// error_log('QUERY: ' . print_r($wp_query, TRUE), 0);
		$data_array = array('data' => array());

		$taxonomies = array();

		$find_taxonomies = array(
		    'category',
		    'post_tag',
		    'country'
		);

		if ( 'any' == $this->params['post_type'] ) {
			$protected = array('post','any');
			foreach ( $kickpress_post_types as $post_type_key => $post_type_values ) {
				if ( in_array($post_type_key, $protected) ) { continue; }
				$find_taxonomies[] = $post_type_key . '-category';
			}
		} elseif ( 'post' != $this->params['post_type'] ) {
			$find_taxonomies[] = $this->params['post_type'] . '-category';
		}

		$terms = get_terms( $find_taxonomies, $args );

		foreach( $terms as $term_id => $term ) {
			if ( ! isset( $data_array['data'][$term->taxonomy] ) ) {
				$data_array['data'][$term->taxonomy] = array();
			}

			$data_array['data'][$term->taxonomy][$term->slug] = array(
				'term_id'          => $term->term_id,
				'name'             => $term->name,
				'slug'             => $term->slug,
				'parent'           => $term->parent,
				'count'            => $term->count
			);
		}

		//error_log('Taxonomy: ' . print_r($data_array], TRUE), 0);

		ob_clean();
		header( 'Content-type: application/json' );
		if ( isset( $_GET['callback'] ) )
			echo $_GET['callback'] . '(';
		echo json_encode( $data_array );
		if ( isset( $_GET['callback'] ) )
			echo ');';

		exit;
	}

	public function current_user_data() {
		$user = wp_get_current_user();
		$data_array = array(
			'data' => array(
				'ID'              => $user->ID,
				'user_login'      => $user->data->user_login,
				'user_email'      => $user->data->user_email,
				'user_registered' => $user->data->user_registered,
				'user_status'     => $user->data->user_status,
				'display_name'    => $user->data->display_name,
				'roles'           => $user->roles
			)
		);

		if ( is_super_admin() ) {
			$data_array['data']['roles'][] = 'administrator';
		}

		ob_clean();
		header( 'Content-type: application/json' );
		if ( isset( $_GET['callback'] ) )
			echo $_GET['callback'] . '(';
		echo json_encode( $data_array );
		if ( isset( $_GET['callback'] ) )
			echo ');';

		exit;
	}

	public function users_data() {
		$data_array = array('data' => array());

		// For now, limit this to authors and the current blog id,
		// later we can change it to handle more users and blogs.
		$args = array(
			'blog_id'      => $GLOBALS['blog_id'],
			'role'         => 'author'
		 );

		$users = get_users( $args );

		foreach( $users as $user ) {
			if ( ! isset( $data_array['data'][$user->ID] ) ) {
				$data_array['data'][$user->ID] = array();
			}
			//echo '<pre>'; print_r($user); echo '</pre>'; die();

			$data_array['data'][$user->ID] = array(
				'ID'              => $user->ID,
				'user_login'      => $user->user_login,
				'user_email'      => $user->user_email,
				'user_registered' => $user->user_registered,
				'user_status'     => $user->user_status,
				'display_name'    => $user->display_name,
				'first_name'      => $user->first_name,
				'last_name'       => $user->last_name
			);
		}

		ob_clean();
		header( 'Content-type: application/json' );
		if ( isset( $_GET['callback'] ) )
			echo $_GET['callback'] . '(';
		echo json_encode( $data_array );
		if ( isset( $_GET['callback'] ) )
			echo ');';

		exit;
	}

	public function export() {
		$this->init_api_version();

		if ( isset( $this->params['format'] ) )
			$format = $this->params['format'];
		elseif ( isset( $this->params['action_key'] ) )
			$format = $this->params['action_key'];
		else
			$format = 'json';

		$method = 'export_' . strtolower( $format );

		if ( method_exists( $this, $method ) )
			call_user_func( array( $this, $method ) );

		exit;
	}

	private function export_node() {
		$meta_fields = $this->get_custom_fields( false );

		if ( $image = wp_get_attachment_image_src( get_post_thumbnail_id( get_the_ID() ) ) )
			$image_source = $image[0];
		else
			$image_source = '';

		$node = (object) array(
			'ID'      => get_the_ID(),
			'type'    => get_post_type(),
			'title'   => get_the_title(),
			'link'    => get_permalink(),
			'date'    => get_the_date( $this->params['date_format'] ),
			'author'  => (object) array(
				'name'  => get_the_author_meta( 'display_name' ),
				'email' => get_the_author_meta( 'user_email' ),
				'url'   => get_the_author_meta( 'user_url' )
			),
			'content' => get_the_content(),
			'excerpt' => get_the_excerpt(),
			'image'   => $image_source, // do we need this?
			'meta'    => array()
		);

		foreach ( $meta_fields as $field_name => $field_meta ) {
			$meta_value = get_post_meta( get_the_ID(), $field_meta['name'], true );

			if ( is_string( $meta_value ) && $field_meta['exportable'] )
				$node->meta[$field_name] = $meta_value;
		}

		return $node;
	}

	private function export_debug() {
		global $wp_query;

		header( 'Content-Type: text/plain' );

		print_r( $wp_query->request );
	}

	private function export_csv() {
		$filename = $this->params['post_type'] . '-' . date('Y-m-d');

		$cols = array(
			'title',
			'link',
			'date',
			'author',
			'content',
			'excerpt'
		);

		$fields = $this->get_custom_fields( false );

		$cols = array_merge( $cols, array_keys( $fields ) );
		$rows = array( implode( ',', $cols ) );

		while ( have_posts() ) {
			the_post();

			$row = array(
				'title'   => get_the_title(),
				'link'    => get_permalink(),
				'date'    => get_the_date( DATE_RSS ),
				'author'  => get_the_author(),
				'content' => get_the_content(),
				'excerpt' => get_the_excerpt()
			);

			foreach ( $fields as $field_name => $field_meta ) {
				$meta_value = get_post_meta( get_the_ID(), $field_meta['name'], true );
				$row[$field_name] = is_string( $meta_value ) ? $meta_value : null;
			}

			foreach ( $row as $key => $value ) {
				if ( preg_match( '/\\r|\\n|,|"/', $value ) )
					$row[$key] = '"' . str_replace( '"', '""', $value ) . '"';
			}

			$rows[] = implode( ',', $row );
		}

		header( 'Content-type: text/csv' );
		header( "Content-disposition: attachment; filename={$filename}.csv" );

		echo implode( PHP_EOL, $rows );
	}

	private function export_json() {
		$json_array = array();

		while ( have_posts() ) {
			the_post();

			$json_array['data'][] = $this->export_node();
		}

		header( 'Content-type: application/json' );

		if ( isset( $_GET['callback'] ) )
			echo $_GET['callback'] . '(';

		echo json_encode( $json_array );

		if ( isset( $_GET['callback'] ) )
			echo ');';
	}

	private function export_xml() {
		$meta_fields = $this->get_custom_fields( false );

		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->preserveWhiteSpace = true;
		$doc->formatOutput = true;

		$root = $doc->appendChild( $doc->createElement( 'data' ) );

		while ( have_posts() ) {
			the_post();

			$node = $this->export_node();

			$post = $root->appendChild( $doc->createElement( 'post' ) );
			$post->setAttribute( 'id',   $node->ID );
			$post->setAttribute( 'type', $node->type );
			$post->appendChild( $doc->createElement( 'title', $node->title ) );
			$post->appendChild( $doc->createElement( 'link',  $node->link ) );
			$post->appendChild( $doc->createElement( 'date',  $node->date ) );

			$author = $post->appendChild( $doc->createElement( 'author' ) );
			$author->appendChild( $doc->createElement( 'name',  $node->author->name ) );
			$author->appendChild( $doc->createElement( 'email', $node->author->email ) );
			$author->appendChild( $doc->createElement( 'url',   $node->author->url ) );

			$content = $post->appendChild( $doc->createElement( 'content' ) );
			$content->appendChild( $doc->createCDATASection( $node->content ) );

			$excerpt = $post->appendChild( $doc->createElement( 'excerpt' ) );
			$excerpt->appendChild( $doc->createCDATASection( $node->excerpt ) );

			$meta = $post->appendChild( $doc->createElement( 'meta' ) );

			foreach ( $node->meta as $meta_key => $meta_value ) {
				$meta_elem = $meta->appendChild( $doc->createElement( $meta_key ) );
				$meta_elem->appendChild( $doc->createCDATASection( $meta_value ) );
			}

			$comments = get_comments( array(
				'post_id' => get_the_ID()
			) );

			if ( ! empty( $comments ) ) {
				$comment_list = $post->appendChild( $doc->createElement( 'comments' ) );

				foreach ( $comments as $comment ) {
					$comment_element = $comment_list->appendChild( $doc->createElement( 'comment' ) );
					$comment_element->appendChild( $doc->createElement( 'date', date( DATE_RSS, strtotime( $comment->comment_date ) ) ) );

					$author = $comment_element->appendChild( $doc->createElement( 'author' ) );
					$author->appendChild( $doc->createElement( 'name',  $comment->comment_author ) );
					$author->appendChild( $doc->createElement( 'email', $comment->comment_author_email ) );
					$author->appendChild( $doc->createElement( 'url',   $comment->comment_author_url ) );

					$content = $comment_element->appendChild( $doc->createElement( 'content' ) );
					$content->appendChild( $doc->createCDATASection( $comment->comment_content ) );
				}
			}
		}

		header( 'Content-Type: text/xml; charset=UTF-8' );

		echo $doc->saveXML();
	}

	private function export_rss() {
		$api_trigger = get_option( 'kickpress_api_trigger', 'api' );

		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->preserveWhiteSpace = true;
		$doc->formatOutput = true;

		$rss = $doc->appendChild( $doc->createElement( 'rss' ) );
		$rss->setAttribute( 'version', '2.0' );

		$channel = $rss->appendChild( $doc->createElement( 'channel' ) );
		$channel->appendChild( $doc->createElement( 'title',   get_bloginfo( 'name' ) ) );
		$channel->appendChild( $doc->createElement( 'link',    get_bloginfo( 'url' ) ) );
		$channel->appendChild( $doc->createElement( 'pubDate', date( DATE_RSS ) ) );

		$description = $channel->appendChild( $doc->createElement( 'description' ) );
		$description->appendChild( $doc->createCDATASection( get_bloginfo( 'description' ) ) );

		while ( have_posts() ) {
			the_post();

			$author = get_the_author_meta( 'user_email' )
			        . ' (' . get_the_author_meta( 'display_name' ) . ')';

			$post = $channel->appendChild( $doc->createElement( 'item' ) );
			$post->appendChild( $doc->createElement( 'guid',    get_permalink() ) );
			$post->appendChild( $doc->createElement( 'title',   get_the_title() ) );
			$post->appendChild( $doc->createElement( 'link',    get_permalink() ) );
			$post->appendChild( $doc->createElement( 'author',  $author ) );
			$post->appendChild( $doc->createElement( 'pubDate', get_the_date( DATE_RSS ) ) );
			$post->appendChild( $doc->createElement( 'comments', get_permalink() . $api_trigger . '/export-comments[xml]' ) );

			$description = $post->appendChild( $doc->createElement( 'description' ) );
			$description->appendChild( $doc->createCDATASection( get_the_content() ) );
		}

		header( 'Content-Type: text/xml; charset=UTF-8' );

		echo $doc->saveXML();
	}

	public function export_comments() {
		$this->init_api_version();

		$action = $this->params['action'];
		$action_key = $this->params['action_key'];
		$format = isset( $this->params['format'] )
		        ? $this->params['format'] : 'json';

		$post_id   = intval( $this->params['id'] );
		$post_type = $this->params['post_type'];

		if ( in_array( $action_key, array( 'bookmarks', 'notes', 'tasks' ) ) ) {
			$args = array();

			if ( $post_id > 0 )
				$args['post_id'] = $post_id;

			$extra = $this->params['extra'];

			for ( $i = 0, $n = count( $extra ); $i < $n; $i++ ) {
				if ( 'karma' == $extra[ $i ] ) {
					$args['karma'] = intval( $extra[ $i + 1 ] );

					$i++;
				} elseif ( preg_match( '/^meta\[(\w*)\]$/', $extra[ $i ], $match ) ) {
					$args['meta_query'][] = array(
						'key'   => $match[ 1 ],
						'value' => $extra[ $i + 1 ]
					);

					$i++;
				} elseif ( preg_match( '/^meta~(\w*)$/', $extra[ $i ], $match ) ) {
					$args['meta_query'][] = array(
						'key'   => $match[ 1 ],
						'value' => $extra[ $i + 1 ]
					);

					$i++;
				}
			}

			if ( ! empty( $args['meta_query'] ) )
				$args['meta_query']['relation'] = 'OR';

			$comments = call_user_func( 'kickpress_get_' . $action_key, $args );

			if ( isset( $_REQUEST['date'] ) ) {
				$min_date = strtotime( $_REQUEST['date'] );

				foreach ( $comments as $index => $comment ) {
					$date     = strtotime( $comment->comment_date );
					$modified = strtotime( $comment->comment_modified );

					if ( $date < $min_date && $modified < $min_date )
						unset( $comments[$index] );
				}
			}
		} else {
			if ( empty( $post_id ) ) exit;

			$comments = get_comments( array(
				'post_id' => $post_id,
				'status'  => 'approve'
			) );

			$trees = array();
			$nodes = array();

			foreach ( $comments as &$comment ) {
				$nodes[$comment->comment_ID] =& $comment;
				$nodes[$comment->comment_ID]->comments = array();
			}

			unset( $comments, $comment );

			ksort( $nodes );

			foreach ( $nodes as &$node ) {
				if ( 0 == $node->comment_parent ) $trees[$node->comment_ID] =& $node;
				else $nodes[$node->comment_parent]->comments[$node->comment_ID] =& $node;
			}

			$comments = $trees;
		}

		ob_end_clean();

		$method = 'export_comments_' . strtolower( $format );

		if ( method_exists( $this, $method ) )
			call_user_func( array( $this, $method ), $comments );

		exit;
	}

	private function export_comment_node( $comment ) {
		$node = (object) array(
			'id'      => intval( $comment->comment_ID ),
			'post_id' => intval( $comment->comment_post_ID ),
			'date'    => date_i18n( $this->params['date_format'],
				strtotime( $comment->comment_date ) )
		);

		if ( isset( $comment->comment_modified ) )
			$node->modified = date_i18n( $this->params['date_format'],
				strtotime( $comment->comment_modified ) );

		$node->author = (object) array(
			'name'  => $comment->comment_author,
			'email' => $comment->comment_author_email,
			'url'   => $comment->comment_author_url
		);

		if ( isset( $comment->comment_title ) )
			$node->title = $comment->comment_title;

		$node->content  = $comment->comment_content;
		$node->karma    = intval( $comment->comment_karma );
		$node->meta     = $comment->comment_meta;
		$node->comments = array();

		if ( ! empty( $comment->comments ) ) {
			foreach ( $comment->comments as $child_comment ) {
				$node->comments[] = $this->export_comment_node( $child_comment );
			}
		}

		return $node;
	}

	private function export_comments_json( $comments ) {
		$json_array = array();

		foreach ( $comments as $comment ) {
			$json_array[] = $this->export_comment_node( $comment );
		}

		header( 'Content-type: application/json' );
		// header( "Content-disposition: attachment; filename={$filename}.json" );

		if ( isset( $_GET['callback'] ) )
			echo $_GET['callback'] . '(';

		echo json_encode( $json_array );

		if ( isset( $_GET['callback'] ) )
			echo ');';
	}

	private function export_comments_xml( $comments ) {
		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->preserveWhiteSpace = true;
		$doc->formatOutput = true;

		$root = $doc->appendChild( $doc->createElement( 'comments' ) );

		foreach ( $comments as $comment ) {
			$node = $this->export_comment_node( $comment );
			$node = $this->export_comment_node_xml( $node );
			$root->appendChild( $doc->importNode( $node, true ) );
		}

		header( 'Content-Type: text/xml; charset=UTF-8' );

		echo $doc->saveXML();
	}

	private function export_comment_node_xml( $node ) {
		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->preserveWhiteSpace = true;
		$doc->formatOutput = true;

		$root = $doc->appendChild( $doc->createElement( 'comment' ) );
		$root->setAttribute( 'id', $node->id );
		$root->setAttribute( 'post_id', $node->post_id );

		/* if ( 0 < $comment->comment_parent )
			$root->setAttribute( 'parent_id', $comment->comment_parent ); */

		$root->appendChild( $doc->createElement( 'date', $node->date ) );

		if ( isset( $comment->comment_modified ) )
			$root->appendChild( $doc->createElement( 'modified', $node->modified ) );

		$author = $root->appendChild( $doc->createElement( 'author' ) );
		$author->appendChild( $doc->createElement( 'name',  $node->author->name ) );
		$author->appendChild( $doc->createElement( 'email', $node->author->email ) );
		$author->appendChild( $doc->createElement( 'url',   $node->author->url ) );

		if ( isset( $node->title ) ) {
			$title = $root->appendChild( $doc->createElement( 'title' ) );
			$title->appendChild( $doc->createCDATASection( $node->title ) );
		}

		$content = $root->appendChild( $doc->createElement( 'content' ) );
		$content->appendChild( $doc->createCDATASection( $node->content ) );

		if ( ! empty( $node->comments ) ) {
			$children = $root->appendChild( $doc->createElement( 'comments' ) );

			foreach ( $node->comments as $child_comment ) {
				$node = $this->export_comment_node_xml( $child_comment );
				$children->appendChild( $doc->importNode( $node, true ) );
			}
		}

		return $root;
	}

	public function import_comments() {
		$action = $this->params['action'];
		$action_key = $this->params['action_key'];
		$format = isset( $this->params['format'] )
		        ? $this->params['format'] : 'json';

		$post_id   = $this->params['id'];
		$post_type = $this->params['post_type'];

		$func = array( $this, "import_comments_{$format}" );
		$args = $_REQUEST['data'];

		if ( is_callable( $func ) )
			$new_comments = call_user_func( $func, $args );
		else return;

		$comments = array(
			'old' => array(),
			'new' => array()
		);

		foreach ( $new_comments as &$comment ) {
			if ( empty( $comment->date ) )
				$comment->date = gmdate( DATE_RSS );

			if ( empty( $comment->modified ) )
				$comment->modified = $comment->date;

			if ( empty( $comment->post_id ) )
				$comment->post_id = $post_id;

			if ( 0 < $comment->id )
				$comments[$comment->id]['new'] =& $comment;
			else
				$comments['new'][] =& $comment;
		}

		unset ( $comment );

		if ( empty( $action_key ) && is_user_logged_in() ) {
			$this->action_results['messages']['comments'] = array();
			$this->action_results['data']['comments'] = array();

			foreach ( $comments['new'] as $comment ) {
				$this->import_comment( $comment );
			}

			unset( $comments['new'], $comments['old'] );

			foreach ( $comments as $comment_id => &$comment ) {
				if ( $old_comment = get_comment( $comment_id ) )
					$comment['old'] = $old_comment;

				if ( ! isset( $comment['new'] ) ) {
					// no new comment, skip ahead
					continue;
				} elseif ( ! isset( $comment['old'] ) ) {
					// no old comment, carry on
				} else {
					// wrong user, skip ahead
					if ( $comment['old']->user_id != get_current_user_id() )
						continue;

					$old_mod_date = strtotime( $comment['old']->modified );
					$new_mod_date = strtotime( $comment['new']->modified );

					// keep old comment, skip ahead
					if ( $new_mod_date <= $old_mod_date )
						continue;
				}

				$this->import_comment( $comment['new'] );
			}

			echo json_encode( $this->action_results['data']['comments'] );
		} elseif ( in_array( $action_key, array( 'bookmarks', 'notes', 'tasks' ) ) ) {
			$old_comments = call_user_func( "kickpress_get_{$action_key}" );

			foreach ( $old_comments as &$comment ) {
				$comment = $this->export_comment_node( $comment );
				$comments[$comment->id]['old'] =& $comment;
			}

			unset( $comment );

			$type = substr( $action_key, 0, -1 );
			$func = array( $this, "import_{$type}" );

			if ( is_callable( $func ) ) {
				$this->action_results['messages'][$action_key] = array();
				$this->action_results['data'][$action_key] = array();

				foreach ( $comments['new'] as $comment ) {
					call_user_func( $func, $comment );
				}

				unset( $comments['new'] );

				foreach ( $comments as $comment_id => $comment ) {
					if ( ! isset( $comment['new'] ) ) {
						// no new comment, skip ahead
						continue;
					} elseif ( ! isset( $comment['old'] ) ) {
						// no old comment, carry on
					} else {
						$old_mod_date = strtotime( $comment['old']->modified );
						$new_mod_date = strtotime( $comment['new']->modified );

						// keep old comment, skip ahead
						if ( $new_mod_date <= $old_mod_date )
							continue;
					}

					call_user_func( $func, $comment['new'] );
				}

				echo json_encode( $this->action_results['data'][$action_key] );
			}
		}

		exit;
	}

	private function import_comments_json( $json ) {
		header( 'Content-type: application/json' );

		return json_decode( stripslashes( $json ) );
	}

	private function import_comments_xml( $xml ) {
		header( 'Content-Type: text/xml; charset=UTF-8' );

		$sxe = simplexml_load_string( stripslashes( $xml ) );

		$comments = array();

		foreach ( $sxe->comment as $node ) {
			$comments[] = (object) array(
				'id'        => intval( $node['id'] ),
				'post_id'   => intval( $node['post_id'] ),
				'mobile_id' => (string) $node['mobile_id'],
				'date'      => (string) $node->date,
				'modified'  => (string) $node->modified,
				'author'    => (object) array(
					'name'  => (string) $node->author->name,
					'email' => (string) $node->author->email,
					'url'   => (string) $node->author->url
				),
				'title'     => trim( (string) $node->title ),
				'content'   => trim( (string) $node->content ),
				'status'    => (string) $node->status
			);
		}

		return $comments;
	}

	private function import_comment( $comment ) {
		if ( 'trash' == $comment->status ) {
			wp_delete_comment( $comment->id );

			$this->action_results['messages']['comments'][] = __( 'Comment Deleted', 'kickpress' );
			$this->action_results['data']['comments'][] = array(
				'id'        => $comment->id,
				'post_id'   => $comment->post_id,
				'mobile_id' => $comment->mobile_id,
				'status'    => 'deleted'
			);
		} elseif ( 0 == $comment->id ) {
			// $offset = get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
			$date = date( 'Y-m-d H:i:s', strtotime( $comment->date ) );

			if ( $user = wp_get_current_user() ) {
				if ( empty( $comment->author) )
					$comment->author = (object) array();
				if ( empty( $comment->author->name ) )
					$comment->author->name = $user->display_name;
				if ( empty( $comment->author->email ) )
					$comment->author->email = $user->user_email;
				if ( empty( $comment->author->url ) )
					$comment->author->url = $user->user_url;
			}

			$comment->id = wp_new_comment( array(
				'comment_post_ID'      => $comment->post_id,
				'comment_content'      => $comment->content,
				'comment_author'       => $comment->author->name,
				'comment_author_email' => $comment->author->email,
				'comment_author_url'   => $comment->author->url,
				'comment_date'         => $date,
				'user_id'              => get_current_user_id()
			) );

			$this->action_results['messages']['comments'][] = __( 'Comment Created', 'kickpress' );
			$this->action_results['data']['comments'][] = $this->export_comment_node( get_comment( $comment->id ) );
		} else {
			wp_update_comment( array(
				'comment_ID'      => $comment->id,
				'comment_content' => $comment->content
			) );

			$this->action_results['messages']['comments'][] = __( 'Comment Updated', 'kickpress' );
			$this->action_results['data']['comments'][] = $this->export_comment_node( get_comment( $comment->id ) );
		}
	}

	private function import_bookmark( $bookmark ) {
		if ( 'trash' == $bookmark->status ) {
			kickpress_delete_bookmark( $bookmark->post_id );

			$this->action_results['messages']['bookmarks'][] = __( 'Bookmark Deleted', 'kickpress' );
			$this->action_results['data']['bookmarks'][] = array(
				'id'        => intval( $bookmark->id ),
				'post_id'   => intval( $bookmark->post_id ),
				'mobile_id' => $bookmark->mobile_id,
				'status'    => 'deleted'
			);
		} else {
			$bookmark->id = (int) kickpress_insert_bookmark( $bookmark->post_id );

			$this->action_results['messages']['bookmarks'][] = __( 'Bookmark Created', 'kickpress' );
			$this->action_results['data']['bookmarks'][] = array(
				'id'        => intval( $bookmark->id ),
				'post_id'   => intval( $bookmark->post_id ),
				'mobile_id' => $bookmark->mobile_id,
				'status'    => 'created'
			);
		}
	}

	private function import_note( $note ) {
		if ( 'trash' == $note->status ) {
			kickpress_delete_note( $note->id );

			$this->action_results['messages']['notes'][] = __( 'Note Deleted', 'kickpress' );
			$this->action_results['data']['notes'][] = array(
				'id'        => intval( $note->id ),
				'post_id'   => intval( $note->post_id ),
				'mobile_id' => $note->mobile_id,
				'status'    => 'deleted'
			);
		} elseif ( 0 == $note->id ) {
			$date = date( 'Y-m-d H:i:s', strtotime( $note->date ) );

			// using low-level form to force date
			$note->id = (int) kickpress_insert_private_comment( $note->post_id, array(
				'comment_date'    => $date,
				'comment_title'   => $note->title,
				'comment_content' => $note->content,
				'comment_type'    => 'note'
			) );

			$this->action_results['messages']['notes'][] = __( 'Note Created', 'kickpress' );
			$this->action_results['data']['notes'][] = array(
				'id'        => intval( $note->id ),
				'post_id'   => intval( $note->post_id ),
				'mobile_id' => $note->mobile_id,
				'status'    => 'created'
			);
		} else {
			$date = date( 'Y-m-d H:i:s', strtotime( $note->modified ) );

			// using low-level form to force modified date
			kickpress_update_private_comment( $note->id, array(
				'comment_modified' => $date,
				'comment_title'    => $note->title,
				'comment_content'  => $note->content,
				'comment_type'     => 'note'
			) );

			$this->action_results['messages']['notes'][] = __( 'Note Updated', 'kickpress' );
			$this->action_results['data']['notes'][] = array(
				'id'        => intval( $note->id ),
				'post_id'   => intval( $note->post_id ),
				'mobile_id' => $note->mobile_id,
				'status'    => 'updated'
			);
		}
	}

	private function import_task( $task ) {
		if ( 'trash' == $task->status ) {
			kickpress_delete_task( $task->id );

			$this->action_results['messages']['tasks'][] = __( 'Task Deleted', 'kickpress' );
			$this->action_results['data']['tasks'][] = array(
				'id'        => intval( $task->id ),
				'post_id'   => intval( $task->post_id ),
				'mobile_id' => $task->mobile_id,
				'status'    => 'deleted'
			);
		} elseif ( 0 == $task->id ) {
			$date = date( 'Y-m-d H:i:s', strtotime( $task->date ) );

			$args = array(
				'comment_date'    => $date,
				'comment_content' => $task->content,
				'comment_type'    => 'task'
			);

			if ( isset( $task->karma ) && is_numeric( $task->karma ) )
				$args['comment_karma'] = intval( $task->karma );

			if ( isset( $task->meta ) && is_array( $task->meta ) )
				$args['comment_meta'] = $task->meta;

			// using low-level function to set explicit date
			$task->id = (int) kickpress_insert_private_comment( $task->post_id, $args );

			$this->action_results['messages']['tasks'][] = __( 'Task Created', 'kickpress' );
			$this->action_results['data']['tasks'][] = array(
				'id'        => intval( $task->id ),
				'post_id'   => intval( $task->post_id ),
				'mobile_id' => $task->mobile_id,
				'status'    => 'created'
			);
		} else {
			$date = date( 'Y-m-d H:i:s', strtotime( $note->modified ) );

			$args = array(
				'comment_modified' => $date,
				'comment_type'     => 'task'
			);

			if ( isset( $task->content ) )
				$args['comment_content'] = $task->content;

			if ( isset( $task->karma ) && is_numeric( $task->karma ) )
				$args['comment_karma'] = intval( $task->karma );

			if ( isset( $task->meta ) && is_array( $task->meta ) )
				$args['comment_meta'] = $task->meta;

			// using low-level function to set explicit modified date
			kickpress_update_private_comment( $task->id, $args );

			$this->action_results['messages']['tasks'][] = __( 'Task Updated', 'kickpress' );
			$this->action_results['data']['tasks'][] = array(
				'id'        => intval( $task->id ),
				'post_id'   => intval( $task->post_id ),
				'mobile_id' => $task->mobile_id,
				'status'    => 'updated'
			);
		}
	}

	/* Theme/Template Elements */

	/**
	 * @uses	form_fields
	 * @uses	get_form_fields
	 */
	public function form( $action = 'edit', $editable = true ) {
		global $wpdb, $post, $user_ID, $kickpress_post_types;

		$html = '';
		$post_data = array();
		$api_trigger = kickpress_get_api_trigger();

		// if action "add" was specified, start with an empty post
		if ( 'form' == $this->params['view'] && ('add' == $this->params['view_alias'] || 'add' == $action ) ) {
			unset( $post, $this->params['id'] );
			$post = $this->get_blank_post();
			$action = 'add';
		} elseif ( ! isset( $post->ID ) && ! empty( $this->params['id'] ) ) {
			$post = get_post( $this->params['id'] );
		}

		// determine correct capabilities for this post type
		if ( 'disable' == get_option( 'kickpress_use_post_type_cap', 'enable' ) ) {
			$create_posts  = 'create_posts';
			$edit_posts = 'edit_posts';
		} elseif ( in_array( $this->params['post_type'], array( 'post', 'page' ) ) ) {
			$create_posts  = 'create_' . $this->params['post_type'] . 's';
			$edit_posts = 'edit_' . $this->params['post_type'] . 's';
		} else {
			$create_posts  = 'create_' . $this->params['post_type'];
			$edit_posts = 'edit_' . $this->params['post_type'];
		}

		if ( $action_results = $this->get_action_results() ) {
			if ( $post_id == $action_results[ 'data' ][ 'post_id' ] ) {
				$html .= $this->generate_system_notes( $action_results );

				// If there was an error on a previous attempt to
				// save data, retrieve it and try again
				if ( 'failure' == $action_results['status'] || ( isset( $action_results['messages']['error'] ) && count( $action_results['messages']['error'] ) ) ) {
					$post_data = $action_results['data'];
					$post_data = $this->process_form_data( $post_data );
				}
			}
		}

		// Get the form data
		$form_data = kickpress_get_form_data( $post, $post_data );

/*
		// Process data if this form is being submitted, if form has errors, reload form with warnings.
		if ( isset($_POST['data'][$post->post_type]) && isset($this->validation->errors) && ! $this->validation->errors ) {
			$html .= $this->update();
			return $html;
		}
*/
		if ( (current_user_can( $create_posts ) || kickpress_anonymous_user_can( $create_posts )) && 'add' == $action ||
			(current_user_can( $edit_posts ) || kickpress_anonymous_user_can( $edit_posts )) && 'edit' == $action ) {

			if ( ! empty( $post->ID ) && is_int( $post->ID ) && 0 != $post->ID ) {
				$form_action = sprintf( '%1$s%2$s/save/', get_permalink( $post->ID ), $api_trigger );
			} else {
				$form_action = home_url( sprintf( '/%1$s/%2$s/save/', $post->post_type, $api_trigger ) );
			}

			/* if ( ! isset($this->params['tab']) ) { */
				$field_types = array();
				$fields = $this->get_custom_fields();
				foreach ( $fields as $key => $field ) {
					$field_types[] = isset( $field['type'] ) ? $field['type'] : 'text';
				}

				if ( 'media' == $post->post_type || in_array( 'file', $field_types ) )
					$upload = ' enctype="multipart/form-data"'; //inline=&amp;upload-page-form=
				else
					$upload = '';

				if ( !empty( $this->params['view_alias'] ) && 'quick-edit' == $this->params['view_alias'] ) {
					$col_id = '';
					$quick_edit = true;
				} else {
					$col_id = 'col-1';
					$quick_edit = false;
				}
				$GLOBALS['column'] = $col_id;
				$GLOBALS['quick_edit'] = $quick_edit;

				$html .= sprintf('
				<div class="form-wrapper">
					<form%5$s id="%1$s_form_%2$s" name="%1$s_form_%2$s" action="%3$s" method="post" class="site-form%4$s" data-post-type="%1$s" data-post-id="%2$s">
						%6$s',
					$post->post_type,
					$post->ID, /* ( 0 != $post->ID ? $post->ID : "edit" ), */
					$form_action,
					( $quick_edit ? ' quick-edit' : '' ),
					$upload,
					wp_nonce_field(plugin_basename(__FILE__), 'kickpress_'.$post->post_type.'_wpnonce', false, false)
				);

				if ( $quick_edit ) {
					$html .= '
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
							<h4 class="modal-title" id="edit-label-' . $post->ID . '">' . __( ucwords( $action ), 'kickpress' ) . '</h4>
						</div>
						<div class="modal-body">';
				} else {
					$html .= '
						<div id="' . $col_id . '" class="panel-group">
							<div class="panel panel-defualt">
								<div class="panel-body">';
				}

				// Always wrap the form fields with a table
				$html .= '
									<table class="form-table">';

				if ( isset($this->params['additional_content_before']) )
					$html .= $this->params['additional_content_before'];

				$html .= $this->form_fields( $form_data, $post, $editable );

				$html .= '
									</table><!-- /.form-table -->';

				// Close a panel if one has been opened
				if ( $quick_edit ) {
					$html .= '
						</div><!-- /.modal-body -->';

				} else {
					$html .= '
								</div><!-- /.panel-body -->
							</div><!-- /.panel -->
						</div><!-- /.panel-group -->';
				}

				if ( isset($this->params['additional_content_after']) )
					$html .= $this->params['additional_content_after'];

				if ( isset($this->params['custom_toolbar']) ) {
					// Custom toolbar
					$html .= $this->params['custom_toolbar'];
				} else {

					if ( $quick_edit ) {
						// Quick Edit Toolbar
						$html .= '
								<!-- Open toolbar -->
								<div class="modal-footer quick-edit-toolbar">';
					} else {
						// Default Toolbar
						$html .= '
								<!-- Open toolbar -->
								<div class="btn-toolbar form-toolbar">';
					}

					if ( $editable ) {
						if ( (current_user_can( $create_posts ) || kickpress_anonymous_user_can( $create_posts )) && 0 == $post->ID ||
								(current_user_can( $edit_posts ) || kickpress_anonymous_user_can( $edit_posts )) && 0 != $post->ID ){
							$html .= sprintf('<button type="submit" class="btn btn-success reload save-form" id="submit" data-post-id="%1$s">%2$s</button>',
								$post->ID,
								__( 'Submit', 'kickpress' )
							);
						}

						if ( current_user_can( 'delete_posts' ) && 0 != $post->ID ) {
							$delete_link = get_permalink($post->ID) . $api_trigger . '/delete/';
							$html .= sprintf('<a class="btn btn-danger delete-form" href="%3$s" name="delete_%2$s_%1$s" data-post-id="%2$s">%4$s</a>',
								$post->post_type,
								$post->ID,
								$delete_link,
								__( 'Delete', 'kickpress' )
							);
						}
					}

					if ( $quick_edit ) {
						$html .= sprintf('<button type="button" class="btn btn-primary close-form" data-dismiss="modal">%1$s</button>',
							__( 'Close', 'kickpress' )
						);
					} else {
						$close_link = 0 == $post->ID ? '/' . $post->post_type . '/' : get_permalink($post->ID);
						$html .= sprintf('<a class="btn btn-primary close-form" href="%1$s">%3$s</a>',
							$close_link,
							$post->ID,
							__( 'Close', 'kickpress' )
						);
					}
				}

				$html .= '
							</div><!-- /.btn-toolbar -->';

				// Close Box
				$html .= sprintf('
						</form>
					</div><!-- /#form-wrapper -->'
				);
/*
			} else {
				$child_module = array(
					'post_type'=>$this->params['tab'],
					'post_id'=>null,
					'filter'=>array(
						$post->post_type=>array(
							'parent'=>$post->ID
						)
					)
				);

				$args = array('view'=>'archive', 'post_type'=>$this->params['tab']);
				kickpress_loop_template( $args );
			}
*/
			if ( method_exists($this, 'local_js') )
				$this->local_js($post->ID);

			if ( isset($this->params['javascript']) ) {
				$html .= sprintf('
					<script type="text/javascript">
						%1$s
					</script>',
					implode('',$this->params['javascript'])
				);
			}
		} else {
			if ( 0 == $user_ID ){

				if ( 0 == $post->ID ) {
					$permalink = sprintf('/%1$s/%2$s/form/', $post->post_type, $api_trigger);
				} else {
					$permalink = get_permalink( $post->ID ) . $api_trigger . '/form/';
				}

				$permalink .= '?'.http_build_query($_GET);

				$args = array(
					'echo' => false,
					'redirect' => $permalink,
					'form_id' => 'loginform',
					'label_username' => __( 'Username', 'kickpress' ),
					'label_password' => __( 'Password', 'kickpress' ),
					'label_remember' => __( 'Remember Me', 'kickpress' ),
					'label_log_in' => __( 'Log In', 'kickpress' ),
					'id_username' => 'user_login',
					'id_password' => 'user_pass',
					'id_remember' => 'rememberme',
					'id_submit' => 'wp-submit',
					'remember' => true,
					'value_username' => NULL,
					'value_remember' => false
				);

				$html .= wp_login_form( $args );
			} else {
				$html .= sprintf('
					<div class="site-notes">
						<p>%1$s</p>
					</div>',
					__( "You don't have permission to do what you have requested.", 'kickpress' )
				);
			}
		}

		return $html;
	}

	public function get_blank_post( $args = array() ) {
		$defaults = array(
			'ID'        => 0,
			'post_type' => isset( $this->params['post_type'] ) ? $this->params['post_type'] : null,
			'post_name' => null
		);
		$post = array_merge($defaults, $args);

		return (object)$post;
	}

	public function form_fields( $form_data, $post, $editable ) {
		$html = '';

		if ( ! isset($post->ID) ) {
			$post_type = $this->params['post_type'];
			if ( ! empty ($this->params['id']) )
				$post_id = (integer) $this->params['id'];
			else
				$post_id = 0;
		} else {
			$post_type = $post->post_type;
			$post_id = $post->ID;
		}

		$custom_fields = $this->get_custom_fields( $this->params['merge_base_fields'] == 'enable' );

		foreach ( $custom_fields as $key => $field ) {
			$field_name = ( isset($field['name']) ? $field['name'] : $key );

			$hidden_element = false;

			if ( is_admin() && '_thumbnail_id' == $field['name'] ) {
				$hidden_element = true;
			}

			if ( is_admin() && kickpress_boolean( $field['hide_in_admin'], false ) ) {
				$hidden_element = true;
			}

			// If no element type exists, set it to text
			if ( $editable ) {
				if ( ! isset($field['type']) )
					$field['type'] = 'text';

				if ( 'timestamp' == (string) $field['type'] ) {
					$hidden_element = true;
				}
			} else {
				if ( ! isset($field['grid_element']) )
					$field['type'] = 'click';
				else
					$field['type'] = (string) $field['grid_element'];
			}

			if ( isset($field['conditionalfield']) ) {
				if ( isset($this->params[(string) $field['conditionalfield']] ) && ($this->params[(string) $field['conditionalfield']] == (string) $field['conditionalvalue']) ) {
					$hidden_element = false;
				//} elseif ( ! isset($this->params[(string) $field['conditionalfield']]) ) {
				//	$hidden_element = false;
				} else {
					$hidden_element = true;
				}
			}

			//
			// Show or Hide input based on whether this is a custom form:
			//
/*
			if ( isset($custom_fields) && count($this->params['datatables'][$post_type]) ) {

				if ( isset($custom_fields[$field_name]) && ! $hidden_element ) {
					if ( isset($custom_fields[$field_name]) ) {
						$hidden_element = $custom_fields[$field_name];
					}
					$hidden_element = false;
				} else {
					$hidden_element = true;
				}
			}
*/
			if ( isset($this->params['view_alias']) && 'quick-edit' == $this->params['view_alias'] ) {
				if ( isset($field['quickedit']) && $field['quickedit'] && ! $hidden_element ) {
					// Override the default field type
					if ( is_string( $field['quickedit'] ) ) {
						$field['type'] = (string) $field['quickedit'];
					}
					$hidden_element = false;
				} else {
					$hidden_element = true;
				}
			}

			if ( ! $hidden_element ) {
				// Make sure a caption exists
				$field['caption'] = $this->get_caption($field, $field_name);
				$field['post_id'] = $post_id;

				if ( isset( $form_data[ $field_name ]['value'] ) ) {
					// If the $form_data array existes it is because the form had
					// errors, fill in inputs with what user had set as values
					$field['value'] = $form_data[ $field_name ]['value'];
				} elseif ( isset($post->$field_name) ) {
					if ( isset($field['type']) && ('password' == $field['type']) ) {
						$field['value'] = null;
					} else {
						$field['value'] = htmlspecialchars($post->$field_name);
					}
				} elseif ( '_sticky' == $field_name ) {
					$stickies = get_option('sticky_posts');

					if ( in_array($post_id, $stickies) )
						$field['value'] = 'enable';
					else
						$field['value'] = 'disable';
				} elseif ( $value = get_post_meta($post_id, $field_name, true) ) {
					if ( isset($field['type']) && ('password' == $field['type']) ) {
						$field['value'] = null;
					} elseif ( is_string($value) ) {
						$field['value'] = htmlspecialchars($value);
					}
				}

				if ( empty( $field['value'] ) && !empty( $field['autofill'] ) && isset( $_GET[ $field['autofill'] ] ) ) {
					// Parameters were passed in through the url, use those
					$field['value'] = urldecode( $_GET[ $field['autofill'] ] );
				} elseif ( !isset( $field['value'] ) && isset( $field['default'] ) ) {
					// Set a default value
					if ( isset($field['default']) && 0 == $post_id ) {
						$field['value'] = (string)$field['default'];
					}
				}

				if ( isset($field['class']) )
					$class = explode(' ', $field['class']);
				else
					$class = array();

				if ( isset($field['required']) && $field['required'] == true ) {
					if ( 'select' == (string) $field['type'] || 'one_to_one' == (string) $field['type'] )
						$class[] = 'validate-selection';
					else
						$class[] = 'required';
				}

				if ( isset($field['type']) )
					$class[] = sprintf("%s",strtolower((string) $field['type']));

				$field['class'] = (is_array($class)?implode(" ", $class):"");

				// pass properties as array
				/* $properties = array();
				if ( isset($field['properties']) && is_array($field['properties']) ) {
					foreach ( $field['properties'] as $property=>$value )
						$properties[] = sprintf(' %s="%s"', $property, $value);
				}
				$field['properties'] = (count($properties)?implode(" ", $properties):""); */

				// Render the element
				$form_element = kickpress_get_form_element( $field['type'] );
				$attributes = $field;
				$post_id = $field['post_id'];
				$attributes['id'] = "{$post_type}_{$post_id}{$field_name}";
				$attributes['name'] = "data[{$post_type}][{$post_id}][{$field_name}]";
				$attributes['post_type'] = $this->params['post_type'];
				//$attributes['valid_views'] = $this->params['valid_views'];

				$html .= $form_element->element($attributes);

				// If the element requires confrimation, build a second confirmation input
				if ( isset($field['confirm']) && $field['confirm'] ) {
					$attributes['caption'] = 'Re-enter '.$field['caption'];
					$attributes['id'] = "{$post_type}_{$post_id}{$field_name}_confirm";
					$attributes['name'] = "data[{$post_type}][{$post_id}][{$field_name}_confirm]";
					$html .= $form_element->element($attributes);
				}
			}
		}

		if ( is_object( $this->_handler ) )
			$html .= $this->_handler->form_footer();

		return $html;
	}

	public function get_form_fields($form_data, $post, $editable) {
		$post_type = (string) $post->post_type;
		$skip_post_vars = array('ID','post_author','post_date','post_date_gmt','post_content','post_title','post_category','post_excerpt','post_status','comment_status','ping_status','post_password','post_name','to_ping','pinged','post_modified','post_modified_gmt','post_content_filtered','post_parent','guid','menu_order','post_type','post_mime_type','comment_count','tags_input','categories_input');

		foreach ( $form_data as $key=>$field ) {
			$field_name = $field['name'];
			if ( in_array($field_name, $skip_post_vars) )
				continue;

			$hidden_element = false;

			if ( is_admin() && '_thumbnail_id' == $field['name'] ) {
				$hidden_element = true;
			}

			if ( is_admin() && kickpress_boolean( $field['hide_in_admin'], false ) ) {
				$hidden_element = true;
			}

			// If no element type exists, set it to text
			if ( $editable ) {
				if ( ! isset($field['type']) )
					$field['type'] = "text";

				if ( isset($field['type']) && strtoupper((string) $field['type']) == "timestamp" ) {
					$hidden_element = true;
				}
			} else {
				if ( ! isset($field['grid_element']) )
					$field['type'] = "click";
				else
					$field['type'] = (string) $field['grid_element'];
			}

			if ( isset($field['conditionalfield']) ) {
				if (
					isset($this->params[(string) $field['conditionalfield']])
					&& ($this->params[(string) $field['conditionalfield']] == (string) $field['conditionalvalue'] )
				) {
					$hidden_element = false;
				//} elseif ( ! isset($this->params[(string) $field['conditionalfield']]) ) {
				//	$hidden_element = false;
				} else {
					$hidden_element = true;
				}
			}

			if ( isset($this->params['view_alias']) && 'quick-edit' == $this->params['view_alias'] ) {
				if ( isset($field['quickedit']) && $field['quickedit'] && ! $hidden_element ) {
					// Override the default field type
					if ( is_string( $field['quickedit'] ) ) {
						$field['type'] = (string) $field['quickedit'];
					}
					$hidden_element = false;
				} else {
					$hidden_element = true;
				}
			}

			if ( ! $hidden_element ) {
				// Make sure a caption exists
				$field['caption'] = $this->get_caption($field, $field_name);

				if ( isset($post->ID) )
					$field['post_id'] = $post->ID;
				else
					$field['post_id'] = 0;

				if ( empty( $field['value'] ) ) {
					if ( !$field['value'] = get_post_meta( $post->ID, $field['name'], true ) )
						$field['value'] = null;
				}

				if ( isset($field['type']) && ('password' == $field['type']) ) {
					$field['value'] = null;
				} elseif ( 'wysiwyg' != $field['type'] ) {

				} elseif ( is_string($field['value']) && ( isset($field['type']) && 'wysiwyg' != $field['type'] ) ) {
					$field['value'] = htmlspecialchars( $field['value'] );
				}

				if ( empty($field['value']) && isset( $_GET[ $field['autofill'] ] ) ) {
					$field['value'] = urldecode( $_GET[ $field['autofill'] ] );
				} elseif ( ! isset($field['value']) && isset($field['default']) ) {
					$field['value'] = (string) $field['default'];
				}

				if ( isset($field['class']) )
					$class = explode(' ', $field['class']);
				else
					$class = array();

				if ( isset($field['required']) && $field['required'] == true ) {
					if ( ('select' == (string) $field['type']) || ('one_to_one' == (string) $field['type']) )
						$class[] = 'validate-selection';
					else
						$class[] = 'required';
				}

				if ( isset($field['type']) )
					$class[] = sprintf("%s",strtolower((string) $field['type']));

				$field['class'] = (is_array($class)?implode(' ', $class):'');

				// pass properties as array
				/* $properties = array();
				if ( isset($field['properties']) && is_array($field['properties']) ) {
					foreach ( $field['properties'] as $property=>$value )
						$properties[] = sprintf(' %s="%s"', $property, $value);
				}
				$field['properties'] = (count($properties)?implode(" ", $properties):""); */

				// Render the element
				$form_element = kickpress_get_form_element( $field['type'] );
				$attributes = $field;
				$post_id = $field['post_id'];
				$attributes['id'] = "{$post_type}_{$post_id}{$field_name}";
				$attributes['name'] = "data[{$post_type}][{$post_id}][{$field_name}]";
				$attributes['post_type'] = $post_type;
				$attributes['post_name'] = $post->post_name;

				$html .= $form_element->element($attributes);

				// If the element requires confrimation, build a second confirmation input
				if ( isset($field['confirm']) && $field['confirm'] ) {
					$attributes['caption'] = 'Re-enter '.$field['caption'];
					$attributes['id'] = "{$post_type}_{$post_id}{$field_name}_confirm";
					$attributes['name'] = "data[{$post_type}][{$post_id}][{$field_name}_confirm]";
					$html .= $form_element->element($attributes);
				}

				//unset($attributes);
			}
		}

		return $html;
	}

	// The $thumb_size variable can be either an array or a string like: 'thumb-medium'
	public function get_thumbnail( $post, $args=array() ) {
		if ( ! has_post_thumbnail( $post->ID ) )
			return '';

		$html = '';

		$default_args = array(
			'thumb_size'     => null,
			'thumb_caption'  => null,
			'thumb_align'    => null, // alignleft, alignright
			'thumb_link'     => get_permalink( $post->ID )
		);
		$args = array_merge($default_args, $args);
		extract($args);
		$thumb_align = " $thumb_align";

		// Only wrap the thumbnail with a caption if one exists
		if ( ! empty ( $thumb_caption ) ) {
			$html .= '<div class="wp-caption' . $thumb_align . '">';
			$thumb_align = null; // Unset so that the img tag will not reuse the class
		}

		if ( empty( $thumb_size ) ) {
			if ( ! $thumb_size = get_post_meta( $post->ID, '_thumb_size', true ) ) {
				$thumb_size = array( 100, 100 );
			}
		}

		$thumb_args = array(
			'alt'=>$post->post_title,
			'title'=>$post->post_title,
			'class'=>'post-thumbnail' . $thumb_align
		);

		if ( $thumb = get_the_post_thumbnail( $post->ID, $thumb_size, $thumb_args ) ) {
			if ( ! empty( $thumb_link ) )
				$html .= '<a href="' . $thumb_link . '">' . $thumb . '</a>';
			else
				$html .= $thumb;
		} else {
			return '';
		}

		if ( ! empty ( $thumb_caption ) ) {
			$html .= '<p class="wp-caption-text">' . $thumb_caption . '</p></div>';
		}

		return $html;
	}

	public function get_content( $post, $args ) {
/*

	$defaults = array(
		'text'=>'',
		'link'=>'#',
		'charset'=>get_bloginfo('charset'),
		'length'=>40,
		'use_words'=>true,
		'ellipsis' => '&hellip;',
		'allowed_tags' => $allowed_tags,
		'exclude_tags' => '',
		'read_more'=>true,
		'read_more_text'=>'Read&nbsp;More&nbsp;&raquo;',
		'trailing'=>array()
	);

*/


		$default_args = array(
			'use_excerpt'    => false,
			'create_excerpt' => false,
			'apply_filters'  => true,
			'length'         => 50,
			'link'           => get_permalink( $post->ID ),
			'trailing'       => array(),
			'read_more'      => true,
			'get_thumbnail'  => false,
			'thumb_size'     => null,
			'thumb_caption'  => null,
			'thumb_align'    => null,
			'use_words'      => true
		);
		$args = array_merge($default_args, $args);
		extract($args);

		if ( $create_excerpt )
		{
			if ( isset($this->params['description_length']) )
				$excerpt_length = $this->params['description_length'];
			else
				$excerpt_length = $length;
		}
		else
		{
			// use the full length content
			$excerpt_length = 0;
		}

		if ( $get_thumbnail ) {
			$thumb_args = array(
				'thumb_size'    => $thumb_size,
				'thumb_caption' => $thumb_caption,
				'thumb_align'   => $thumb_align,
				'thumb_link'    => $link
			);
			$content = $this->get_thumbnail( $post, $thumb_args );
		}

		if ( $use_excerpt )
			$content .= $post->post_excerpt;
		else
			$content .= $post->post_content;

		if ( $apply_filters )
			$content = apply_filters('the_content', $content);

		$desc_array = array(
			'text'      => $content,
			'length'    => $excerpt_length,
			'link'      => $link,
			'read_more' => $read_more,
			'trailing'  => $trailing,
			'use_words' => $use_words
		);

		return kickpress_the_excerpt($desc_array);
	}

	public function post_type_toolbar() {
		global $wpdb;
		$html = '';

		$query = $wpdb->prepare(
			"SELECT post_content " .
			"FROM $wpdb->posts " .
			"WHERE post_type = 'custom-post-types' " .
			"AND post_name = %s",
			$this->params['post_type']
		);

		if ( $post_type_content = $wpdb->get_var( $query ) ) {
			$html .= '<div class="post-type-content">'.apply_filters('the_content', $post_type_content).'</div>';
		} else {
			$html .= '';
		}

		// Categories Menu
		if ( isset( $this->params['categories_toolbar'] ) && kickpress_boolean( $this->params['categories_toolbar'] ) !== false )
			$html .= $this->termsbar();

		// Alphabar
		if ( isset( $this->params['alphabar'] ) && kickpress_boolean( $this->params['alphabar'] ) !== false )
			$html .= $this->alphabar();

		return $html;
	}

	public function alphabar() {
		ob_start();

		$widget = new kickpress_alphabar_widget();
		$widget->widget( array(), array(
			'post_type' => $this->params['post_type']
		) );

		$html = ob_get_contents();

		ob_end_clean();
	}

	public function termsbar() {
		ob_start();

		$widget = new kickpress_termsbar_widget();
		$widget->widget( array(), array(
			'taxonomy' => $this->params['post_type'] . '-category'
		) );

		$html = ob_get_contents();

		ob_end_clean();
	}

	public function __call($name, $arguments=array()) {
		if ( strpos($name, '__') ) {
			$parts = explode('__', $name);
			if ( $parts[0] == 'widgets_control' )
				$this->widgets_control($arguments[0]);
			elseif ( $parts[0] == 'render_widgets' )
				$this->render_widgets($arguments[0], $arguments[1]);
		} elseif ( is_object( $this->_handler ) && method_exists( $this->_handler, $name ) ) {
			return call_user_func_array( array( $this->_handler, $name ), $arguments );
		}
	}

	public function destroy() {
		$this->api = null;
		foreach ($this as $index=>$value)
			unset($this->$index);
	}
}

?>
