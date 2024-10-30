<?php

add_action( 'admin_enqueue_scripts', 'kickpress_admin_workflow_scripts' );
add_action( 'admin_menu', 'kickpress_init_workflows' );
add_action( 'add_meta_boxes', 'kickpress_workflow_meta_boxes' );
add_action( 'save_post', 'kickpress_workflow_step', 10, 2 );

function log_dump($data) {
	$file = realpath(dirname(__FILE__).'/kickpress.log');
	$line = date('Y-m-d H:i:s')."\t".$data.PHP_EOL;
	file_put_contents($file,file_get_contents($file).$line);
}

function kickpress_workflow_meta_boxes() {
	global $kickpress_post_types;
	
	foreach ( $kickpress_post_types as $post_type ) {
		if ( $post_type_object = get_post_type_object( $post_type['post_type'] ) ) {
			$id    = $post_type_object->name . '-workflow';
			$title = $post_type_object->label . ' Workflow';
			
			add_meta_box( $id, $title, 'kickpress_workflow_meta_post', $post_type_object->name, 'side', 'high' );
		}
	}
}

function kickpress_workflow_meta_post( $post ) {
	$post_type = get_post_type_object( $post->post_type );
	
	$taxonomy = $post_type->name . '_workflow';
	
	$terms = kickpress_sort_workflow_steps( get_terms( $taxonomy, array(
		'hide_empty' => false
	) ) );
	
	if ( empty( $terms ) ) {
?>
<div class="form-wrap">
	No Workflow Established
</div>
<?php
		return;
	}
	
	$post_terms = wp_get_post_terms( $post->ID, $taxonomy );
	
	if ( empty( $post_terms ) ) {
		$post_term = null;
		$next_term = $terms[0];
		$term_index = -1;
	} else {
		foreach ( $post_terms as &$post_term ) {
			$post_term->params = unserialize( $post_term->description );
			unset( $post_term->description );
		}
		
		$post_term = $post_terms[0];
		
		foreach ( $terms as $index => $term ) {
			if ( $term->term_id == $post_term->term_id ) {
				$term_index = $index + 1;
			}
			
			if ( $term->parent == $post_term->term_id ) {
				$next_term = $term;
			}
		}
	}
?>
<style type="text/css">
	#<?php echo $post->post_type; ?>-workflow {
		display: none;
	}
	
	.workflow-step {
		padding-left: 20px;
		background-position: center left;
		background-repeat: no-repeat;
	}
	
	.workflow-step-complete {
		background-image: url('../wp-content/plugins/kickpress/includes/images/icons/tick.png');
	}
	
	.workflow-step-incomplete {
		background-image: url('../wp-content/plugins/kickpress/includes/images/icons/cross.png');
	}
	
	.workflow-step-enabled {
		color: inherit;
		font-style: normal;
	}
	
	.workflow-step-disabled {
		color: #999;
		font-style: italic;
	}
</style>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#visibility').prev().before($('#workflow-step-label'));
		// $('#<?php echo $post->post_type; ?>-workflow').remove();
		$('#workflow-step-edit').click(function() {
			$('#workflow-step-edit').hide('blind');
			$('#workflow-step-select').show('blind');
			return false;
		});
		$('#workflow-step-save').click(function() {
			$('#workflow-step-edit').show('blind');
			$('#workflow-step-select').hide('blind');
			$('#workflow-step-hidden').val($('#workflow-step').val());
			$('#workflow-step-title').text($('#workflow-step option:selected').text());
			return false;
		});
		$('#workflow-step-cancel').click(function() {
			$('#workflow-step-edit').show('blind');
			$('#workflow-step-select').hide('blind');
			return false;
		});
	});
</script>
<div id="workflow-step-label" class="misc-pub-section">
	Workflow Step: <span id="workflow-step-title" style="font-weight: bold;"><?php echo is_null( $post_term ) ? '(empty)' : $post_term->name; ?></span>
	<?php if ( current_user_can( 'administrator' ) ) : ?>
	<a href="#visibility" id="workflow-step-edit">Edit</a>
	<div id="workflow-step-select" class="hide-if-js">
		<select name="workflow_step" id="workflow-step">
			<option value="">(empty)</option>
			<?php foreach ( $terms as $term ) :
				$selected = $term->slug == $post_term->slug ? ' selected="selected"' : '';
			?>
			<option value="<?php echo $term->slug; ?>"<?php echo $selected; ?>><?php echo $term->name; ?></option>
			<?php endforeach; ?>
		</select>
		<input type="hidden" name="workflow_step_hidden" id="workflow-step-hidden">
		<a href="#workflow-step" id="workflow-step-save" class="hide-if-no-js button">OK</a>
		<a href="#workflow-step" id="workflow-step-cancel" class="hide-if-no-js">Cancel</a>
	</div>
	<?php endif; ?>
	<ol>
		<?php foreach ( $terms as $index => $term ) :
			$classes   = array( 'workflow-step' );
			$classes[] = $term_index > $index ? 'workflow-step-complete' : 'workflow-step-incomplete';
			$classes[] = empty( $term->params['capability'] ) || current_user_can( $term->params['capability'] )
			           ? 'workflow-step-enabled' : 'workflow-step-disabled';
			
			$classes = implode( ' ', $classes );
		?>
		<li class="<?php echo $classes; ?>"><?php echo $term->name; ?></li>
		<?php endforeach; ?>
	</ol>
</div>
<div class="form-wrap">
	<div class="form-field">
		<label>Current Step:</label>
		<p><?php echo is_null( $post_term ) ? '(empty)' : $post_term->name; ?></p>
		<label>Next Step:</label>
		<p><?php echo $next_term->name; ?></p>
	</div>
	<?php if ( current_user_can( $post_term->params['role'] ) ) : ?>
	<p class="submit" style="text-align: center;"><input type="button" value="Complete Step" class="button"></p>
	<?php endif; ?>
	<?php if ( $term_index >= 0 ) : ?>
	<div class="form-field">
		<label for="">Revert to Previous Step:</label>
		<select>
			<?php for ( $i = $term_index - 1; $i >= 0; $i-- ) : ?>
			<option value="<?php echo $terms[$i]->slug; ?>"><?php echo $terms[$i]->name; ?></option>
			<?php endfor; ?>
			<option value="">(empty)</option>
		</select>
	</div>
	<p class="submit" style="text-align: center;"><input type="button" value="Revert Post" class="button"></p>
	<?php endif; ?>
</div>
<?php
}

function kickpress_sort_workflow_steps( $terms ) {
	$trees = array();
	$nodes = array();
	
	foreach ( $terms as &$term ) {
		$term->params = unserialize( $term->description );
		unset( $term->description );
		
		$nodes[$term->term_id] = $term;
		$nodes[$term->term_id]->children = array();
	}
	
	foreach ( $nodes as &$node ) {
		if ( 0 == $node->parent )
			$trees[$node->term_id] = $node;
		else
			$nodes[$node->parent]->children[$node->term_id] = $node;
	}
	
	kickpress_sort_workflow_nodes( $trees );
	
	usort( $nodes, 'kickpress_compare_workflow_nodes' );
	
	return $nodes;
}

function kickpress_sort_workflow_nodes( &$nodes, $depth = 0 ) {
	foreach ( $nodes as &$node ) {
		$node->params['order'] = $depth;
		
		kickpress_sort_workflow_nodes( $node->children, $depth + 1 );
		
		unset( $node->children );
	}
}

function kickpress_compare_workflow_nodes( $node1, $node2 ) {
	return $node1->params['order'] - $node2->params['order'];
}

function kickpress_workflow_step( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || in_array( $post->post_status, array( 'auto-draft', 'trash' ) ) ) return;
	
	$taxonomy = $post->post_type . '_workflow';
	
	if ( ! empty( $_REQUEST['workflow_step_hidden'] ) && current_user_can( 'administrator' ) ) {
		$term = sanitize_key( $_REQUEST['workflow_step_hidden'] );
		
		if ( $term_row = get_term_by( 'slug', $term, $taxonomy ) ) {
			$term_row->params = unserialize( $term_row->description );
			unset( $term_row->description );
			
			if ( $result = wp_set_post_terms( $post_id, $term_row->term_id, $taxonomy ) ) {
				kickpress_update_post_status( $post, $term_row->params['post_status'] );
				return $result;
			}
		}
	}
	
	$terms = kickpress_sort_workflow_steps( get_terms( $taxonomy, array(
		'hide_empty' => false
	) ) );
	
	if ( empty( $terms ) ) return;
	
	$post_terms = wp_get_post_terms( $post_id, $taxonomy );
	$term_index = -1;
	
	if ( empty( $post_terms ) ) {
		$post_term = null;
		$next_term = $terms[0];
	} else {
		$post_term = $post_terms[0];
		$post_term->params = unserialize( $post_term->description );
		unset( $post_term->description );
		
		foreach ( $terms as $index => $term ) {
			if ( $post_term->term_id == $term->term_id ) $term_index = $index;
			if ( $post_term->term_id == $term->parent  ) $next_term  = $term;
		}
	}
	
	if ( isset( $next_term ) ) {
		if ( current_user_can( $next_term->params['capability'] ) ) {
			if ( $result = wp_set_post_terms( $post_id, $next_term->term_id, $taxonomy ) ) {
				kickpress_update_post_status( $post_id, $next_term->params['post_status'] );
				return $result;
			}
		} else {
			for ( $i = $term_index; $i >= 0; $i-- ) {
				if ( current_user_can( $terms[$i]->params['capability'] ) ) {
					if ( $result = wp_set_post_terms( $post_id, $terms[$i]->term_id, $taxonomy ) ) {
						kickpress_update_post_status( $post_id, $terms[$i]->params['post_status'] );
						return $result;
					}
				}
			}
			
			if ( $result = wp_set_post_terms( $post_id, array(), $taxonomy ) ) {
				kickpress_update_post_status( $post_id, 'pending' );
				return $result;
			}
		}
	}
}

function kickpress_admin_workflow_scripts() {
	wp_enqueue_script( 'jquery-ui-sortable' );
}

function kickpress_init_workflows() {
	global $kickpress_post_types;
	
	foreach ( $kickpress_post_types as $type ) {
		if ( kickpress_boolean( $type['api']->params['builtin'] ) ) {
			$slug = $type['post_type'] . '-workflow';
			
			register_taxonomy( $type['post_type'] . '_workflow', $type['post_type'], array(
				'label'        => $type['post_type_title'] . ' Workflow',
				'public'       => false,
				'hierarchical' => true
			) );
			
			$parent_slug = 'post' == $type['post_type'] ? 'edit.php' : 'edit.php?post_type=' . $type['post_type'];
			
			$slug = $type['post_type'] . '-workflow';
			
			add_submenu_page( $parent_slug, 'Manage Workflow', 'Workflow',
				'manage_options', $slug, 'kickpress_post_workflow' );
		}
	}
}

function kickpress_post_workflow() {
	$post_type = ! empty( $_REQUEST['post_type'] ) ? $_REQUEST['post_type'] : 'post';
	$taxonomy  = $post_type . '_workflow';
	
	$action = (string) $_REQUEST['action'];
	$view   = (string) $_REQUEST['view'];
	
	$nonce  = $_REQUEST['_wpnonce'];
	$refurl = $_REQUEST['_wp_http_referer'];
	
	if ( wp_verify_nonce( $nonce, 'workflow-step' ) ) {
		switch ( $action ) {
			case 'save':
				$name = (string) $_REQUEST['name'];
				$slug = (string) $_REQUEST['slug'];
				$role = (string) $_REQUEST['role'];
				
				$capability  = (string) $_REQUEST['capability'];
				$post_status = (string) $_REQUEST['post_status'];
				
				$parent = (int) $_REQUEST['parent'];
				
				if ( empty( $slug ) ) $slug = sanitize_title( $name );
				
				$args = array(
					'slug'   => $slug,
					'parent' => $parent
				);
				
				$args['description'] = serialize( array(
					'post_status' => $post_status,
					'capability'  => $capability,
					'role'        => $role
				) );
				
				if ( $term_ids = get_term_children( $parent, $taxonomy ) )
					$old_term = get_term( $term_ids[0], $taxonomy );
				
				$new_term = wp_insert_term( $name, $taxonomy, $args );
				
				if ( isset( $old_term ) )
					wp_update_term( $old_term->term_id, $taxonomy, array(
						'parent' => $new_term['term_id']
					) );
				
			break;
			case 'delete':
				$term_id = $_REQUEST['term_id'];
				
				wp_delete_term( $term_id, $taxonomy );
			break;
		}
		
		echo '<script type="text/javascript">'
		   . 'document.location = "' . $refurl . '";'
		   . '</script>';
		
		exit();
	}
	
	if ( 'edit' == $view ) {
		$term_id = (int) $_REQUEST['term_id'];
		
		if ( 0 < $term_id )
			$term = get_term( $term_id, $taxonomy );
		else
			$term = object;
?>
<div class="wrap">
	<div class="form-wrap">
		<div id="icon-edit" class="icon32"><br></div>
		<h2>Edit Step</h2>
		<form action="" method="get">
			<input type="hidden" name="post_type" value="<?php echo $post_type; ?>">
			<input type="hidden" name="page" value="<?php echo $post_type; ?>-workflow">
			<input type="hidden" name="action" value="save">
			<input type="hidden" name="term_id" value="<?php echo $term_id; ?>">
			<?php wp_nonce_field( 'workflow-step' ); ?>
			<div class="form-field form-required">
				<label for="step-name">Name</label>
				<input id="step-name" type="text" size="40" name="name" value="" aria-required="true">
				<p>The name is how it appears on your site.</p>
			</div>
			<div class="form-field">
				<label for="step-slug">Slug</label>
				<input id="step-slug" type="text" size="40" name="slug" value="">
				<p>The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</p>
			</div>
			<div class="form-field">
				<label for="step-parent">Previous Step</label>
				<select id="step-parent" name="parent">
					<option value="">- Select Step -</option>
					<?php foreach ( $terms as $term ) : ?>
					<option value="<?php echo $term->term_id; ?>"><?php echo $term->name; ?></option>
					<?php endforeach; ?>
				</select>
				<p>The role is the group of users who must complete this step.</p>
			</div>
			<div class="form-field">
				<label for="step-post-status">Post Status</label>
				<select id="step-post-status" name="post_status">
					<option value="">- Select Status -</option>
					<option value="publish">Published</option>
					<option value="pending">Pending Review</option>
				</select>
				<p>The post status is the status that will be assigned to the post when this step is completed.</p>
			</div>
			<div class="form-field">
				<label for="step-cap">Required Capability</label>
				<select id="step-cap" name="capability">
					<option value="">- Select Capability -</option>
					<?php foreach ( $capabilities as $cap ) : ?>
					<option value="<?php echo $cap; ?>"><?php echo ucwords( str_replace( '_', ' ', $cap ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<p>The required capability is the permission a user must have to complete this step.</p>
			</div>
			<div class="form-field">
				<label for="step-role">Notification Role</label>
				<select id="step-role" name="role">
					<option value="">- Select Role -</option>
					<?php foreach ( $wp_roles->roles as $key => $role ) : ?>
					<option value="<?php echo $key; ?>"><?php echo $role['name']; ?></option>
					<?php endforeach; ?>
				</select>
				<p>The notification role is the group of users who will receive notifications when this step is completed.</p>
			</div>
			<p class="submit"><input id="submit" class="button" type="submit" name="submit" value="Add New Step"></p>
		</form>
	</div>
</div>
<?php
	} else {
		global $wp_roles;
		
		$table = new Workflow_List_Table( $post_type );
		$terms = get_terms( $taxonomy, array(
			'hide_empty' => false
		) );
		
		$cap_type = in_array( $post_type, array( 'post', 'page' ) )
		          ? $post_type . 's' : $post_type;
		
		$capabilities = array(
			'create_' . $cap_type,
			'edit_' . $cap_type,
			'publish_' . $cap_type
		);
?>
<style type="text/css">
	#the-list tr {
		cursor: move;
	}
	
	#sort-msg {
		display: none;
		padding: 5px 10px;
		border-radius: 3px;
		border: 1px solid #E6DB55;
		background-color: #FFFFE0;
	}
	
	.quick-edit-row label {
		float: left;
		width: 50px;
		padding: 4px 0px;
	}
</style>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('#doaction').after($('<span id="sort-msg"></span>'));
		
		$('tbody#the-list').sortable({
		    helper: function(event, ui) {
			    ui.children().each(function() {
			        $(this).width($(this).width());
			    });
			    
			    return ui;
			},
			stop: function(event, ui) {
				$('tbody#the-list tr').removeClass('alternate');
				$('tbody#the-list tr:even').addClass('alternate');
				
				var order = 0;
				
				$('tbody#the-list td.column-order').each(function(index, object) {
					$(object).find('span').text(order + 1);
					
					order++;
				});
				
				$('select[name="action"]').val('save-order');
				
				$('#sort-msg').text('Press "Apply" to save new order.');
				$('#sort-msg').show();
			}
		});	//.disableSelection();
		
		$('.quick-edit').each(function(index, item) {
			$(item).click(function() {
				var id = $(this).attr('id').substr(11);
				var row = $('<tr id="term-' + id + '" class="quick-edit-row">');
				var cell = $('<td colspan="3">');
				
				cell.append($('<label for="term-' + id + '-name">Name</label>'));
				cell.append($('<input id="term-' + id + '-name" type="text" name="term[' + id + '][name]">'));
				cell.append($('<br>'));
				cell.append($('<label for="term-' + id + '-slug">Slug</label>'));
				cell.append($('<input id="term-' + id + '-slug" type="text" name="term[' + id + '][slug]">'));
				cell.append($('<br>'));
				cell.append($('<input id="term-' + id + '-save" type="button" value="Save" class="button-primary">'));
				cell.append($('<input id="term-' + id + '-cancel" type="button" value="Cancel" class="button">'));
				
				row.append(cell);
				
				var selector = $('<select id="term-' + id + '-post-status" name="term[' + id + '][post_status]">');
				
				cell = $('<td>');
				cell.append(selector);
				
				selector.append($('<option value="">- Select Status -</option>'));
				selector.append($('<option value="publish">Published</option>'));
				selector.append($('<option value="pending">Pending Review</option>'));
				
				row.append(cell);
				
				selector = $('<select id="term-' + id + '-capability" name="term[' + id + '][capability]">');
				selector.append($('<option value="">- Select Capability -</option>'));
				<?php foreach ( $capabilities as $cap ) : ?>
				selector.append($('<option value="<?php echo $cap; ?>"><?php echo ucwords( str_replace( '_', ' ', $cap ) ); ?></option>'));
				<?php endforeach; ?>
				
				cell = $('<td>');
				cell.append(selector);
				
				row.append(cell);
				
				selector = $('<select id="term-' + id + '-role" name="term[' + id + '][role]">');
				selector.append($('<option value="">- Select Role -</option>'));
				<?php foreach ( $wp_roles->roles as $key => $role ) : ?>
				selector.append($('<option value="<?php echo $key; ?>"><?php echo $role['name']; ?></option>'));
				<?php endforeach; ?>
				
				cell = $('<td>');
				cell.append(selector);

				row.append(cell);
				
				$(this).parents('tr').after(row);
				$(this).parents('tr').hide();
				
				$('#term-' + id + '-name').val($('#hidden-term-' + id + '-name').val());
				$('#term-' + id + '-slug').val($('#hidden-term-' + id + '-slug').val());
				$('#term-' + id + '-post-status').val($('#hidden-term-' + id + '-post-status').val());
				$('#term-' + id + '-capability').val($('#hidden-term-' + id + '-capability').val());
				$('#term-' + id + '-role').val($('#hidden-term-' + id + '-role').val());
				
				$('#term-' + id + '-save').click(function() {
					$('.tablenav .actions').hide();
					
					$('select[name="action"]').append($('<option value="save">Save</option>'));
					$('select[name="action"]').val('save');
					
					$('#term-id').val(id);
					$('#term-id').parent().submit();
					
					//alert($('#term-id').parent().serialize());
					
					$(this).parents('tr').prev().show();
					$(this).parents('tr').remove();
				});
				
				$('#term-' + id + '-cancel').click(function() {
					$(this).parents('tr').prev().show();
					$(this).parents('tr').remove();
				});
				
				return false;
			});
		});
	});
</script>
<div class="wrap">
	<div id="icon-edit" class="icon32"><br></div>
	<h2>Manage Workflow</h2>
	<div id="ajax-response"></div>
	<div id="col-container">
		<div id="col-right">
			<div class="col-wrap">
				<form action="" method="get">
					<input type="hidden" name="page" value="<?php echo $post_type; ?>-workflow">
					<input type="hidden" name="term_id" id="term-id">
					<?php $table->display(); ?>
				</form>
			</div>
		</div>
		<div id="col-left">
			<div class="col-wrap">
				<div class="form-wrap">
					<h3>Add New Step</h3>
					<form action="" method="get">
						<input type="hidden" name="page" value="<?php echo $post_type; ?>-workflow">
						<input type="hidden" name="action" value="save">
						<?php wp_nonce_field( 'workflow-step' ); ?>
						<div class="form-field form-required">
							<label for="step-name">Name</label>
							<input id="step-name" type="text" size="40" name="name" value="" aria-required="true">
							<p>The name is how it appears on your site.</p>
						</div>
						<div class="form-field">
							<label for="step-slug">Slug</label>
							<input id="step-slug" type="text" size="40" name="slug" value="">
							<p>The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.</p>
						</div>
						<div class="form-field">
							<label for="step-parent">Previous Step</label>
							<select id="step-parent" name="parent">
								<option value="">- Select Step -</option>
								<?php foreach ( $terms as $term ) : ?>
								<option value="<?php echo $term->term_id; ?>"><?php echo $term->name; ?></option>
								<?php endforeach; ?>
							</select>
							<p>The role is the group of users who must complete this step.</p>
						</div>
						<div class="form-field">
							<label for="step-post-status">Post Status</label>
							<select id="step-post-status" name="post_status">
								<option value="">- Select Status -</option>
								<option value="publish">Published</option>
								<option value="pending">Pending Review</option>
							</select>
							<p>The post status is the status that will be assigned to the post when this step is completed.</p>
						</div>
						<div class="form-field">
							<label for="step-cap">Required Capability</label>
							<select id="step-cap" name="capability">
								<option value="">- Select Capability -</option>
								<?php foreach ( $capabilities as $cap ) : ?>
								<option value="<?php echo $cap; ?>"><?php echo ucwords( str_replace( '_', ' ', $cap ) ); ?></option>
								<?php endforeach; ?>
							</select>
							<p>The required capability is the permission a user must have to complete this step.</p>
						</div>
						<div class="form-field">
							<label for="step-role">Notification Role</label>
							<select id="step-role" name="role">
								<option value="">- Select Role -</option>
								<?php foreach ( $wp_roles->roles as $key => $role ) : ?>
								<option value="<?php echo $key; ?>"><?php echo $role['name']; ?></option>
								<?php endforeach; ?>
							</select>
							<p>The notification role is the group of users who will receive notifications when this step is completed.</p>
						</div>
						<p class="submit"><input id="submit" class="button" type="submit" name="submit" value="Add New Step"></p>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
	}
}

function kickpress_update_post_status( $post, $post_status ) {
	global $wpdb;
	
	if ( ! empty( $post_status ) ) {
		$wpdb->update( $wpdb->posts, array(
			'post_status' => $post_status
		), array(
			'ID' => $post->ID
		) );
	}
}

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Workflow_List_Table extends WP_List_Table {
	var $_column_headers;
	
	var $_post_type;
	
	var $_menu_slug;
	
	function __construct( $post_type = 'post' ) {
		parent::__construct( array(
			'singular' => 'step',
			'plural'    => 'steps'
		) );
		
		$this->_post_type = $post_type;
		$this->_menu_slug = $post_type . '-workflow';
	}
	
	function display() {
		$this->process_bulk_action();
		
		$this->prepare_items();
		
		parent::display();
	}
	
	function prepare_items() {
		global $wp_roles;
		
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array( $columns, array(), $sortable );
		
		$per_page = 20;
		
		$taxonomy = $this->_post_type . '_workflow';
		
		$terms = get_terms( $taxonomy, array(
			'hide_empty' => false
		) );
		
		$trees = array();
		$nodes = array();
		
		foreach ( $terms as &$term ) {
			$term->params = unserialize( $term->description );
			
			$nodes[$term->term_id] = $term;
			$nodes[$term->term_id]->children = array();
		}
		
		foreach ( $nodes as &$node ) {
			if ( 0 == $node->parent )
				$trees[$node->term_id] = $node;
			else
				$nodes[$node->parent]->children[$node->term_id] = $node;
		}
		
		$this->order_nodes( $trees );
		
		usort( $nodes, array( $this, 'compare_nodes' ) );
		
		$this->items = $nodes;
		
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
			'step'        => 'Step',
			'order'       => 'Order',
			'post_status' => 'Post Status',
			'capability'  => 'Capability',
			'role'        => 'Role'
		);
	}
	
	function get_sortable_columns() {
		return array();
	}
	
	/**
	 * Custom column handler for checkbox column
	 */
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%s[]" value="%s">',
			$this->_args['singular'],
			$item->term_id
		);
	}
	
	/**
	 * Custom column handler for "step" column
	 */
	function column_step( $item ) {
		$nonce = wp_create_nonce( 'workflow-' . $this->_args['singular'] );
		$refurl = urlencode( 'edit.php?page=' . $this->_menu_slug );
		
		$actions = array(
			'edit'   => sprintf( '<a href="?page=%s&view=%s&term_id=%s" id="quick-edit-%s" class="quick-edit">Edit</a>',
				$this->_menu_slug,
				'edit',
				$item->term_id,
				$item->term_id
			),
			'delete' => sprintf( '<a href="?page=%s&action=%s&term_id=%s&_wpnonce=%s&_wp_http_referer=%s">Delete</a>',
				$this->_menu_slug,
				'delete',
				$item->term_id,
				$nonce,
				$refurl
			)
		);
		
		$fields = sprintf( '<input id="hidden-term-%1$d-name" type="hidden" value="%2$s">'
			. '<input id="hidden-term-%1$d-slug" type="hidden" value="%3$s">',
			$item->term_id,
			esc_attr( $item->name ),
			esc_attr( $item->slug )
		);
		
		return sprintf( '<a href="?page=%s&view=%s&name=%s">%s</a> %s %s',
			$this->_menu_slug,
			'edit',
			$item->term_id,
			$item->name,
			$this->row_actions( $actions ),
			$fields
		);
	}
	
	/**
	 * Custom column handler for "order" column
	 */
	function column_order( $item ) {
		return sprintf( '<span>%d</span><input type="hidden" name="order[]" value="%d">',
			$item->params['order'] + 1,
			$item->term_id
		);
	}
	
	/**
	 * Custom column handler for "post status" column
	 */
	function column_post_status( $item ) {
		return sprintf( '<input id="hidden-term-%1$d-post-status" type="hidden" value="%2$s"> %3$s',
			$item->term_id,
			esc_attr( $item->params['post_status'] ),
			ucwords( str_replace( array( '_', '-' ), ' ', $item->params['post_status'] ) )
		);
	}
	
	/**
	 * Custom column handler for "capability" column
	 */
	function column_capability( $item ) {
		return sprintf( '<input id="hidden-term-%1$d-capability" type="hidden" value="%2$s"> %3$s',
			$item->term_id,
			esc_attr( $item->params['capability'] ),
			ucwords( str_replace( array( '_', '-' ), ' ', $item->params['capability'] ) )
		);
	}
	
	/**
	 * Custom column handler for "role" column
	 */
	function column_role( $item ) {
		global $wp_roles;
		
		return sprintf( '<input id="hidden-term-%1$d-role" type="hidden" value="%2$s"> %3$s',
			$item->term_id,
			esc_attr( $item->params['role'] ),
			$wp_roles->roles[$item->params['role']]['name']
		);
	}
	
	/**
	 * Default column handler, catch-all for columns without custom handlers
	 */
	function column_default( $item, $column_name ) {
		return $item->$column_name;
	}
	
	function get_bulk_actions() {
		return array(
			'save-order' => 'Save Order',
			'delete'     => 'Delete'
		);
	}
	
	function process_bulk_action() {
		$nonce  = $_REQUEST['_wpnonce'];
		$refurl = $_REQUEST['_wp_http_referer'];
		
		if ( ! wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) return;
		
		$taxonomy = $this->_post_type . '_workflow';
		
		switch ( $this->current_action() ) {
			case 'save-order':
				$terms = $_REQUEST['order'];
				
				if ( ! is_array( $terms ) ) return;
				
				$parent = 0;
				
				foreach ( $terms as $index => $term_id ) {
					wp_update_term( $term_id, $taxonomy, array(
						'parent' => $parent
					) );
					
					$parent = $term_id;
				}
				
				break;
			case 'save':
				$term_id = $_REQUEST['term_id'];
				
				$term = $_REQUEST['term'][$term_id];
				$term['description'] = serialize( array(
					'post_status' => $term['post_status'],
					'capability'  => $term['capability'],
					'role'        => $term['role']
				) );
				
				unset( $term['post_status'], $term['capability'], $term['role'] );
				
				if ( empty( $term['slug'] ) ) $term['slug'] = sanitize_title( $term['name'] );
				
				wp_update_term( $term_id, $taxonomy, $term );
				
				break;
			case 'delete':
				$terms = $_REQUEST[$this->_args['singular']];
				
				if ( ! is_array( $terms ) ) return;
				
				foreach ( $terms as $term_id ) {
					wp_delete_term( $term_id, $taxonomy );
				}
				
				break;
		}
		
		echo '<script type="text/javascript">'
		   . 'document.location = "' . $refurl . '";'
		   . '</script>';
		
		exit();
	}
	
	function order_nodes( &$nodes, $depth = 0 ) {
		foreach ( $nodes as &$node ) {
			$node->params['order'] = $depth;
			$this->order_nodes( $node->children, $depth + 1 );
			unset( $node->children );
		}
	}
	
	function compare_nodes( $node1, $node2 ) {
		return $node1->params['order'] - $node2->params['order'];
	}
}

?>