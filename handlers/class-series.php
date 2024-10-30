<?php

class kickpress_series_handler extends kickpress_api_handler {
	private $_post_type = null;
	private $_post_type_options = array(
		'series_taxonomy' => array(
			'name'    => '_series_taxonomy',
			'type'    => 'hidden',
			'caption' => 'Series Taxonomy',
			'default' => ''
		),
		'series_object_type' => array(
			'name'    => '_series_object_type',
			'type'    => 'post_types',
			'caption' => 'Target Post Type',
			'default' => 'post'
		)
	);

	private $_taxonomy;

	public function __construct( $api ) {
		parent::__construct( $api );

		$api->add_action( array(
			'slug'       => 'start-series',
			'method'     => 'start_series',
			'callback'   => 'single',
			'label'      => 'Start Series',
			'capability' => 'edit_tasks'
		) );

		$api->add_action( array(
			'slug'       => 'quit-series',
			'method'     => 'quit_series',
			'callback'   => 'single',
			'label'      => 'Quit Series',
			'capability' => 'edit_tasks'
		) );

		$api->add_action( array(
			'slug'       => 'export-series',
			'method'     => 'export_series',
			'label'      => 'Export Series',
			'capability' => 'read_posts'
		) );

		$api->add_action( array(
			'slug'       => 'export-series-tasks',
			'method'     => 'export_series_tasks',
			'label'      => 'Export Series Tasks',
			'capability' => 'read_posts'
		) );

		if ( $post_type_id = intval( $api->params['post_type_id'] ) ) {
			$post = get_post( $post_type_id );

			$this->_post_type = $post->post_name;

			foreach ( $this->_post_type_options as &$option ) {
				$value = get_post_meta( $post_type_id, $option['name'], true );

				if ( empty( $value ) && isset( $option['default'] ) )
					$value = $option['default'];

				$option['value'] = $value;
			}

			unset( $option );
		}

		$post_type = $this->_post_type;

		$this->add_action( 'registered_post_type', null, 10, 2 );
		$this->add_action( 'registered_taxonomy', null, 10, 3 );

		$this->add_action( 'admin_enqueue_scripts', 'admin_scripts' );
		$this->add_action( 'wp_ajax_search_posts', 'ajax_search_posts' );

		$this->add_filter( 'manage_edit-' . $post_type . '_columns', 'filter_columns' );
		$this->add_action( 'manage_' . $post_type . '_posts_custom_column', 'custom_column', 10, 2 );

		$this->add_action( 'add_meta_boxes_' . $post_type, 'add_meta_boxes' );
	}

	// API Action Handler
	public function start_series( $params ) {
		$this->start( $params['id'] );
	}

	// API Action Handler
	public function quit_series( $params ) {
		$this->quit( $params['id'] );
	}

	public function export_series( $params ) {
		$post = $params['id'];
		$args = array();

		for ( $i = 0, $n = count( $params['extra'] ); $i < $n - 1; $i += 2 ) {
			$key   = $params['extra'][ $i ];
			$value = $params['extra'][ $i + 1 ];

			if ( in_array( $key, array( 'page', 'posts_per_page' ) ) )
				$args[ $key ] = $value;
		}

		$posts = $this->get_posts( $post, $args, $count );

		$export = array( 'total' => $count, 'data' => array() );

		foreach ( $posts as $post ) {
			$node = (object) array(
				'ID'      => $post->ID,
				'type'    => $post->post_type,
				'title'   => $post->post_title,
				'link'    => get_permalink( $post->ID ),
				'date'    => get_the_date( DATE_RSS, $post->ID ),
				'author'  => (object) array(
					'name'  => get_the_author_meta( 'display_name', $post->post_author ),
					'email' => get_the_author_meta( 'user_email', $post->post_author ),
					'url'   => get_the_author_meta( 'user_url', $post->post_author )
				),
				'content' => apply_filters( 'the_content', $post->post_content ),
				'excerpt' => apply_filters( 'the_excerpt', $post->post_excerpt ),
				'meta'    => array()
			);

			if ( $api = kickpress_init_api( $post->post_type ) ) {
				$meta_fields = $api->get_custom_fields( true );

				foreach ( $meta_fields as $field_name => $field_meta ) {
					$meta_value = get_post_meta( $post->ID, $field_meta['name'], true );

					if ( is_string( $meta_value ) && $field_meta['exportable'] )
						$node->meta[ $field_name ] = $meta_value;
				}
			}


			$export['data'][] = $node;
		}

		header( 'Content-type: application/json' );
		die( json_encode( $export ) );
	}

	public function export_series_tasks( $params ) {
		if ( $post = $params['id'] ) {
			$tasks = $this->get_user_posts( $post );

			$export = array( 'total' => count( $tasks ), 'data' => array() );

			foreach ( $tasks as $task ) {
				$export['data'][] = array(
					'ID'      => intval( $task->comment_ID ),
					'post_id' => intval( $task->comment_post_ID ),
					'status'  => boolval( $task->comment_karma )
				);
			}

			header( 'Content-type: application/json' );
			die( json_encode( $export ) );
		} else {
			$tasks = kickpress_get_tasks( array(
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     => '_series',
						'value'   => ':reading',
						'compare' => 'LIKE'
					)
				),
				'meta_key' => '_index',
				'orderby'  => 'meta_value_num',
				'order'    => 'ASC'
			) );

			$progress = array();

			foreach ( $tasks as $task ) {
				$meta_key = $task->comment_meta['_series'];

				if ( ( $pos = strrpos( $meta_key, ':reading' ) ) !== false )
					$meta_key = substr( $meta_key, 0, $pos );

				$progress[ $meta_key ][] = array(
					'ID'      => intval( $task->comment_ID ),
					'post_id' => intval( $task->comment_post_ID ),
					'status'  => boolval( $task->comment_karma )
				);
			}

			$posts = get_posts( array(
				'post_type'  => $this->_post_type->name,
				'meta_query' => array(
					array(
						'key'     => '_series_meta_key',
						'value'   => array_keys( $progress ),
						'compare' => 'IN'
					)
				)
			) );

			$export = array();

			$meta_fields = $this->_api->get_custom_fields( true );

			foreach ( $posts as $post ) {
				$meta_key = $this->get_meta_key( $post );

				$node = (object) array(
					'ID'      => $post->ID,
					'type'    => $post->post_type,
					'title'   => $post->post_title,
					'link'    => get_permalink( $post->ID ),
					'date'    => get_the_date( DATE_RSS, $post->ID ),
					'author'  => (object) array(
						'name'  => get_the_author_meta( 'display_name', $post->post_author ),
						'email' => get_the_author_meta( 'user_email', $post->post_author ),
						'url'   => get_the_author_meta( 'user_url', $post->post_author )
					),
					'content' => apply_filters( 'the_content', $post->post_content ),
					'excerpt' => apply_filters( 'the_excerpt', $post->post_excerpt ),
					'meta'    => array(),
					'tasks'   => $progress[ $meta_key ]
				);

				foreach ( $meta_fields as $field_name => $field_meta ) {
					$meta_value = get_post_meta( $post->ID, $field_meta['name'], true );

					if ( is_string( $meta_value ) && $field_meta['exportable'] )
						$node->meta[ $field_name ] = $meta_value;
				}

				$export['data'][] = $node;
			}

			die( json_encode( $export ) );
		}
	}

	public function get_post_type_options() {
		return $this->_post_type_options;
	}

	public function get_post_type_option( $key ) {
		return $this->_post_type_options[ $key ]['value'];
	}

	public function set_post_type_option( $key, $value ) {
		if ( isset( $this->_post_type_options[ $key ] ) ) {
			$this->_post_type_options[ $key ]['value'] = $value;

			if ( $post_type_id = intval( $api->params['post_type_id'] ) ) {
				$option_name = $this->_post_type_options[ $key ]['name'];
				update_post_meta( $post_type_id, $option_name, $value );
			}
		}
	}

	public function get_custom_fields() {
		return array(
			'series_term' => array(
				'name'       => '_series_term',
				'type'       => 'hidden',
				'caption'    => 'Series Term',
				'default'    => '',
				'exportable' => true
			),
			'series_meta_key' => array(
				'name'       => '_series_meta_key',
				'type'       => 'hidden',
				'caption'    => 'Series Meta Key',
				'default'    => '',
				'exportable' => true
			)
		);
	}

	public function update_meta_fields( $post, $post_data, $form_data ) {
		// get the newly-saved post, not the old post
		$post = get_post( $post->ID );

		$post_data['_series_term'] = $this->update_term( $post );
		$post_data['_series_meta_key'] = $this->update_meta_key( $post );

		$term = $this->get_term( $post );

		$meta_key = $this->get_meta_key( $post );

		if ( isset( $post_data['term_order'] ) ) {
			global $wpdb;

			$wpdb->delete( $wpdb->term_relationships, array(
				'term_taxonomy_id' => $term->term_taxonomy_id
			) );

			$wpdb->delete( $wpdb->postmeta, array(
				'meta_key' => $meta_key
			) );

			$count = 0;

			foreach ( $post_data['term_order'] as $index => $post_id ) {
				if ( $post_id > 0 ) {
					$wpdb->insert( $wpdb->term_relationships, array(
						'object_id' => $post_id,
						'term_taxonomy_id' => $term->term_taxonomy_id,
						'term_order' => $index
					) );

					if ( isset( $post_data['post_tasks'][ $post_id ] ) ) {
						$post_tasks = $post_data['post_tasks'][ $post_id ];

						foreach ( $post_tasks as $meta_value ) {
							if ( ! empty( $meta_value ) ) {
								add_post_meta( $post_id, $meta_key, $meta_value, false );
							}
						}
					}

					$count++;
				}
			}

			$wpdb->update( $wpdb->term_taxonomy, array(
				'count' => $count
			), array(
				'term_taxonomy_id' => $term->term_taxonomy_id
			) );

			unset( $post_data['term_order'] );
		}

		return $post_data;
	}

	public function registered_post_type( $post_type, $args ) {
		if ( $post_type == $this->_post_type ) {
			$this->_post_type = $args;
			$this->remove_action( 'registered_post_type' );
			$this->register_taxonomy();
		}
	}

	public function registered_taxonomy( $taxonomy, $object_type, $args ) {
		if ( $taxonomy == $this->_post_type->name . '-series' ) {
			$this->_taxonomy = $this->_post_type->taxonomies[] = $args;
			$this->remove_action( 'registered_taxonomy' );
		}
	}

	public function register_taxonomy() {
		$old_taxonomy_slug = $this->get_post_type_option( 'series_taxonomy' );
		$new_taxonomy_slug = $this->_post_type->name . '-series';

		// Update old records when slug changes
		if ( $old_taxonomy_slug != $new_taxonomy_slug ) {
			$this->set_post_type_option( 'series_taxonomy', $new_taxonomy_slug );

			if ( ! empty( $old_taxonomy_slug ) ) {
				global $wpdb;

				$wpdb->update( $wpdb->term_taxonomy, array(
					'taxonomy' => $new_taxonomy_slug
				), array(
					'taxonomy' => $old_taxonomy_slug
				) );
			}
		}

		$labels = array( 'name' => $post->post_title );

		$keys = array( 'singular_name', 'menu_name' );

		foreach ( $keys as $key ) {
			$labels[ $key ] = $this->_post_type->labels->$key;
		}

		if ( function_exists( 'register_taxonomy' ) ) {
			$object_type = $this->get_post_type_option( 'series_object_type' );

			register_taxonomy( $new_taxonomy_slug, $object_type, array(
				'label' => $this->_post_type->label,
				'labels' => $labels,
				'public' => true,
				'show_ui' => false,
				'show_in_nav_menus' => false,
				'show_tagcloud' => false,
				'show_admin_column' => false,
				'rewrite' => true,
				'hierarchical' => true,
				'query_var' => true,
				'sort' => false
			) );
		}

		$this->_taxonomy = get_taxonomy( $new_taxonomy_slug );
	}

	public function update_term( $post ) {
		$taxonomy_slug = $this->_taxonomy->name;
		$old_term_slug = get_post_meta( $post->ID, '_series_term', true );
		$new_term_slug = $this->_truncate( $post->post_name, 190 ) . '-series';
		$new_term_name = $post->post_title;

		if ( $old_term_slug != $new_term_slug ) {
			if ( $term = term_exists( $old_term_slug, $taxonomy_slug ) ) {
				wp_update_term( intval( $term['term_id'] ), $taxonomy_slug, array(
					'name' => $new_term_name,
					'slug' => $new_term_slug
				) );
			} else {
				wp_insert_term( $new_term_name, $taxonomy_slug, array(
					'slug' => $new_term_slug
				) );
			}
		}

		return $new_term_slug;
	}

	public function update_meta_key( $post ) {
		$base_key = $post->post_type . ':' . $post->post_name;

		$old_meta_key = get_post_meta( $post->ID, '_series_meta_key', true );
		$new_meta_key = '_series:' . $this->_truncate( $base_key, 180 );

		if ( $old_meta_key != $new_meta_key ) {
			if ( '' === $old_meta_key ) {
				add_post_meta( $post->ID, '_series_meta_key', $new_meta_key, true );
			} else {
				update_post_meta( $post->ID, '_series_meta_key', $new_meta_key );
			}
		}

		return $new_meta_key;
	}

	public function get_taxonomy() {
		return $this->_taxonomy;
	}

	public function get_term( $post ) {
		$post_id = is_object( $post ) ? $post->ID : intval( $post );
		$slug = get_post_meta( $post_id, '_series_term', true );
		return get_term_by( 'slug', $slug, $this->_taxonomy->name );
	}

	public function get_meta_key( $post ) {
		$post_id = is_object( $post ) ? $post->ID : intval( $post );
		return get_post_meta( $post_id, '_series_meta_key', true );
	}

	public function get_posts( $post, $args = array(), &$count = null ) {
		$term = $this->get_term( $post );
		$taxonomy = $this->get_taxonomy();

		$defaults = array(
			'suppress_filters'    => false,
			'ignore_sticky_posts' => true,
			'post_status'         => 'publish',
			'posts_per_page'      => 10,
			'paged'               => 1
		);

		$args = wp_parse_args( $args, $defaults );
		$args['post_type'] = $taxonomy->object_type;
		$args['tax_query'] = array(
			array(
				'taxonomy' => $term->taxonomy,
				'terms'    => $term->slug,
				'field'    => 'slug'
			)
		);

		$this->add_filter( 'posts_fields', 'query_filter_fields' );
		$this->add_filter( 'posts_orderby', 'query_filter_order' );

		$query = new WP_Query( $args );
		$posts = $query->get_posts();
		$count = $query->found_posts;

		$this->remove_filter( 'posts_fields', 'query_filter_fields' );
		$this->remove_filter( 'posts_orderby', 'query_filter_order' );

		foreach ( $posts as $index => $series_post ) {
			$posts[ $index ]->series_tasks = $this->get_tasks( $post, $series_post );
		}

		return $posts;
	}

	public function get_tasks( $post, $args = array() ) {
		$meta_key = $this->get_meta_key( $post );

		$tasks = array();

		if ( ! empty( $meta_key ) ) {
			if ( is_array( $args ) ) {
				$args['posts_per_page'] = -1;

				$posts = $this->get_posts( $post, $args );

				foreach ( $posts as $series_post ) {
					$meta = get_post_meta( $series_post->ID, $meta_key, false );

					foreach ( $meta as $meta_value ) {
						$tasks[] = (object) array(
							'post_id'      => $series_post->ID,
							'task_content' => $meta_value,
							'meta_key'     => $meta_key
						);
					}
				}
			} else {
				$post_id = is_object( $args ) ? $args->ID : intval( $args );

				$meta = get_post_meta( $post_id, $meta_key, false );

				foreach ( $meta as $meta_value ) {
					$tasks[] = (object) array(
						'post_id'      => $post_id,
						'task_content' => $meta_value,
						'meta_key'     => $meta_key
					);
				}
			}
		}

		return $tasks;
	}

	public function get_next_post( $post, $series_post = null ) {
		$posts = $this->get_posts( $post, array(
			'posts_per_page' => -1
		) );

		if ( empty( $series_post ) ) {
			return $posts[0];
		}

		$post_id = is_object( $series_post ) ? $series_post->ID : intval( $series_post );

		$match = false;

		foreach ( $posts as $next_post ) {
			if ( $match ) {
				return $next_post;
			}

			$match = $next_post->ID == $post_id;
		}

		return false;
	}

	public function get_user_posts( $post, $args = array() ) {
		// Provide missing indexes
		$this->index_user_posts( $post );

		$meta_key = $this->get_meta_key( $post );

		$args['meta_query'] = array( 'relation' => 'AND',
			array( 'key' => '_series', 'value' => $meta_key . ':reading' ),
			array( 'key' => '_index', 'compare' => 'EXISTS' )
		);

		$args['meta_key'] = '_index';
		$args['orderby'] = 'meta_value_num';
		$args['order'] = 'ASC';

		return kickpress_get_tasks( $args );
	}

	public function get_user_tasks( $post, $args = array() ) {
		$meta_key = $this->get_meta_key( $post );

		$args['meta_query'] = array( 'relation' => 'AND',
			array( 'key' => '_series', 'value' => $meta_key ),
			array( 'key' => '_index', 'compare' => 'EXISTS' )
		);

		$args['meta_key'] = '_index';
		$args['orderby'] = 'meta_value_num';
		$args['order'] = 'ASC';

		return kickpress_get_tasks( $args );
	}

	public function get_next_user_post( $post ) {
		$args = array( 'karma' => 0 );

		if ( $tasks = $this->get_user_posts( $post, $args ) ) {
			return get_post( $tasks[0]->comment_post_ID );
		}

		return false;
	}

	public function get_user_progress( $post, $type = 'any' ) {
		$count = $total = 0;

		$meta_key = $this->get_meta_key( $post );

		if ( in_array( $type, array( 'any', 'post' ) ) ) {
			$meta_query = array(
				array( 'key' => '_series', 'value' => $meta_key . ':reading' )
			);

			$count += kickpress_count_private_comments( 'task', array(
				'meta_query' => $meta_query, 'karma' => 1
			) );

			$total += kickpress_count_private_comments( 'task', array(
				'meta_query' => $meta_query
			) );
		}

		if ( in_array( $type, array( 'any', 'task' ) ) ) {
			$meta_query = array( 'relation' => 'AND',
				array( 'key' => '_series', 'value' => $meta_key ),
				array( 'key' => '_index', 'compare' => 'EXISTS' )
			);

			$count += kickpress_count_private_comments( 'task', array(
				'meta_query' => $meta_query, 'karma' => 1
			) );

			$total += kickpress_count_private_comments( 'task', array(
				'meta_query' => $meta_query
			) );
		}

		if ( 0 == $total ) {
			return 0;
		}

		return $count / $total;
	}

	private function index_user_posts( $post ) {
		$meta_key = $this->get_meta_key( $post );

		$unindexed = kickpress_get_tasks( array(
			'meta_query' => array( 'relation' => 'AND',
				array( 'key' => '_series', 'value' => $meta_key . ':reading' ),
				array( 'key' => '_index', 'compare' => 'NOT EXISTS' )
			)
		) );

		if ( ! empty( $unindexed ) ) {
			$posts = $this->get_posts( $post, array( 'posts_per_page' => -1 ) );

			foreach ( $posts as $series_post ) {
				foreach ( $unindexed as $index => $task ) {
					if ( $task->comment_post_ID == $series_post->ID ) {
						add_comment_meta( $task->comment_ID, '_index',
							$series_post->term_order, true );

						unset( $unindexed[ $index ] );
						break;
					}
				}
			}
		}
	}

	public function admin_scripts() {
		$path = dirname( __FILE__ );

		$url = plugins_url( 'includes/js/kickpress-series.js', $path );
		wp_enqueue_script( 'kickpress-series', $url, array( 'jquery' ) );

		$url = plugins_url( 'includes/css/kickpress-series.css', $path );
		wp_enqueue_style( 'kickpress-series', $url );
	}

	public function ajax_search_posts() {
		if ( $this->_post_type->name == $_REQUEST['type'] ) {
			if ( ! empty( $_REQUEST['s'] ) ) {
				$table = new kickpress_series_search_list_table( $this->_taxonomy );
				$table->display();
			} else {
				echo 'No search terms.';
			}

			exit;
		}
	}

	public function filter_columns( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[$key] = $value;

			if ( 'title' == $key ) {
				$new_columns['series'] = 'Series';
			}
		}

		return $new_columns;
	}

	public function custom_column( $column_name, $post_id ) {
		if ( 'series' == $column_name ) {
			$posts = $this->get_posts( $post_id, array(
				'post_status' => array( 'publish', 'future' ),
				'posts_per_page' => 5
			), $count );

			if ( ! empty( $posts) ) {
				$links = array();

				foreach ( $posts as $post ) {
					$links[] = sprintf(
						'<a href="post.php?post=%d&action=edit">%s</a>',
						$post->ID, $post->post_title
					);
				}

				if ( count( $posts ) < $count ) {
					$links[] = sprintf( '<span>%d more</span>',
						$count - count( $posts ) );
				}

				echo implode( ", ", $links );
			} else {
				echo '&#8212;';
			}
		}
	}

	public function add_meta_boxes() {
		add_meta_box( $this->_taxonomy->name, 'Series Posts',
			array( $this, 'meta_box' ), $this->_post_type->name );
	}

	public function meta_box( $post ) {
		$table = new kickpress_series_post_list_table( $this );
		$table->display();
?>
<h3><label for="add-series-post-search">Add Posts</label></h3>
<div id="add-series-post-input">
	<input type="text" size="40" placeholder="Enter Post Title" id="add-series-post-search">
	<input type="hidden" id="add-series-post-type" value="<?php echo $post->post_type; ?>">
	<input type="button" value="Search" id="add-series-post-button" class="button">
	<input type="button" value="Clear" id="add-series-post-clear" class="button">
	<span id="add-series-post-spinner" class="spinner"></span>
</div>
<div id="add-series-post-results"></div>
<?php
	}

	public function start( $post ) {
		if ( $user_id = get_current_user_id() ) {
			// Return any pre-existing to-do items
			$user_tasks = $this->get_user_tasks( $post );

			if ( ! empty( $user_tasks ) ) {
				return true;
			}

			$series_key = $this->get_meta_key( $post );

			// Add series tasks to user's to-do list
			$posts = $this->get_posts( $post, array(
				'posts_per_page' => -1
			) );

			foreach ( $posts as $series_post ) {
				kickpress_insert_task( $series_post->ID, $series_post->post_title, 0, array(
					'_series' => $series_key . ':reading',
					'_index'  => $series_post->term_order
				) );

				foreach ( $series_post->series_tasks as $index => $task ) {
					kickpress_insert_task( $series_post->ID, $task->task_content, 0, array(
						'_series' => $series_key,
						'_index'  => $index
					) );
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * TODO
	 * find all post tasks
	 * find all user tasks
	 * update user tasks to match post tasks
	 */
	public function sync( $post ) {
		$posts = $this->get_posts( $post, array( 'posts_per_page' => -1 ) );

		$user_posts = $this->get_user_posts( $post );
		$user_tasks = $this->get_user_tasks( $post );

		$post_map = array_combine( array_map( function( $post ) {
			return $post->ID;
		}, $posts ), $posts );

		$user_post_map = array_combine( array_map( function( $task ) {
			return $task->comment_post_ID;
		}, $user_posts), $user_posts);

		foreach ( $user_tasks as $task ) {
			if ( ! isset( $user_post_map[ $task->comment_post_ID ]->tasks ) ) {
				$user_post_map[ $task->comment_post_ID ]->tasks = array();
			}

			$user_post_map[ $task->comment_post_ID ]->tasks[] = $task;
		}

		$post_vars = array();

		foreach ( $post_map as $series_post ) {
			$vars = array(
				'order' => $series_post->term_order,
				'title' => $series_post->post_title,
				'karma' => 'undefined',
				'tasks' => array(),
				'user_tasks' => array()
			);

			foreach ( $series_post->series_tasks as $task ) {
				$vars['tasks'][] = $task->task_content;
			}

			if ( isset( $user_post_map[ $series_post->ID ] ) ) {
				$vars['karma'] = $user_post_map[ $series_post->ID ]->comment_karma;

				foreach ( $user_post_map[ $series_post->ID ]->tasks as $task ) {
					$vars['user_tasks'][] = array(
						'order'   => $task->comment_meta['_index'],
						'content' => $task->comment_content,
						'karma'   => $task->comment_karma
					);
				}
			}

			$post_vars[] = $vars;
		}

		var_dump( $post_vars );
	}

	public function quit( $post ) {
		if ( $user_id = get_current_user_id() ) {
			$user_posts = $this->get_user_posts( $post );
			$user_tasks = $this->get_user_tasks( $post );

			$tasks = array_merge($user_posts, $user_tasks);

			foreach ( $tasks as $task ) {
				kickpress_delete_task( $task->comment_ID );
			}

			return true;
		}

		return false;
	}

	public function query_filter_fields( $fields ) {
		global $wpdb;
		return "$fields, $wpdb->term_relationships.term_order";
	}

	public function query_filter_order( $orderby ) {
		global $wpdb;
		return "$wpdb->term_relationships.term_order ASC";
	}

	protected function _truncate( $slug, $length = 200 ) {
		if ( strlen( $slug ) > $length ) {
			$decoded_slug = urldecode( $slug );
			if ( $decoded_slug === $slug )
				$slug = substr( $slug, 0, $length );
			else
				$slug = utf8_uri_encode( $decoded_slug, $length );
		}

		return rtrim( $slug, '-' );
	}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class kickpress_series_post_list_table extends WP_List_Table {
	private $_handler;

	function __construct( $handler = null ) {
		parent::__construct( array(
			'singular' => 'series-post',
			'plural'   => 'series-posts'
		) );

		$this->_handler = $handler;
	}

	function display() {
		$this->prepare_items();
		$this->display_hidden();

		parent::display();
	}

	/**
	 * Over-riding base class to remove nonce fields
	 */
	function display_tablenav( $which ) {
?>
	<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
<?php
		$this->extra_tablenav( $which );
		$this->pagination( $which );
?>

		<br class="clear" />
	</div>
<?php
	}

	function display_hidden() {
		$post = get_post( $this->get_var( 'post' ) );
		printf( '<input type="hidden" name="data[%1$s][%2$d][term_order][]" value="0" id="series-term-order-name">'
			. '<input type="hidden" value="data[%1$s][%2$d][post_tasks]" id="series-post-task-prefix">',
			$post->post_type, $post->ID );
	}

	function prepare_items() {
		global $post;

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns()
		);

		$per_page = -1;

		$this->items = $this->_handler->get_posts( $post, array(
			'post_status' => array( 'publish', 'future' ),
			'posts_per_page' => $per_page
		), $total_items );

		$total_pages = ceil( $total_items / $per_page );
		$total_pages = 1;

		$this->set_pagination_args( compact( 'total_items', 'total_pages', 'per_page' ) );
	}

	function get_var( $key, $default = false ) {
		return ! empty( $_GET[ $key ] ) ? $_GET[ $key ] : $default;
	}

	function get_bulk_actions() {
		return array();
	}

	function get_table_classes() {
		$classes = parent::get_table_classes();
		$classes[] = 'series-term-order';

		return $classes;
	}

	function get_columns() {
		return array(
			'order'  => 'Order',
			'title'  => 'Title',
			'tasks'  => 'Tasks',
			'author' => 'Author',
			'date'   => 'Date'
		);
	}

	function get_sortable_columns() {
		return array();
	}

	function column_order( $item ) {
		return sprintf( '<span class="term-order">%d</span>'
			. '<input type="hidden" value="%d">',
			isset( $item->term_order ) ? $item->term_order + 1 : 0,
			$item->ID
		);
	}

	function column_title( $item ) {
		$actions = array(
			'up'     => '<a href="#" class="term-order-up">&#x25b2; Move Up</a>',
			'down'   => '<a href="#" class="term-order-down">&#x25bc; Move Down</a>',
			'remove' => '<a href="#" class="remove-post">&#x2718; Remove</a>'
		);

		return sprintf( '<a href="post.php?post=%d&action=edit">%s</a> %s',
			$item->ID, $item->post_title, $this->row_actions( $actions ) );
	}

	function column_tasks( $item ) {
		$post = get_post( $this->get_var( 'post' ) );

		$meta_key = get_post_meta( $post->ID, '_series_meta_key', true );

		$tasks = empty( $meta_key ) ? array()
			: get_post_meta( $item->ID, $meta_key, false );

		$html = '<ul class="form-wrap">';

		foreach ( $item->series_tasks as $task ) {
			$html .= $this->column_tasks_item( $item, $post, $task );
		}

		$html .= $this->column_tasks_item( $item, $post, (object) array(
			'task_content' => ''
		), 'blank-task' );

		$html .= '<a href="#" class="add-task">+ Add New Task</a></ul>';

		return $html;
	}

	function column_tasks_item( $item, $post, $task, $class = '' ) {
		return sprintf( '<li class="task-wrap %5$s">'
			. '<div class="task-preview">'
			. '<div class="task-content">%4$s</div>'
			. '<div class="task-actions">'
			. '<a href="#" class="edit-task">&#x270E; Edit</a> | '
			. '<a href="#" class="remove-task">&#x2717; Remove</a>'
			. '</div>'
			. '</div>'
			. '<div class="form-field task-field">'
			. '<textarea name="data[%1$s][%2$d][post_tasks][%3$d][]" class="task-content" placeholder="Task Description">%4$s</textarea>'
			. '<div class="task-actions">'
			. '<a href="#" class="done-task">&#x2713; Done</a> | '
			. '<a href="#" class="cancel-task">&#x2717; Cancel</a>'
			. '</div>'
			. '</div>'
			. '</li>',
			$post->post_type,
			$post->ID,
			$item->ID,
			$task->task_content,
			$class );
	}

	function column_author( $item ) {
		$user = get_userdata( $item->post_author );
		$user_name = ! empty( $user->display_name )
			? $user->display_name : $user->user_login;

		return sprintf( '<a href="edit.php?post_type=%s&author=%d">%s</a>',
			$item->post_type, $item->post_author, $user_name );
	}

	function column_date( $item ) {
		return date( 'Y/m/d', strtotime( $item->post_date ) );
	}

	function column_default( $item, $column_name ) {
		return $item->$column_name;
	}
}

class kickpress_series_search_list_table extends kickpress_series_post_list_table {
	private $_taxonomy;

	function __construct( $taxonomy ) {
		parent::__construct();

		$this->_taxonomy = $taxonomy;
	}

	function display_hidden() {
	}

	function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns()
		);

		$per_page = 10;

		$args = array(
			'post_type'      => $this->_taxonomy->object_type,
			'post_status'    => array( 'publish', 'future' ),
			'posts_per_page' => $per_page,
			'paged'          => $this->get_var( 'paged', 1 ),
			'orderby'        => $this->get_var( 'orderby', 'title' ),
			'order'          => $this->get_var( 'order', 'asc' ),
			's'              => $this->get_var( 's' )
		);

		add_filter( 'posts_search', array( $this, 'filter_search'), 10, 2 );

		$query = new WP_Query( $args );

		$this->items = $query->get_posts();

		remove_filter( 'posts_search', array( $this, 'filter_search' ) );

		$total_items = $query->found_posts;
		$total_pages = ceil( $total_items / $per_page );

		$this->set_pagination_args( compact( 'total_items', 'total_pages', 'per_page' ) );
	}

	function column_tasks( $item ) {
		return sprintf( '<ul class="form-wrap">'
			. '<li class="task-wrap blank-task">'
			. '<div class="task-preview">'
			. '<div class="task-content"></div>'
			. '<div class="task-actions">'
			. '<a href="#" class="edit-task">&#x270E; Edit</a> | '
			. '<a href="#" class="remove-task">&#x2717; Remove</a>'
			. '</div>'
			. '</div>'
			. '<div class="form-field task-field">'
			. '<textarea name="[%1$d][]" class="task-content" placeholder="Task Description"></textarea>'
			. '<div class="task-actions">'
			. '<a href="#" class="done-task">&#x2713; Done</a> | '
			. '<a href="#" class="cancel-task">&#x2717; Cancel</a>'
			. '</div>'
			. '</div>'
			. '</li>'
			. '<a href="#" class="add-task">+ Add New Task</a>'
			. '</ul>',
			$item->ID );
	}

	function filter_search( $search, $query ) {
		global $wpdb;

		if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $query->query_vars['s'], $matches ) ) {
			$terms   = $matches[0];
			$clauses = array();

			foreach ( $terms as $term ) {
				$term = '%' . $wpdb->esc_like( $term ) . '%';
				$clauses[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $term );
			}

			$search = " AND (" . implode( " AND ", $clauses ) . ")";
		}

		return $search;
	}

	function get_table_classes() {
		$classes = parent::get_table_classes();

		if ( ( $index = array_search( 'series-term-order', $classes ) ) !== false ) {
			unset( $classes[$index] );
		}

		return $classes;
	}
}
