<?php

function kickpress_init_taxonomies() {
	$taxonomies = get_option( 'kickpress_taxonomies', array() );

	foreach ( $taxonomies as $slug => $args ) {
		$name        = sanitize_title( $slug );
		$object_type = $args['object_type'];

		register_taxonomy( $name, $object_type, $args );
	}
}

function kickpress_taxonomies_menu_page() {
	add_submenu_page( 'edit.php?post_type=custom-post-types', 'Taxonomies', 'Taxonomies',
		'manage_options', 'taxonomies', 'kickpress_taxonomies_menu_page_callback' );
}

function kickpress_taxonomies_menu_page_callback() {
	kickpress_taxonomies_process_single_action();

	$view = isset( $_REQUEST['view'] ) ? $_REQUEST['view'] : 'list';

	$callback = 'kickpress_taxonomies_' . $view . '_view';

	if ( function_exists( $callback ) )
		call_user_func( $callback );
	else
		kickpress_taxonomies_list_view();
}

function kickpress_taxonomies_list_view() {
	$table = new kickpress_taxonomies_list_table();
?>
<div class="wrap">
	<div id="icon-edit" class="icon32"><br></div>
	<h2>Taxonomies <a href="edit.php?post_type=custom-post-types&page=taxonomies&view=edit" class="add-new-h2">Add New</a></h2>
	<div id="ajax-response"></div>
	<form action="edit.php?post_type=custom-post-types&page=taxonomies" method="post">
		<input type="hidden" name="post_type" value="custom-post-types">
		<input type="hidden" name="page" value="taxonomies">
		<?php $table->display(); ?>
	</form>
</div>
<?php
}

function kickpress_taxonomies_edit_view() {
	global $kickpress_post_types;

	$taxonomies = get_option( 'kickpress_taxonomies', array() );

	$slug = sanitize_title( @$_REQUEST['slug'] );

	if ( isset( $taxonomies[$slug] ) ) {
		$taxonomy = $taxonomies[$slug];
	} else {
		$taxonomy = array(
			'object_type'       => array(),
			'labels'            => array(),
			'show_in_nav_menus' => true,
			'show_ui'           => true,
			'show_tagcloud'     => true,
			'hierarchical'      => false
		);
	}

	$label_keys   = kickpress_taxonomies_labels();
	$setting_keys = kickpress_taxonomies_settings();

	$label_defaults = array(
		'name'                       => '%P',
		'menu_name'                  => '%P',
		'singular_name'              => '%S',
		'search_items'               => 'Search %P',
		'popular_items'              => 'Popular %P',
		'all_items'                  => 'All %P',
		'parent_item'                => 'Parent %S',
		'parent_item_colon'          => 'Parent %S:',
		'edit_item'                  => 'Edit %S',
		'view_item'                  => 'View %S',
		'update_item'                => 'Update %S',
		'new_item_name'              => 'New %S',
		'add_new_item'               => 'Add New %S',
		'separate_items_with_commas' => 'Separate %p with commas',
		'add_or_remove_items'        => 'Add or remove %p',
		'choose_from_most_used'      => 'Choose from most used %p',
		'name_admin_bar'             => '%P'
	);
?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#kp-gen-labels').click(function(event) {
			var title = $('#title').val();

			var plural_title = title;
			var single_title = title;

			if ('s' == title.charAt(title.length - 1)) {
				single_title = title.substr(0, title.length - 1);
			} else {
				plural_title = title + 's';
			}

			$('#kp-labels-table input[type="text"]').each(function() {
				$(this).val($(this).data('default')
					.replace('%S', single_title)
					.replace('%s', single_title.toLowerCase())
					.replace('%P', plural_title)
					.replace('%p', plural_title.toLowerCase())
				);
			});
		});
	});
</script>
<div class="wrap">
	<div id="icon-edit" class="icon32"><br></div>
	<h2>Edit Taxonomy</h2>
	<form action="edit.php?post_type=custom-post-types&page=taxonomies" method="post">
		<input type="hidden" name="post_type" value="custom-post-types">
		<input type="hidden" name="page" value="taxonomies">
		<input type="hidden" name="action" value="save">
		<?php wp_nonce_field( 'single-taxonomy' ); ?>
		<div class="metabox-holder has-right-sidebar">
			<div id="side-info-column" class="inner-sidebar">
				<div id="side-sortables" class="meta-box-sortables ui-sortable">
					<div class="postbox">
						<div class="handlediv" title="Click to toggle"><br></div>
						<h3 class="hndle"><span>Save Taxonomy</span></h3>
						<div class="inside">
							<input type="submit" value="Save Changes" class="button-primary">
						</div>
					</div>
					<div class="postbox">
						<div class="handlediv" title="Click to toggle"><br></div>
						<h3 class="hndle"><span>Object Type(s)</span></h3>
						<div class="inside">
							<ul>
								<li>
									<input id="object-type-post" type="checkbox"
										name="tax[object_type][]" value="post"<?php
										echo in_array( 'post', $taxonomy['object_type'] )
										   ? ' checked="checked"' : ''; ?>>
									<label for="object-type-post">Post</label>
								</li>
								<li>
									<input id="object-type-page" type="checkbox"
										name="tax[object_type][]" value="page"<?php
										echo in_array( 'page', $taxonomy['object_type'] )
										   ? ' checked="checked"' : ''; ?>>
									<label for="object-type-page">Page</label>
								</li>
								<?php foreach ( $kickpress_post_types as $post_type ) :
									$name    = $post_type['post_type'];
									$title   = $post_type['post_type_title'];
									$checked = in_array( $name, $taxonomy['object_type'] )
											 ? ' checked="checked"' : '';
								?>
								<li>
									<input id="object-type-<?php echo $name; ?>" type="checkbox"
										name="tax[object_type][]" value="<?php echo $name; ?>"<?php echo $checked; ?>>
									<label for="object-type-<?php echo $name; ?>"><?php echo $title; ?></label>
								</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
					<div class="postbox">
						<div class="handlediv" title="Click to toggle"><br></div>
						<h3 class="hndle"><span>Settings</span></h3>
						<div class="inside">
							<ul>
								<?php foreach ( $setting_keys as $key => $descrip ) :
									$title   = ucwords( str_replace( '_', ' ', $key ) );
									$checked = $taxonomy[$key] ? ' checked="checked"' : '';
								?>
								<li>
									<input id="setting-<?php echo $key; ?>" type="checkbox"
										name="tax[<?php echo $key; ?>]"<?php echo $checked; ?>>
									<label for="setting-<?php echo $key; ?>" title="<?php echo esc_attr( $descrip ); ?>"><?php echo $title; ?></label>
								</li>
								<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<div id="post-body">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<label for="title" id="title-prompt-text" class="hide-if-no-js" style="visibility: hidden;">Enter title here</label>
							<input id="title" type="text" name="title" value="<?php echo esc_attr( $taxonomy['labels']['name'] ); ?>">
						</div>
						<!-- <div class="inside">
							<div id="edit-slug-box">
								<strong>Permalink:</strong>
								<span id="sample-permalink">http://wordpress.dev/?post_type=post_type&amp;p=7</span>
								<span id="view-post-btn"><a href="http://wordpress.dev/?post_type=post_type&amp;p=7" class="button" target="_blank">View Post Type</a></span>
							</div>
						</div> -->
					</div>
					<div class="meta-box-sortables ui-sortable">
						<div class="postbox">
							<div class="handlediv" title="Click to toggle"><br></div>
							<h3 class="hndle"><span>Labels</span></h3>
							<div class="inside">
								<span class="description">Labels can be generated from title.</span>
								<input id="kp-gen-labels" type="button" value="Generate" class="button">
								<table id="kp-labels-table" class="kp-form-table">
									<thead>
										<tr>
											<th class="snap">Label Name</th>
											<th class="snap">Label</th>
											<th>Description</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $label_keys as $key => $descrip ) :
											$title = ucwords( str_replace( '_', ' ', $key ) );
											$value = @$taxonomy['labels'][$key];
										?>
										<tr>
											<td class="snap">
												<label for="labels-<?php echo $key; ?>"><?php echo $title; ?></label>
											</td>
											<td class="snap">
												<input id="labels-<?php echo $key; ?>" type="text"
													name="tax[labels][<?php echo $key; ?>]"
													value="<?php echo esc_attr( $value ); ?>"
													data-default="<?php echo $label_defaults[$key]; ?>">
											</td>
											<td class="description"><?php echo $descrip; ?></td>
										</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
			<br class="clear">
		</div>
	</form>
</div>
<?php
}

function kickpress_taxonomies_process_single_action() {
	$nonce = @$_REQUEST['_wpnonce'];
	$url   = @$_REQUEST['_wp_http_referer'];

	if ( isset( $_REQUEST['action'] ) && wp_verify_nonce( $nonce, 'single-taxonomy' ) ) {
		switch ( $_REQUEST['action'] ) {
			case 'save':
				$taxonomy = $_REQUEST['tax'];
				$settings = kickpress_taxonomies_settings();

				foreach ( $settings as $key => $descrip )
					$taxonomy[$key] = isset( $taxonomy[$key] );

				if ( ! isset( $taxonomy['object_type'] ) )
					$taxonomy['object_type'] = array();

				$slug = sanitize_title( $taxonomy['labels']['name'] );

				if ( ! empty( $slug ) ) {
					$option = get_option( 'kickpress_taxonomies', array() );
					$option[$slug] = $taxonomy;

					update_option( 'kickpress_taxonomies', $option );
				}

				break;
			case 'delete':
				$slug = sanitize_title( @$_REQUEST['slug'] );

				if ( ! empty( $slug ) ) {
					$option = get_option( 'kickpress_taxonomies', array() );

					unset( $option[$slug] );

					update_option( 'kickpress_taxonomies', $option );
				}

				break;
		}

		if ( ! empty( $url ) )
			die( '<script type="text/javascript">'
			   . 'document.location = "' . $url . '";'
			   . '</script>' );
	}
}

function kickpress_taxonomies_labels() {
	return array(
		'name'                       => 'Caption used for this taxonomy, usually plural',
		'menu_name'                  => 'Caption used for this taxonomy in admin menu',
		'singular_name'              => 'Caption used for a single term of this taxonomy',
		'search_items'               => 'Caption used for search box',
		'popular_items'              => 'Caption used for popular terms',
		'all_items'                  => 'Caption used for all terms',
		'parent_item'                => 'Caption used for parent term',
		'parent_item_colon'          => 'Caption used for parent term with colon',
		'edit_item'                  => 'Caption used for editing a single term',
		'view_item'                  => 'Caption used for viewing a single term',
		'update_item'                => 'Caption used for saving a single term',
		'new_item_name'              => 'Caption used for a new term',
		'add_new_item'               => 'Caption used for adding a new term',
		'separate_items_with_commas' => '',
		'add_or_remove_items'        => 'Caption used for adding/removing a term from a post',
		'choose_from_most_used'      => 'Caption for the most commonly used terms',
		'name_admin_bar'             => ''
	);
}

function kickpress_taxonomies_settings() {
	return array(
		'show_ui'           => 'This taxonomy will have an admin interface',
		'show_in_nav_menus' => 'This taxonomy can be used in nav menus',
		'show_tagcloud'     => 'This taxonomy can be used in the tag cloud widget',
		'hierarchical'      => 'This taxonomy is hierarchical'
	);
}

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class kickpress_taxonomies_list_table extends WP_List_Table {
	var $_post_type = 'custom-post-types';
	var $_menu_slug = 'taxonomies';

	function __construct() {
		parent::__construct( array(
			'singular' => 'taxonomy',
			'plural'   => 'taxonomies'
		) );
	}

	function display() {
		$this->process_bulk_action();
		$this->prepare_items();

		parent::display();
	}

	function prepare_items() {
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, array(), $sortable );

		$this->items = array();

		$option = get_option( 'kickpress_taxonomies', array() );

		foreach ( $option as $slug => $args ) {
			$this->items[] = array(
				'slug'        => $slug,
				'name'        => $args['labels']['name'],
				'object_type' => $args['object_type']
			);
		}

		$per_page = 20;

		$total_items = count( $this->items );
		$total_pages = ceil( $total_items / $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page'    => $per_page
		) );
	}

	function get_columns() {
		return array(
			'cb'          => '<input type="checkbox">',
			'name'        => 'Name',
			'object_type' => 'Object Type(s)'
		);
	}

	function get_sortable_columns() {
		return array(
			array( 'name', true )
		);
	}

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="taxonomy[%s]" value="%s">',
			$item['slug'],
			esc_attr( $item['name'] )
		);
	}

	function column_name( $item ) {
		$refurl = sprintf( 'edit.php?post_type=%s&page=%s',
			$this->_post_type,
			$this->_menu_slug
		);

		$editurl   = $refurl . '&view=edit&slug=' . $item['slug'];
		$deleteurl = $refurl . '&action=delete&slug=' . $item['slug'];

		$actions = array(
			'edit' => sprintf( '<a href="%s">Edit</a>',
				$editurl
			),
			'delete' => sprintf( '<a href="%s&_wp_http_referer=%s">Delete</a>',
				wp_nonce_url( $deleteurl, 'single-taxonomy' ),
				urlencode( $refurl )
			)
		);

		return sprintf( '<a href="%s">%s</a> %s',
			$editurl,
			$item['name'],
			$this->row_actions( $actions )
		);
	}

	function column_object_type( $item ) {
		$object_types = array();

		foreach ( $item['object_type'] as $object_type ) {
			$post_type = get_post_type_object( $object_type );
			$object_types[] = $post_type->label;
		}

		return implode( ', ', $object_types );
	}

	function column_default( $item, $column_name ) {
		return $item[$column_name];
	}

	function get_bulk_actions() {
		return array(
			'delete' => 'Delete'
		);
	}

	function process_bulk_action() {
		$nonce = @$_REQUEST['_wpnonce'];
		$url   = @$_REQUEST['_wp_http_referer'];

		if ( '' != $this->current_action() && wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
			switch ( $this->current_action() ) {
				case 'delete':
					$option = get_option( 'kickpress_taxonomies', array() );

					foreach ( (array) @$_REQUEST['taxonomy'] as $slug => $name ) {
						unset( $option[$slug] );
					}

					update_option( 'kickpress_taxonomies', $option );

					break;
			}

			if ( ! empty( $url ) )
				die( '<script type="text/javascript">'
				   . 'document.location = "' . esc_attr( $url ) . '";'
				   . '</script>' );
		}
	}
}

?>