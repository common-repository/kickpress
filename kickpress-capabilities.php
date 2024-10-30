<?php

add_action( 'admin_menu', 'kickpress_init_roles' );
add_action( 'personal_options', 'kickpress_user_roles' );
add_action( 'profile_update', 'kickpress_profile_update' );
add_action( 'wp_ajax_save_caps', 'kickpress_save_caps' );
add_action( 'wp_ajax_add_role', 'kickpress_add_role' );
add_action( 'wp_ajax_remove_role', 'kickpress_remove_role' );

function kickpress_init_roles() {
	// add_users_page( 'Manage User Roles', 'User Roles', 'edit_users', 'user-roles', 'kickpress_admin_roles' );
	add_users_page( 'User Roles and Capabilities', 'Roles and Capabilities', 'edit_users', 'capabilities', 'kickpress_admin_capabilities' );
}

function kickpress_admin_roles() {
	$action = $_REQUEST['action'];
	$view   = $_REQUEST['view'];

	$nonce  = $_REQUEST['_wpnonce'];
	$refurl = $_REQUEST['_wp_http_referer'];

	if ( wp_verify_nonce( $nonce, 'user-roles' ) ) {
		if ( 'save' == $action ) {
		} else {
		}

		echo '<script type="text/javascript">'
		   . 'document.location = "' . $refurl . '";'
		   . '</script>';

		exit;
	}

	if ( 'edit' == $view ) {
		global $wp_roles;

		$role = get_role( $_REQUEST['name'] );
		$role->title = $wp_roles->role_names[$role->name];

		ksort( $role->capabilities );
?>
<div class="wrap">
	<div id="icon-users" class="icon32"><br></div>
	<h2>Edit Roles</h2>
	<form action="" method="get">
		<input type="hidden" name="page" value="user-roles">
		<input type="hidden" name="action" value="save">
		<?php wp_nonce_field( 'role' ); ?>
		<div id="poststuff" class="metabox-holder has-right-sidebar">
			<div id="post-body">
				<div id="post-body-content">
					<div id="titlediv">
						<div id="titlewrap">
							<label for="title" id="title-prompt-text" class="hide-if-no-js" style="visibility:hidden">
								<?php echo ! empty( $role->title ) ? $role->title : 'Enter title here'; ?>
							</label>
							<input id="title" type="text" size="30" name="post_title"
									value="<?php echo esc_attr( htmlspecialchars( $role->title ) ); ?>"
									tabindex="1" autocomplete="off">
						</div>
					</div>
					<pre><?php print_r( $wp_roles ); ?></pre>
				</div>
			</div>
			<br class="clear" />
		</div>
	</form>
</div>
<?php
	} else {
		$table = new Role_List_Table();
		$table->prepare_items();
?>
<div class="wrap">
	<div id="icon-users" class="icon32"><br></div>
	<h2>User Roles <a href="?page=user-roles&view=edit" class="add-new-h2">Add New</a></h2>
	<form action="" method="get">
		<input type="hidden" name="page" value="user-roles">
		<?php $table->display(); ?>
	</form>
</div>
<?php
	}
}

function kickpress_admin_capabilities() {
	global $wp_roles, $kickpress_post_types, $kickpress_builtin_post_types;

	$core_roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber', 'app' );

	$caps = array(
		'Posts' => array(
			'read',
			'read_private_posts',
			'create_posts',
			'edit_posts',
			'edit_others_posts',
			'edit_private_posts',
			'edit_published_posts',
			'publish_posts',
			'delete_posts',
			'delete_others_posts',
			'delete_private_posts',
			'delete_published_posts'
		),
		'Pages' => array(
			'read_private_pages',
			'create_pages',
			'edit_pages',
			'edit_others_pages',
			'edit_private_pages',
			'edit_published_pages',
			'publish_pages',
			'delete_pages',
			'delete_others_pages',
			'delete_private_pages',
			'delete_published_pages'
		)
	);

	$caps['Posts'] = kickpress_add_caps( 'post', $caps['Posts'] );
	$caps['Pages'] = kickpress_add_caps( 'page', $caps['Pages'] );

	foreach ( $kickpress_post_types as $post_type ) {
		if ( ! isset( $post_type['post_type'] ) || in_array( $post_type['post_type'], $kickpress_builtin_post_types ) ) continue;

		$this_post_type = (string) $post_type['post_type'];

		$post_type_title = isset( $post_type['post_type_title'] ) ? $post_type['post_type_title']
						 : ucwords( str_replace( array( '-', '_' ), ' ', $this_post_type ) );

		$caps[$post_type_title] = array(
			'read_private_' . $this_post_type,
			'create_' . $this_post_type,
			'edit_' . $this_post_type,
			'edit_others_' . $this_post_type,
			'edit_private_' . $this_post_type,
			'edit_published_' . $this_post_type,
			'publish_' . $this_post_type,
			'delete_' . $this_post_type,
			'delete_others_' . $this_post_type,
			'delete_private_' . $this_post_type,
			'delete_published_' . $this_post_type
		);

		//$caps[ $post_type_title ] = apply_filters_ref_array( 'kickpress_post_type_caps', array( $this_post_type, $caps[ $post_type_title ] ) );

		$caps[ $post_type_title ] = kickpress_add_caps( $this_post_type, $caps[ $post_type_title ] );
	}

	$caps['Content'] = array(
		'moderate_comments',
		'manage_categories',
		'manage_links',
		'unfiltered_html',
		'unfiltered_upload',
		'upload_files',
		'edit_files',
		'import',
		'export'
	);
	$caps['Themes']  = array(
		'switch_themes',
		'edit_theme_options',
		'install_themes',
		'update_themes',
		'edit_themes',
		'delete_themes'
	);
	$caps['Plugins'] = array(
		'activate_plugins',
		'install_plugins',
		'update_plugins',
		'edit_plugins',
		'delete_plugins'
	);
	$caps['Users']   = array(
		'list_users',
		'edit_users',
		'promote_users',
		'create_users',
		'delete_users',
		'add_users',
		'remove_users'
	);
	$caps['Admin']   = array(
		'update_core',
		'edit_dashboard',
		'manage_options'
	);
	$caps['Levels']  = array(
		'level_0',
		'level_1',
		'level_2',
		'level_3',
		'level_4',
		'level_5',
		'level_6',
		'level_7',
		'level_8',
		'level_9',
		'level_10'
	);
?>
<style type="text/css">
	.cap-tab {
		padding: 4px 8px;
		border-top: 1px solid #DFDFDF;
		border-left: 1px solid #DFDFDF;
		border-right: 1px solid #DFDFDF;
		border-top-left-radius: 4px;
		border-top-right-radius: 4px;
		background-image: -webkit-linear-gradient(top,#dfdfdf,#cccccc);
		cursor: pointer;
	}

	.cap-tab.active {
		background-image: -webkit-linear-gradient(top,#f9f9f9,#ececec);
	}

	.remove-role, .pending-save {
		cursor: pointer;
	}

	a.remove-role {
		font-size: smaller;
		color: red;
	}

	#working-indicator {
		display: none;
	}
</style>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#new-user-role-link').click(function(event) {
			$('#new-user-role-form').slideToggle();

			return false;
		});

		$('#new-user-role-form').hide();
		$('#new-user-role-form').submit(function(event) {
			if (startWorking()) {
				var roleName = $('#new-user-role-form').find('input[type="text"]').first().val();

				$.post(ajaxurl, $(this).serialize(), function(response) {
					stopWorking();

					var role = JSON.parse(response).name;

					$('.cap-group').each(function() {
						var group = $(this).data('group');

						var id = 'cap-' + group + '-' + role;

						var th = $('<th>').css('text-align', 'center');

						th.append($('<input>', {
							'type': 'checkbox',
							'id': id,
							'class': 'cap-heading',
							'data-role': role
						}).click(toggleRole));

						th.append($('<label>', { 'for': id }).text(roleName));

						th.append($('<br>'));

						th.append($('<a>', {
							'href': '#',
							'title': 'Remove Role',
							'class': 'remove-role',
							'data-nonce': '<?php echo wp_create_nonce('user-roles'); ?>',
							'data-role': role
						}).text('Remove').click(removeRole));

						$(this).find('thead tr').append(th);

						$(this).find('tbody tr').each(function() {
							var cap = $(this).find('.cap-heading').data('cap');

							var key = 'roles[' + role + '][' + cap + ']';

							var td = $('<td>').css('text-align', 'center');

							td.append($('<input>', {
								'type': 'hidden',
								'name': key,
								'value': '0'
							}));

							td.append($('<input>', {
								'type': 'checkbox',
								'name': key,
								'value': '1',
								'class': 'cap-' + cap + ' ' + 'cap-' + group + '-' + role
							}));

							$(this).append(td);
						});
					});

					$('#new-user-role-form').slideToggle();
					$('#new-user-role-form')[0].reset();
				});
			}

			event.preventDefault();
			return false;
		});

		$('.remove-role').click(removeRole);

		function toggleRole(event) {
			$('.' + event.target.id).each(function(index, object) {
				object.checked = event.target.checked;
			});
		}

		function removeRole(event) {
			if (confirm('Are you sure you want to remove this role?')) {
				if (startWorking()) {
					var data = {
						action: 'remove_role',
						_wpnonce: $(event.target).data('nonce'),
						role: $(event.target).data('role')
					};

					$.post(ajaxurl, data, function(response) {
						stopWorking();

						var table = $(event.target).parents().find('table').first();
						var index = table.find('thead tr th').index($(event.target).parent());

						$('.cap-group table thead tr').each(function() {
							$(this).find('th').eq(index).remove();
						});

						$('.cap-group table tbody tr').each(function() {
							$(this).find('td').eq(index - 1).remove();
						});
					});
				}
			}

			return false;
		}

		$('.cap-tab').each(function(index, object) {
			$(object).click(function(event) {
				saveGroup(function(response) {
					showGroup(index);
				});
			});
		});

		$('#cap-group-save').click(function(event) {
			saveGroup(function(){});
		});

		$('.cap-group form').submit(function(event) {
			saveGroup(function(){});
			event.preventDefault();
			return false;
		});

		// Toggle all checkboxes per row/column
		$('.cap-heading').each(function(index, object) {
			$(object).click(toggleRole);
		});

		var currentForm, working = false;

		function showGroup(index) {
			$('.cap-tab').removeClass('active').eq(index).addClass('active');
			$('.cap-group').hide().eq(index).show();
			currentForm = $('.cap-group form').eq(index);
		}

		function saveGroup(callback) {
			if (startWorking()) {
				$.post(ajaxurl, currentForm.serialize(), function(response) {
					stopWorking();
					callback(response);
				});
			}
		}

		function startWorking() {
			if (working) {
				alert('Task already in progress');

				return false;
			}

			$('#working-indicator').show();

			return working = true;
		}

		function stopWorking() {
			$('#working-indicator').hide();

			working = false;
		}

		showGroup(0);
	});
</script>
<div class="wrap">
	<div id="icon-users" class="icon32"><br></div>
	<h2>
		User Roles and Capabilities
		<a href="#" id="new-user-role-link" class="add-new-h2">Add New User Role</a>
		<span id="working-indicator">Working...</span>
	</h2>
	<div class="col-wrap" style="width: 35%;">
		<div class="form-wrap">
			<form id="new-user-role-form">
				<input type="hidden" name="page" value="user-roles">
				<input type="hidden" name="action" value="add_role">
				<?php wp_nonce_field( 'user-roles' ); ?>
				<h3>Add New User Role</h3>
				<div class="form-field form-required">
					<label for="role_name">Name of User Role:</label>
					<input id="role_name" type="text" name="role[name]" value="New Role">
					<p>The name of the user role will be used to assign its capabilities.</p>
				</div>
				<p class="submit">
					<input class="button" type="submit" name="save" value="Add New User Role">
				</p>
			</form>
		</div>
	</div>
	<div id="poststuff" class="metabox-holder">
		<div id="post-body">
			<div id="post-body-content">
				<h4 style="margin: 2px;">
					<?php foreach ( $caps as $group_name => $group ) : ?>
					<?php if ( 'Content' == $group_name ) : ?><div style="padding: 4px 0px;"></div><?php endif; ?>
					<span class="cap-tab"><?php echo $group_name; ?></span>
					<?php endforeach; ?>
				</h4>
				<?php foreach ( $caps as $group_name => $group ) :
					$group_key = strtolower( str_replace( ' ', '_', $group_name ) );
				?>
				<div class="cap-group" data-group="<?php echo $group_key; ?>">
					<form action="" method="post">
						<input type="hidden" name="page" value="capabilities">
						<input type="hidden" name="action" value="save_caps">
						<?php wp_nonce_field( 'capabilities' ); ?>
						<table class="wp-list-table widefat fixed capabilities">
							<thead>
								<tr>
									<th>Capability</th>
									<?php foreach ( $wp_roles->roles as $role_key => $role ) :
										$id = 'cap-' . $group_key . '-' . $role_key;
									?>
									<th style="text-align: center;">
										<input type="checkbox" id="<?php echo $id; ?>" class="cap-heading" data-role="<?php echo $role_key; ?>">
										<label for="<?php echo $id; ?>"><?php echo $role['name']; ?></label>
										<?php if ( ! in_array( $role_key, $core_roles ) ) : ?>
										<a href="#" title="Remove Role" class="remove-role" data-role="<?php echo $role_key; ?>"
											data-nonce="<?php echo wp_create_nonce('user-roles'); ?>">Remove</a>
										<?php endif; ?>
									</th>
									<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $group as $cap ) :
									$cap_title = ucwords( str_replace( array( '-', '_' ), ' ', $cap ) );
								?>
								<tr>
									<th class="left" scope="row">
										<input type="checkbox" id="cap-<?php echo $cap; ?>" class="cap-heading" data-cap="<?php echo $cap; ?>">
										<label for="cap-<?php echo $cap; ?>" title="<?php echo $cap; ?>"><?php echo $cap_title; ?></label>
									</th>
									<?php foreach ( $wp_roles->roles as $role_key => $role ) :
										$role_id = 'cap-' . $group_key . '-' . $role_key;
										if ( ! isset( $role['capabilities'][$cap] ) )
											$role['capabilities'][$cap] = false;
									?>
									<td style="text-align: center;">
										<input type="hidden" name="roles[<?php echo $role_key; ?>][<?php echo $cap; ?>]" value="0">
										<input type="checkbox" name="roles[<?php echo $role_key; ?>][<?php echo $cap; ?>]" value="1"
											class="cap-<?php echo $cap; ?> <?php echo $role_id; ?>"
											<?php echo $role['capabilities'][$cap] ? ' checked="checked"' : ''; ?>>
									</td>
									<?php endforeach; ?>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</form>
				</div>
				<?php endforeach; ?>
				<br class="clear">
				<button class="button-primary" id="cap-group-save"><?php _e('Save Capabilities', 'kickpress'); ?></button>
			</div>
		</div>
		<br class="clear">
	</div>
</div>
<?php
}

function kickpress_add_role() {
	global $wp_roles;

	$nonce = $_REQUEST['_wpnonce'];

	if ( wp_verify_nonce( $nonce, 'user-roles' ) ) {
		$role = $_REQUEST['role']['name'];
		$role_key = sanitize_title( $role );

		if ( ! isset( $wp_roles->roles[ $role_key ] ) ) {
			$role_obj = add_role( $role_key, $role );
			die( json_encode( $role_obj ) );
		}
	}

	exit;
}

function kickpress_remove_role() {
	$nonce = $_REQUEST['_wpnonce'];

	if ( wp_verify_nonce( $nonce, 'user-roles' ) ) {
		$role = $_REQUEST['role'];

		if ( $role_obj = get_role( $role ) ) {
			remove_role( $role );
			die( json_encode( $role_obj ) );
		}
	}

	exit;
}

function kickpress_save_caps() {
	$nonce = $_REQUEST['_wpnonce'];

	if ( wp_verify_nonce( $nonce, 'capabilities' ) ) {
		$roles = $_REQUEST['roles'];

		foreach ( $roles as $role => $caps ) {
			if ( $role_obj = get_role( $role ) ) {
				foreach ( $caps as $cap => $grant ) {
					$grant = (bool) $grant;

					if ( $grant && ! $role_obj->has_cap( $cap ) ) {
						$role_obj->add_cap( $cap );
						echo "$role + $cap\n";
					} elseif ( ! $grant && $role_obj->has_cap( $cap ) ) {
						$role_obj->remove_cap( $cap );
						echo "$role - $cap\n";
					}
				}
			}
		}

		die( 'SUCCESS' );
	}

	die( 'FAILURE' );
}

function kickpress_add_caps( $post_type, $caps ) {
	$post_type_object = get_post_type_object( $post_type );

	foreach ( $post_type_object->cap as $post_cap => $post_type_cap ) {
		if ( in_array( $post_cap, array( 'read', 'edit_post', 'read_post', 'delete_post' ) ) )
			continue;
		elseif ( ! in_array( $post_type_cap, $caps ) )
			$caps[] = $post_type_cap;
	}

	return $caps;
}

function kickpress_user_roles( $user ) {
	global $wpdb, $wp_roles;

	if ( current_user_can( 'administrator' ) ) {
		$key = $wpdb->get_blog_prefix() . 'capabilities';

		$roles = get_user_meta( $user->ID, $key, true );

		array_shift( $roles );
?>
<tr id="xroles-row">
	<th scope="row"><?php _e('Extra Roles', 'kickpress')?></th>
	<td>
		<ul>
			<?php foreach ( $wp_roles->roles as $role_name => $role ) : ?>
			<li>
				<input id="xroles_<?php echo $role_name; ?>" type="checkbox" name="xroles[<?php echo $role_name; ?>]" value="1"
					<?php echo isset( $roles[$role_name] ) ? ' checked="checked"' : ''; ?>>
				<label for="xroles_<?php echo $role_name; ?>"><?php echo $role['name']; ?></label>
			</li>
			<?php endforeach; ?>
		</ul>
	</td>
</tr>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		var source = $('#xroles-row');
		var target = $('table.form-table tr').has('select[name="role"]');

		target.find('th label').text('Primary Role');
		target.after(source);
	});
</script>
<?php
	}
}

function kickpress_profile_update( $user_id ) {
	global $wpdb;

	$key = $wpdb->get_blog_prefix() . 'capabilities';

	$roles = get_user_meta( $user_id, $key, true );

	$xroles = $_REQUEST['xroles'];

	if ( is_array( $roles ) && is_array( $xroles ) ) {
		foreach ( $xroles as $xrole => $value ) {
			$roles[$xrole] = $value;
		}

		update_user_meta( $user_id, $key, $roles );
	}
}

/**
 * Checks if a non-logged in user has permissions
 * @param  string $capability must be the capability name that we are checking for
 * @return boolean
 */
function kickpress_anonymous_user_can($capability) {
	return apply_filters('kickpress_anonymous_user_can', false, $capability);
}

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Role_List_Table extends WP_List_Table {
	var $_column_headers;

	var $_menu_slug = 'user-roles';

	function __construct() {
		parent::__construct( array(
			'singlular' => 'role',
			'plural'    => 'roles'
		) );
	}

	function display() {
		$this->process_bulk_action();

		$this->prepare_items();

		parent::display();
	}

	function prepare_items() {
		global $wp_roles;

		$columns = $this->get_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, array(), $sortable );

		$per_page = 20;

		$roles = $wp_roles->roles;

		foreach ( $roles as $key => &$value ) $value['key'] = $key;

		$this->items = $roles;

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
			'cb'      => '<input type="checkbox">',
			'title'   => 'Title',
			'level'   => 'User Level',
			'caps'    => 'Capabilities'
		);
	}

	function get_sortable_columns() {
		return array(
			'title' => array( 'title', true ),
			'level' => array( 'level', false )
		);
	}

	/**
	 * Custom column handler for checkbox column
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%s[]" value="%s">',
			$this->_args['singular'],
			$item['ID']
		);
	}

	/**
	 * Custom column handler for "title" column
	 */
	function column_title( $item ) {
		$nonce = wp_create_nonce( 'bulk-' . $this->_args['plural'] );

		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&view=%s&name=%s">Edit</a>',
				$this->_menu_slug,
				'edit',
				$item['key']
			),
			'delete' => sprintf( '<a href="?page=%s&action=%s&name=%s&_wpnonce=%s">Delete</a>',
				$this->_menu_slug,
				'delete',
				$item['key'],
				$nonce
			)
		);

		return sprintf( '<a href="?page=%s&view=%s&name=%s">%s</a> %s',
			$this->_menu_slug,
			'edit',
			$item['key'],
			$item['name'],
			$this->row_actions( $actions )
		);
	}

	function column_level( $item ) {
		$user_level = array_reduce(
			array_keys( $item['capabilities'] ),
			array( &$this, 'level_reduction' ),
			0
		);

		return 'Level ' . $user_level;
	}

	function column_caps( $item ) {
		$max = 5;

		$html = '<ul>';

		foreach ( array_keys( $item['capabilities'] ) as $index => $capability ) {
			$html .= '<li>' . $capability . '</li>';

			if ( $index == $max - 1 ) break;
		}

		$html .= '</ul>';

		if ( count( $item['capabilities'] ) > $max ) {
			$html .= sprintf( '<div><a href="?page=%s&view=%s&name=%s">%d more...</a></div>',
				$this->_menu_slug,
				'edit',
				$item['key'],
				count( $item['capabilities'] ) - $max
			);
		}

		return $html;
	}

	/**
	 * Default column handler, catch-all for columns without custom handlers
	 */
	function column_default( $item, $column_name ) {
		return $item[$column_name];
	}

	function get_bulk_actions() {
		return array(
			'delete' => 'Delete'
		);
	}

	function process_bulk_action() {
		$nonce = $_REQUEST['_wpnonce'];
		$ids   = $_REQUEST[$this->_args['singular']];

		if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) return;

		//var_dump( $this->current_action(), $ids );

		switch ( $this->current_action() ) {
			case 'delete':
				break;
		}
	}

	function level_reduction( $max, $item ) {
		if ( preg_match( '/^level_(10|[0-9])$/i', $item, $matches ) ) {
			$level = intval( $matches[1] );
			return max( $max, $level );
		} else {
			return $max;
		}
	}
}

?>