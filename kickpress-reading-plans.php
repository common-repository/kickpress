<?php

add_filter( 'posts_fields', 'kickpress_reading_plans_query_fields_front', 10, 2 );
add_filter( 'posts_orderby', 'kickpress_reading_plans_query_order_front', 10, 2 );

add_action( 'init', 'kickpress_init_reading_plans' );

add_action( 'admin_menu', 'kickpress_reading_plans_menu' );

add_action( 'wp_ajax_search_posts', 'kickpress_reading_plans_search_posts' );

function kickpress_init_reading_plans() {
	if ( function_exists( 'register_taxonomy' ) ) {
		register_taxonomy( 'reading-plans', 'post', array(
			'label' => 'Reading Plans',
			'labels' => array(
				'name' => 'Readings Plans',
				'singular_name' => 'Reading Plan',
				'menu_name' => 'Reading Plans',
				'all_items' => 'All Reading Plans',
				'edit_item' => 'Edit Reading Plan',
				'view_item' => 'View Reading Plan',
				'update_item' => 'Update Reading Plan',
				'add_new_item' => 'Add New Reading Plan',
				'new_item_name' => 'New Reading Plan Name',
				'search_items' => 'Search Reading Plans',
				'popular_items' => 'Popular Reading Plans'
			),
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
}

function kickpress_get_reading_plan( $term_id ) {
	return get_term( $term_id, 'reading-plans' );
}

function kickpress_get_reading_steps( $args = array() ) {
	if ( $term = kickpress_get_reading_plan( $args['term_id'] ) ) {
		$args['meta_key']   = $term->taxonomy;
		$args['meta_value'] = $term->slug;
	}

	return kickpress_get_private_comments( 'reading-step', $args );
}

function kickpress_insert_reading_step( $post_id, $term_id, $karma = 0 ) {
	if ( 0 > $karma || 1 < $karma ) return false;

	if ( is_user_logged_in() && $post = get_post( $post_id ) && $term = kickpress_get_reading_plan( $term_id ) ) {
		$steps = kickpress_get_reading_steps( array(
			'post_id' => $post_id,
		) );

		if ( empty( $steps ) ) {
			return kickpress_insert_private_comment( $post_id, array(
				'comment_content' => '[Reading Plan] ' . $term->name . ': ' . $post->post_title,
				'comment_type'    => 'reading-step',
				'comment_karma'   => intval( round( $karma ) ),
				'comment_meta'    => array( $term->taxonomy => $term->slug )
			) );
		} else {
			$steps[0]->comment_meta[$term->taxonomy][$term->slug];
			return kickpress_update_private_comment( $steps[0]->comment_ID, (array) $steps[0] );
		}
	}
}

function kickpress_update_reading_step( $comment_id, $karma = 0 ) {
	if ( 0 > $karma || 1 < $karma ) return false;

	if ( is_user_logged_in() && $comment = get_comment( $comment_id ) ) {
		return kickpress_update_private_comment( $comment_id, array(
			'comment_type'  => 'reading-step',
			'comment_karma' => intval( round( $karma ) )
		) );
	}
}

function kickpress_delete_reading_step( $post_id ) {
	if ( is_user_logged_in() && $post = get_post( $post_id ) ) {
		$steps = get_comments( array(
			'post_id' => $post_id,
			'user_id' => get_current_user_id(),
			'status'  => 'private',
			'type'    => 'reading-step'
		) );

		foreach ( $steps as $step ) {
			wp_delete_comment( $step->comment_ID, true );
		}
	}
}

function kickpress_reading_plans_query_search( $search, $query ) {
	global $wpdb;

	if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $query->query_vars['s'], $matches ) ) {
		$terms = $query->parse_search_terms( $matches[0] );

		$clauses = array();

		foreach ( $terms as $term ) {
			$term = '%' . $wpdb->esc_like( $term ) . '%';
			$clauses[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $term );
		}

		$search = " AND (" . implode( " AND ", $clauses ) . ")";
	}

	return $search;
}

function kickpress_reading_plans_query_fields( $fields ) {
	global $wpdb;
	return "$fields, $wpdb->term_relationships.term_order";
}

function kickpress_reading_plans_query_fields_front( $fields, $query ) {
	if ( ! is_admin() && is_tax( 'reading-plans' ) && $query->is_main_query() ) {
		global $wpdb;
		return "$fields, $wpdb->term_relationships.term_order";
	}

	return $fields;
}

function kickpress_reading_plans_query_order( $order ) {
	global $wpdb;
	return "$wpdb->term_relationships.term_order ASC";
}

function kickpress_reading_plans_query_order_front( $order, $query ) {
	if ( ! is_admin() && is_tax( 'reading-plans' ) && $query->is_main_query() ) {
		global $wpdb;
		return "$wpdb->term_relationships.term_order ASC";
	}

	return $order;
}

function kickpress_reading_plans_get_posts( $term_id, $args = array(), &$count ) {
	$defaults = array(
		'suppress_filters' => false,
		'post_type' => 'post',
		'tax_query' => array(
			array(
				'taxonomy' => 'reading-plans',
				'field' => 'term_id',
				'terms' => $term_id
			)
		)
	);

	$args = wp_parse_args( $args, $defaults );

	add_filter( 'posts_fields', 'kickpress_reading_plans_query_fields' );
	add_filter( 'posts_orderby', 'kickpress_reading_plans_query_order' );

	$query = new WP_Query( $args );
	$posts = $query->get_posts();
	$count = $query->found_posts;

	remove_filter( 'posts_fields', 'kickpress_reading_plans_query_fields' );
	remove_filter( 'posts_orderby', 'kickpress_reading_plans_query_order' );

	return $posts;
}

function kickpress_reading_plans_search_posts() {
	if ( ! empty( $_REQUEST['search'] ) ) {
		$args = array(
			's' => $_REQUEST['search'],
			'post_type'   => 'post',
			'post_status' => 'publish',
			'posts_per_page' => 20
		);

		if ( isset( $_REQUEST['paged'] ) ) {
			$args['offset'] = ( $_REQUEST['paged'] - 1 ) * 20;
		}

		add_filter( 'posts_search', 'kickpress_reading_plans_query_search', 10, 2 );

		$query = new WP_Query( $args );
		$posts = $query->get_posts();
		$count = $query->found_posts;

		remove_filter( 'posts_search', 'kickpress_reading_plans_query_search' );

		$table = new kickpress_reading_plans_post_list_table( $posts, $count, 'term', 'asc', 'add' );
		$table->display();
	} else {
		echo 'No matches found.';
	}

	exit;
}

function kickpress_reading_plans_menu() {
	add_posts_page( 'Reading Plans', 'Reading Plans', 'manage_reading_plans',
		'reading-plans', 'kickpress_reading_plans_menu_callback' );
}

function kickpress_reading_plans_menu_callback() {
	global $wpdb;

	$query = <<<SQL
SELECT p.post_title, p.post_date, tt.taxonomy, t.slug AS term, tr.term_order
FROM $wpdb->terms AS t
INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
INNER JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
INNER JOIN $wpdb->posts AS p ON tr.object_id = p.ID
WHERE tt.taxonomy = 'reading-plans'
AND p.post_type IN ('post')
AND p.post_status IN ('publish')
ORDER BY tr.term_order
SQL;

// echo "<pre>$query</pre>";

	$query = <<<SQL
SELECT p.post_title, p.post_date, tt.taxonomy, t.slug AS term, tr.term_order, c.comment_karma
FROM $wpdb->terms AS t
INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
INNER JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
INNER JOIN $wpdb->posts AS p ON tr.object_id = p.ID
INNER JOIN $wpdb->comments AS c ON p.ID = c.comment_post_ID
INNER JOIN $wpdb->commentmeta AS cm ON c.comment_ID = cm.comment_id
AND cm.meta_key = tt.taxonomy AND cm.meta_value = t.slug
WHERE tt.taxonomy = 'reading-plans'
AND p.post_type IN ('post')
AND p.post_status IN ('publish')
ORDER BY t.slug, tr.term_order
SQL;

// echo "<pre>$query</pre>";

	$action = $real_action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'default';

	$nonce   = @$_REQUEST['_wpnonce'];
	$referer = @$_REQUEST['_wp_http_referer'];

	if ( 'update-posts' == $action ) {
		$action = 'bulk-reading-posts';
	}

	if ( wp_verify_nonce( $nonce, $action ) ) {
		$action = $real_action;

		$func = "kickpress_reading_plans_menu_" . str_replace( '-', '_', $action );

		if ( function_exists( $func ) ) {
			call_user_func( $func );

			die( "<script type='text/javascript'>document.location = '$referer';</script>" );
		}
	}

	$view = isset( $_REQUEST['view'] ) ? $_REQUEST['view'] : 'default';
	$func = "kickpress_reading_plans_menu_" . str_replace( '-', '_', $view );

	if ( function_exists( $func ) )
		call_user_func( $func );
	else
		kickpress_reading_plans_menu_default();
}

function kickpress_reading_plans_menu_default() {
	$list_table = new kickpress_reading_plans_list_table( @$_REQUEST['orderby'],  @$_REQUEST['order'] );
?>
<div class="wrap nosubsub">
	<h2>Reading Plans</h2>
	<div id="col-container">
		<div id="col-right">
			<div class="col-wrap">
				<form action="edit.php?page=reading-plans" method="post">
					<?php $list_table->display(); ?>
				</form>
			</div>
		</div><!-- /col-right -->
		<div id="col-left">
			<div class="col-wrap">
				<div class="form-wrap">
					<h3>Add New Reading Plan</h3>
					<form action="edit.php?page=reading-plans" method="post">
						<input type="hidden" name="action" value="add">
						<?php wp_nonce_field( 'add' ); ?>
						<div class="form-field form-required">
							<label for="reading-plan-name">Name</label>
							<input name="reading-plan[name]" id="reading-plan-name" type="text" size="40" aria-required="true">
							<p>The name is how it appears on your site.</p>
						</div>
						<div class="form-field">
							<label for="reading-plan-slug">Slug</label>
							<input name="reading-plan[slug]" id="reading-plan-slug" type="text" size="40">
							<p>The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</p>
						</div>
						<div class="form-field">
							<label for="reading-plan-description">Description</label>
							<textarea name="reading-plan[description]" id="reading-plan-description" rows="5" cols="40"></textarea>
							<p>The description is not prominent by default; however, some themes may show it.</p>
						</div>
						<p class="submit">
							<input type="submit" class="button button-primary" value="Add New Reading Plan">
						</p>
					</form>
				</div>
			</div>
		</div><!-- /col-left -->
	</div>
</div>
<?php
}

function kickpress_reading_plans_menu_edit() {
	$term = get_term( @$_REQUEST['id'], 'reading-plans' );

	$base_url = 'edit.php?page=reading-plans&id=' . $term->term_id;
?>
<div class="wrap">
	<h2>Edit Reading Plan</h2>
	<form action="edit.php?page=reading-plans" method="post" class="validate">
		<input type="hidden" name="action" value="update">
		<input type="hidden" name="id" value="<?php esc_attr_e( $term->term_id ); ?>">
		<?php wp_nonce_field( 'update' ); ?>
		<table class="form-table">
			<tr class="form-field form-required">
				<th scope="row">
					<label for="reading-plan-name">Name</label>
				</th>
				<td>
					<input id="reading-plan-name" type="text" size="40" name="reading-plan[name]" value="<?php esc_attr_e( $term->name ); ?>" aria-required="true">
					<p class="description">The name is how it appears on your site.</p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row">
					<label for="reading-plan-slug">Slug</label>
				</th>
				<td>
					<input id="reading-plan-slug" type="text" size="40" name="reading-plan[slug]" value="<?php esc_attr_e( $term->slug ); ?>">
					<p class="description">The “slug” is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</p>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row">
					<label for="reading-plan-description">Description</label>
				</th>
				<td>
					<textarea id="reading-plan-description" name="reading-plan[description]" rows="5" cols="50"><?php echo $term->description; ?></textarea>
					<p class="description">The description is not prominent by default; however, some themes may show it.</p>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="submit" class="button button-primary" value="Update">
		</p>
	</form>
</div>
<?php
}

function kickpress_reading_plans_menu_edit_posts() {
	$term = get_term( @$_REQUEST['id'], 'reading-plans' );

	$base_url = 'edit.php?page=reading-plans&id=' . $term->term_id;

	$limit = 20;
	$paged = isset( $_REQUEST['paged'] ) ? $_REQUEST['paged'] - 1 : 0;

	$posts = kickpress_reading_plans_get_posts( $term->term_id, array(
		'posts_per_page' => $limit,
		'offset' => $paged * $limit
	), $count );

	$table = new kickpress_reading_plans_post_list_table( $posts, $count );
?>
<div class="wrap nosubsub">
	<h2>Edit Reading Plan</h2>
	<div class="form-wrap">
		<form action="edit.php?page=reading-plans" method="post">
			<input type="hidden" name="action" value="update-posts">
			<input type="hidden" name="id" value="<?php esc_attr_e( $term->term_id ); ?>">
			<?php wp_nonce_field( 'update-posts' ); ?>
			<div id="reading-post-table"><?php $table->display(); ?></div>
			<p class="submit">
				<input type="submit" class="button button-primary" value="Update">
			</p>
		</form>
		<h3>Add Posts</h3>
		<input id="search-post-title" type="text" size="40">
		<input id="search-button" type="button" value="Search">
		<input id="search-clear" type="button" value="Clear">
		<span id="search-loading"></span>
		<div id="search-results"></div>
	</div>
</div>
<style type="text/css">
	.reading-posts .column-order { width: 50px; }
	.reading-posts tbody .column-order { text-align: center; }

	.reading-posts .row-actions a.disabled { color: #aaa; }

	#search-results .column-order,
	#search-results .row-actions .up,
	#search-results .row-actions .down {
		display: none;
	}
</style>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		function updatePostTable() {
			$('#reading-post-table .row-actions up').show();
			$('#reading-post-table .row-actions down').show();

			$('#reading-post-table tbody tr:even').addClass('alternate');
			$('#reading-post-table tbody tr:odd').removeClass('alternate');

			$('#reading-post-table tbody .term-order-up').removeClass('disabled');
			$('#reading-post-table tbody .term-order-down').removeClass('disabled')

			$('#reading-post-table tbody tr:first .term-order-up').addClass('disabled');
			$('#reading-post-table tbody tr:last .term-order-down').addClass('disabled');

			$('#reading-post-table tbody .term-order').each(function(index) {
				$(this).text(index + 1);
			});

			$('#reading-post-table .term-order-up').unbind('click');
			$('#reading-post-table .term-order-up').click(function(event) {
				if (!$(this).hasClass('disabled')) {
					var row = $(this).parents('tr').first();
					row.after(row.prev().first());
					updatePostTable();
				}

				return false;
			});

			$('#reading-post-table .term-order-down').unbind('click');
			$('#reading-post-table .term-order-down').click(function(event) {
				if (!$(this).hasClass('disabled')) {
					var row = $(this).parents('tr').first();
					row.before(row.next().first());
					updatePostTable();
				}

				return false;
			});

			$('#reading-post-table .remove-post').unbind('click');
			$('#reading-post-table .remove-post').click(function(event) {
				if (confirm('Are you sure you want to remove this post?')) {
					$(this).parents('tr').first().remove();
				}

				return false;
			});
		}

		function sendSearch(args) {
			if (typeof args == 'string')
				args = parseQuery(args);
			else if (typeof args == 'undefined')
				args = {};

			args['action'] = 'search_posts';
			args['search'] = $('#search-post-title').val();

			$('#search-post-title').attr('disabled', 'disabled');
			$('#search-button').attr('disabled', 'disabled');
			$('#search-clear').attr('disabled', 'disabled');

			$('#search-loading').text('Loading');
			var loadout = setInterval(function() {
				$('#search-loading').text($('#search-loading').text() + '.');
			}, 700);

			$.post(ajaxurl, args, function(response) {
				$('#search-post-title').attr('disabled', false);
				$('#search-button').attr('disabled', false);
				$('#search-clear').attr('disabled', false);

				clearInterval(loadout);
				$('#search-loading').text('');
				$('#search-results').html(response);

				$('#search-results .add-post').click(function(event) {
					var row = $(this).parents('tr').first();
					$('#reading-post-table tbody').append(row);

					$(this).removeClass('add-post')
						.addClass('remove-post')
						.html('&#x2718; Remove');

					updatePostTable();

					return false;
				});

				$('#search-results .pagination-links a').click(function(event) {
					if (!$(this).hasClass('disabled'))
						sendSearch(this.search.substr(1));

					return false;
				});
			});
		}

		function parseQuery(query) {
			var params = {};

			var pairs = query.split('&');

			for ( var index in pairs ) {
				var pair = pairs[index].split('=');
				params[pair[0]] = pair[1];
			}

			return params;
		}

		$('#search-button').click(function(event) {
			sendSearch();
		});

		$('#search-clear').click(function(event) {
			$('#search-post-title').val('');
			$('#search-results').html('');
		});

		updatePostTable();
	});
</script>
<?php
}

function kickpress_reading_plans_menu_add() {
	if ( is_array( $reading_plan = @$_REQUEST['reading-plan'] ) ) {
		wp_insert_term( @$reading_plan['name'], 'reading-plans', $reading_plan );
	}
}

function kickpress_reading_plans_menu_update() {
	if ( is_array( $reading_plan = @$_REQUEST['reading-plan'] ) ) {
		wp_update_term( @$_REQUEST['id'], 'reading-plans', $reading_plan );
	}
}

function kickpress_reading_plans_menu_update_posts() {
	if ( is_array( $posts = @$_REQUEST['reading-posts'] ) ) {
		global $wpdb;

		$term = get_term( @$_REQUEST['id'], 'reading-plans' );

		$wpdb->delete( $wpdb->term_relationships, array(
			'term_taxonomy_id' => $term->term_taxonomy_id
		) );

		foreach ( $posts as $index => $post_id ) {
			$wpdb->insert( $wpdb->term_relationships, array(
				'object_id' => $post_id,
				'term_taxonomy_id' => $term->term_taxonomy_id,
				'term_order' => $index
			) );
		}

		$wpdb->update( $wpdb->term_taxonomy, array(
			'count' => count( $posts )
		), array(
			'term_taxonomy_id' => $term->term_taxonomy_id
		) );
	}
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class kickpress_reading_plans_list_table extends WP_List_Table {
	function __construct( $orderby = 'name', $order = 'asc' ) {
		$orderby = ! empty( $orderby ) ? $orderby : 'name';
		$order   = ! empty( $order )   ? $order   : 'asc';

		parent::__construct( array(
			'singular' => 'reading-plan',
			'plural'   => 'reading-plans',
			'orderby'  => $orderby,
			'order'    => $order
		) );
	}

	function display() {
		$current_action = $this->current_action();
		if ( ! empty( $current_action ) ) {
			$nonce   = @$_REQUEST['_wpnonce'];
			$referer = @$_REQUEST['_wp_http_referer'];

			if ( wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
				$this->do_bulk_action( $this->current_action() );

				if ( ! empty( $referer ) ) {
					die( '<script type="text/javascript">'
					   . 'document.location = "' . esc_attr( $referer ) . '";'
					   . '</script>' );
				}
			}
		}

		$this->prepare_items();

		parent::display();
	}

	function prepare_items() {
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, array(), $sortable );

		$this->items = get_terms( 'reading-plans', array(
			'orderby' => $this->_args['orderby'],
			'order'   => strtoupper( $this->_args['order'] ),
			'hide_empty' => false
		) );

		$per_page = 20;

		$total_items = count( $this->items );
		$total_pages = ceil( $total_items / $per_page );

		$pagination_args = compact( 'total_items', 'total_pages', 'per_page' );

		$this->set_pagination_args( $pagination_args );
	}

	function get_columns() {
		return array(
			'cb'          => '<input type="checkbox">',
			'name'        => 'Name',
			'description' => 'Description',
			'slug'        => 'Slug',
			'posts'       => 'Count'
		);
	}

	function get_sortable_columns() {
		return array(
			'name' => array( 'name', 'name' == $this->_args['orderby'] ),
			'slug' => array( 'slug', 'slug' == $this->_args['orderby'] )
		);
	}

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="reading_plan[]" value="%1$d" id="cb-select-%1$d">',
			$item->term_taxonomy_id
		);
	}

	function column_name( $item ) {
		$referer = 'edit.php?page=reading-plans';

		$edit_url = $referer . '&view=edit&id=' . $item->term_id;
		$posts_url = $referer . '&view=edit-posts&id=' . $item->term_id;
		$delete_url = $referer . '&action=delete&id=' . $item->term_id;

		$actions = array(
			'edit' => sprintf( '<a href="%s">Edit</a>',
				esc_attr( $edit_url )
			),
			'edit-posts' => sprintf( '<a href="%s">Manage Posts</a>',
				esc_attr( $posts_url )
			),
			'delete' => sprintf( '<a href="%s">Delete</a>',
				wp_nonce_url( $delete_url, 'single-' . $this->_args['singular'] )
			)
		);

		return sprintf( '<a href="%s">%s</a> %s', $edit_url, $item->name,
			$this->row_actions( $actions ) );
	}

	function column_posts( $item ) {
		return sprintf( '<a href="edit.php?page=reading-plans&view=edit-posts&id=%d">%d</a>',
			$item->term_id, $item->count
		);
	}

	function column_default( $item, $column_name ) {
		return $item->$column_name;
	}

	function get_bulk_actions() {
		return array(
			'delete' => 'Delete'
		);
	}

	function do_bulk_action( $action ) {
		switch ( $action ) {
			case 'delete':
				$option = get_option( 'kickpress_taxonomies', array() );

				foreach ( (array) @$_REQUEST['taxonomy'] as $slug => $name ) {
					unset( $option[$slug] );
				}

				update_option( 'kickpress_taxonomies', $option );

				break;
		}
	}
}

class kickpress_reading_plans_post_list_table extends WP_List_Table {
	function __construct( $posts, $total, $orderby = 'term', $order = 'asc', $action = '' ) {
		parent::__construct( array(
			'singular' => 'reading-post',
			'plural'   => 'reading-posts',
			'orderby'  => ! empty( $orderby ) ? $orderby : 'term',
			'order'    => ! empty( $order )   ? $order   : 'asc',
			'action'   => $action
		) );

		$this->items = $posts;

		$this->set_pagination_args( array(
			'total_items' => $total,
			'total_pages' => ceil( $total / 20 ),
			'per_page'    => 20
		) );
	}

	function display() {
		$this->prepare_items();

		parent::display();
	}

	function prepare_items() {
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, array(), $sortable );
	}

	function get_columns() {
		return array(
			'order' => 'Order',
			'title' => 'Title',
			'pubdate' => 'Date'
		);
	}

	function get_sortable_columns() {
		return array();
	}

	function column_order( $item ) {
		if ( isset( $item->term_order ) ) {
			return sprintf( '<span class="term-order">%d</span>'
				. '<input type="hidden" name="reading-posts[]" value="%d">',
				$item->term_order + 1, $item->ID
			);
		} else {
			return sprintf( '<span class="term-order"></span>'
				. '<input type="hidden" name="reading-posts[]" value="%d">',
				$item->ID
			);
		}
	}

	function column_title( $item ) {
		$actions = array(
			'up'   => '<a href="#" class="term-order-up">&#x25b2; Move Up</a>',
			'down' => '<a href="#" class="term-order-down">&#x25bc; Move Down</a>'
		);

		if ( 'add' == $this->_args['action'] ) {
			$actions['edit'] = '<a href="#" class="add-post">&#x2714; Add</a>';
		} else {
			$actions['edit'] = '<a href="#" class="remove-post">&#x2718; Remove</a>';
		}

		return sprintf( '<span>%s</span> %s', $item->post_title,
			$this->row_actions( $actions ) );
	}

	function column_pubdate( $item ) {
		return date( "Y/m/d", strtotime( $item->post_date ) );
	}

	function column_default( $item, $column_name ) {
		return $item->$column_name;
	}
}
