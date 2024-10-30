<?php

add_action( 'save_post',      'kickpress_relationships_save_post' );
add_action( 'add_meta_boxes', 'kickpress_relationships_meta_boxes' );
add_action( 'admin_menu',     'kickpress_relationships_menu_box' );

add_action( 'wp_ajax_post_search', 'kickpress_relationships_ajax_box' );

add_action( 'init', 'kickpress_init_relationships' );

function kickpress_init_relationships() {
	global $wpdb;

	$wpdb->post_relationships = "{$wpdb->prefix}kp_post_relationships";

	// import old relational connections from wp_postmeta
	if ( 'relationships' == @$_REQUEST['import'] ) {
		$sql = "INSERT INTO `{$wpdb->post_relationships}`"
		     . "(`source_id`, `target_id`, `relationship`) "
		     . "SELECT `meta_value` AS `source_id`, `post_id` AS `target_id`, "
		     . "TRIM(LEADING '_kickpress_rel[' FROM "
		     . "TRIM(TRAILING ']' FROM `meta_key`)) AS `relationship` "
		     . "FROM `{$wpdb->postmeta}` AS `meta` "
		     . "WHERE `meta_key` LIKE '\_kickpress\_rel[%]'";

		$wpdb->query( $sql );
	}

	$option = get_option( 'kickpress_relationships', array() );

	foreach ( $option as $rel ) {
		kickpress_register_relationship( $rel['name'], array(
			'source_type' => $rel['source']['type'],
			'source_qty'  => $rel['source']['qty'],
			'target_type' => $rel['target']['type'],
			'target_qty'  => $rel['target']['qty']
		) );
	}
}

function kickpress_register_relationship( $name, $args ) {
	global $kickpress_relationships;

	$name = sanitize_title( $name );

	$defaults = array(
		'label'       => $name,
		'source_type' => '',
		'source_qty'  => 'multiple',
		'target_type' => '',
		'target_qty'  => 'multiple'
	);

	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

	if ( is_null( $kickpress_relationships ) )
		$kickpress_relationships = array();

	if ( isset( $kickpress_relationships[$name] ) ) return false;

	$kickpress_relationships[$name] = (object) array(
		'name'        => $name,
		'label'       => $label,
		'source_type' => $source_type,
		'source_qty'  => $source_qty,
		'target_type' => $target_type,
		'target_qty'  => $target_qty
	);

	return $kickpress_relationships[$name];
}

function kickpress_get_relationship( $name ) {
	global $kickpress_relationships;

	$name = sanitize_title( $name );

	if ( ! isset( $kickpress_relationships[$name] ) )
		return new WP_Error( 'relationship_not_found',
			__( 'The selected relationship could not be found.', 'kickpress' ) );

	return $kickpress_relationships[$name];
}

/**
 * TODO revisit this function
 */
function kickpress_add_relationship( $name, $args ) {
	$args['action_key'] = 'add';

	return kickpress_update_relationship( $name, $args );
}

/**
 * TODO revisit this function
 */
function kickpress_update_relationship( $name, $args ) {
	if ( empty( $name ) )
		return;

	$slug = sanitize_title( $name );

	if ( empty( $slug ) )
		return;

	$defaults = array(
		'source_type' => '',
		'source_qty'  => 'multiple',
		'target_type' => '',
		'target_qty'  => 'multiple',
		'action_key'  => 'update'
	);

	extract( wp_parse_args( $args, $default ), EXTR_SKIP );

	if ( empty( $source_type ) || empty( $target_type ) )
		return false;

	$option = get_option( 'kickpress_relationships', array() );

	if ( isset( $option[$slug] ) ) {
		switch ( $action_key ) {
			case 'add':
				return false;
			case 'update':
				if ( $source_type != $option[$slug]['source']['type'] ||
					$target_type != $option[$slug]['target']['type'] )
					return false;
		}
	}

	$source_type_object = get_post_type_object( $source_type );
	$target_type_object = get_post_type_object( $target_type );

	if ( is_null( $source_type_object ) || is_null( $target_type_object ) )
		return false;

	/* if ( 'multiple' == $source_qty && 'single' == $target_qty ) {
		$object_type = $target_type;
		$object_qty  = $target_qty;

		$target_type = $source_type;
		$target_qty  = $source_qty;

		$source_type = $object_type;
		$source_qty  = $object_qty;

		unset( $object_type, $object_qty );
	} */

	$option[$slug] = array(
		'name'   => $name,
		'source' => array(
			'type' => $source_type,
			'qty'  => $source_qty
		),
		'target' => array(
			'type' => $target_type,
			'qty'  => $target_qty
		)
	);

	return update_option( 'kickpress_relationships', $option );
}

/**
 * TODO revisit this function
 */
function kickpress_remove_relationship( $name ) {
	if ( empty( $name ) ) return false;

	$slug = sanitize_title( $name );

	$option = get_option( 'kickpress_relationships', array() );

	if ( isset( $option[$slug] ) ) unset( $option[$slug] );

	return update_option( 'kickpress_relationships', $option );
}

function kickpress_get_related_posts( $post, $rel_name, $args = array() ) {
	global $wpdb;

	if ( is_numeric( $post ) )
		$post = get_post( $post );

	if ( ! is_object( $post ) )
		return new WP_Error( 'no_post_found',
			__( 'The selected post could not be found.', 'kickpress' ) );

	$rel = kickpress_get_relationship( $rel_name );

	if ( is_wp_error( $rel ) )
		return $rel;

	$types = array( $rel->source_type, $rel->target_type );

	if ( ! in_array( $post->post_type, $types ) )
		return new WP_Error( 'invalid_post_type',
			__( 'The selected post type is invalid for this relationship.', 'kickpress' ) );

	if ( $post->post_type == $rel->source_type ) {
		$object_type = $rel->target_type;
		$object_id   = 'target_id';
		$post_id     = 'source_id';
	} else {
		$object_type = $rel->source_type;
		$object_id   = 'source_id';
		$post_id     = 'target_id';
	}

	$prefix = $wpdb->prefix . 'kp_';

	$sql = "SELECT `{$object_id}` AS `post_id` "
	     . "FROM `{$prefix}post_relationships` AS `rel` "
	     . "WHERE `rel`.`{$post_id}` = {$post->ID} "
	     . "AND `rel`.`relationship` = '{$rel->name}' ";

	$ids = $wpdb->get_col( $sql );

	if ( empty( $ids ) )
		return array();

	$defaults = array(
		'post_status' => 'publish'
	);

	$overrides = array(
		'post__in'  => $ids,
		'post_type' => $object_type
	);

	$query_args = wp_parse_args( $args, $defaults );
	$query_args = wp_parse_args( $overrides, $query_args );

	if ( 'any' == $query_args['post_status'] )
		unset( $query_args['post_status'] );

	$query = new WP_Query( $query_args );

	return $query->get_posts();
}

function kickpress_add_related_post( $post, $rel_name, $rel_post ) {
	global $wpdb;

	if ( is_numeric( $post ) )     $post     = get_post( $post );
	if ( is_numeric( $rel_post ) ) $rel_post = get_post( $rel_post );

	if ( ! is_object( $post ) )     return;
	if ( ! is_object( $rel_post ) ) return;

	$rel = kickpress_get_relationship( $rel_name );

	if ( $rel->source_type == $post->post_type && $rel->target_type == $rel_post->post_type ) {
		$src_post = $post;
		$tgt_post = $rel_post;
	} elseif ( $rel->source_type == $rel_post->post_type && $rel->target_type == $post->post_type ) {
		$src_post = $rel_post;
		$tgt_post = $post;
	} else return;

	$result = $wpdb->insert( $wpdb->prefix . 'kp_post_relationships', array(
		'source_id' => $src_post->ID,
		'target_id' => $tgt_post->ID,
		'relationship' => $rel_name
	) );

	return $result;
}

function kickpress_remove_related_post( $post, $rel_name, $rel_post ) {
	global $wpdb;

	if ( is_numeric( $post ) )     $post     = get_post( $post );
	if ( is_numeric( $rel_post ) ) $rel_post = get_post( $rel_post );

	if ( ! is_object( $post ) )     return;
	if ( ! is_object( $rel_post ) ) return;

	$rel = kickpress_get_relationship( $rel_name );

	if ( $rel->source_type == $post->post_type && $rel->target_type == $rel_post->post_type ) {
		$src_post = $post;
		$tgt_post = $rel_post;
	} elseif ( $rel->source_type == $rel_post->post_type && $rel->target_type == $post->post_type ) {
		$src_post = $rel_post;
		$tgt_post = $post;
	} else return;

	$wpdb->delete( $wpdb->prefix . 'kp_post_relationships', array(
		'source_id'    => $src_post->ID,
		'target_id'    => $tgt_post->ID,
		'relationship' => $rel_name
	) );
}

function kickpress_relationships_save_post( $post_id ) {
	if ( wp_is_post_revision( $post_id ) ) return;

	global $kickpress_relationships;

	if ( empty( $kickpress_relationships ) ) return;

	$object_type = get_post_type();

	foreach ( $kickpress_relationships as $rel ) {
		$post_types = array( $rel->source_type, $rel->target_type );

		if ( in_array( $object_type, $post_types ) ) {
			$old_posts = kickpress_get_related_posts( $post_id, $rel->name,
				array( 'posts_per_page' => -1, 'post_status' => 'any' ) );

			$new_posts = @array_unique( $_REQUEST['rel_posts'][$rel->name] );

			foreach ( (array) $old_posts as $old_post ) {
				$key = array_search( $old_post->ID, $new_posts );

				if ( $key === false ) {
					kickpress_remove_related_post( $post_id,
						$rel->name, $old_post );
				} else {
					unset( $new_posts[$key] );
				}
			}

			foreach ( (array) $new_posts as $new_post ) {
				if ( 0 < intval( $new_post ) ) {
					kickpress_add_related_post( $post_id,
						$rel->name, intval( $new_post ) );
				}
			}
		}
	}
}

function kickpress_relationships_meta_boxes() {
	global $kickpress_post_types;

	add_meta_box( 'post-related-posts', 'Related Posts',
		'kickpress_relationships_meta_box', 'post', 'side', 'high' );

	foreach ( $kickpress_post_types as $slug => $post_type ) {
		if ( ! in_array( $slug, array( 'any', 'post' ) ) ) {
			add_meta_box( $post_type['post_type'] . '-related-posts',
				'Related Posts', 'kickpress_relationships_meta_box',
				$post_type['post_type'], 'side', 'high' );
		}
	}
}

function kickpress_relationships_meta_box( $post ) {
	global $post, $kickpress_relationships;

	$relationships = array();
	if ( empty( $kickpress_relationships ) )
		return;

	foreach ( $kickpress_relationships as $key => $rel ) {
		if ( $post->post_type == $rel->source_type ) {
			$relationships[$key] = (object) array(
				'label'       => $rel->label,
				'object_type' => $rel->target_type,
				'object_qty'  => $rel->target_qty
			);
		} elseif ( $post->post_type == $rel->target_type ) {
			$relationships[$key] = (object) array(
				'label'       => $rel->label,
				'object_type' => $rel->source_type,
				'object_qty'  => $rel->source_qty
			);
		}
	}
?>
<style type="text/css">
	.rel-post-search {
		margin: 1em 0em;
	}

	/* .ajax-post-search {
		position: absolute;
		z-index: 100;
		width: 100%;
		padding: 10px;
		border: 1px solid #999;
		background-color: #fff;
	} */

	.rel-post-search-pages {
		width: 100%;
		overflow: hidden;
	}

	.rel-post-search-previous {
		float: left;
	}

	.rel-post-search-next {
		float: right;
	}

	.related-posts .empty {
		font-style: italic;
	}

	.related-posts .remove-post {
		display: block;
		float: left;
		width: 16px;
		height: 16px;
		margin-right: 4px;
		background: url('<?php echo plugins_url( 'includes/images/icons/delete.png', __FILE__ ); ?>') center no-repeat;
		text-indent: -999999px;
	}
</style>
<script type="text/javascript">
	var is_rel_search = false;

	jQuery(document).ready(function($) {
		$('#post').bind('submit', function(event) {
			if (is_rel_search) {
				$('#' + is_rel_search).siblings('.button').click();

				$('#publish').removeClass('button-primary-disabled');
				$('#ajax-loading').css('visibility', 'hidden');

				return false;
			}
		});

		$('.rel-post-title').each(function(index, item) {
			$(item).bind('focus', function(event) {
				is_rel_search = $(item).attr('id');
			});

			$(item).bind('blur', function(event) {
				is_rel_search = false;
			});
		});

		$('div.relationship a.hide-if-no-js').each(function(index, item) {
			$(item).click(function(event) {
				$(this).hide();
				//$(this).parents('.relationship').children('.related-posts').hide('blind', null, 'fast');

				var div = $(this.href.match(/#[a-z\-]+/)[0]);

				div.show('blind', null, 'fast');

				div.children('.rel-post-title').focus();

				return false;
			});
		});

		$('.rel-post-search .button').each(function(index, item) {
			$(this).click(function(event) {
				var str = $(this).siblings('.rel-post-title').val();
				var typ = $(this).siblings('.rel-post-type').val();
				var qty = $(this).siblings('.rel-post-qty').val();

				var args = {
					s: str,
					post_type: typ,
					post_qty: qty,
					action: 'post_search'
				};

				var resbox = $(this).siblings('.ajax-post-search').first();

				resbox.html('<ul><li style="font-style: italic;">Loading...</li></ul>');

				jQuery.post(ajaxurl, args, function(response) {
					resbox.html(response);
				});
			});
		});

		$('.rel-post-search .cancel').each(function(index, item) {
			$(this).click(function(event) {
				var form = $(this).parents('.rel-post-search');
				var list = $(this).parents('.relationship').children('.related-posts');

				form.hide('blind', null, 'fast');
				list.show('blind', null, 'fast');

				$(this).parents('.relationship').children('a.hide-if-no-js').show();
				$(this).parents('.rel-post-search').children('.rel-post-title').val('');
				$(this).parents('.rel-post-search').children('.ajax-post-search').empty();

				return false;
			});
		});

		$('.rel-post-search-result').live('click', function(event) {
			var post_id = $(this).attr('rel');

			var form = $(this).parents('.rel-post-search');
			var list = $(this).parents('.relationship').children('.related-posts');

			var slug = form.attr('id').substr(16);

			var input_id = 'rel_posts-' + slug + '-' + post_id;

			var label = $('<label/>', {
				'for': input_id
			}).text(' ' + $(this).text());

			var input = $('<input/>', {
				'id': input_id,
				'type':  'checkbox',
				'name':  'rel_posts[' + slug + '][]',
				'value': post_id,
				'checked': 'checked'
			});

			if ($(this).hasClass('single')) list.empty();

			list.children('.empty').remove();
			list.append($('<div/>', {
				'class': 'related-post'
			}).append(input).append(label));

			$(this).parents('li').first().remove();

			return false;
		});

		$('.rel-post-search-pages a').live('click', function(event) {
			var resbox = $(this).parents('.rel-post-search').children('.ajax-post-search');

			var str = $(this).parents('.rel-post-search').children('.rel-post-title').val();
			var typ = $(this).parents('.rel-post-search').children('.rel-post-type').val();
			var qty = $(this).parents('.rel-post-search').children('.rel-post-qty').val();

			var args = {
				s: str,
				post_type: typ,
				post_qty: qty,
				action: 'post_search'
			};

			var pairs = this.href.match(/[a-z\-_]+=[^\?&#]+/);

			for (var index in pairs) {
				if (['index', 'input'].indexOf(index) < 0) {
					var parts = pairs[index].split('=');
					args[parts[0]] = parts[1];
				}
			}

			jQuery.post(ajaxurl, args, function(response) {
				resbox.html(response);
			});

			return false;
		});

		$('.related-post .remove-post').live('click', function(event) {
			$(this).parents('.related-post').remove();

			var list = $(this).parents('.related-posts');

			if (list.children().length == 0) {
				list.append($('<div/>', {
					'class': 'empty'
				}).text('empty'));
			}
		});
	});
</script>
<?php
	foreach ( $relationships as $name => $rel ) {
		$rel_posts = kickpress_get_related_posts( $post, $name,
			array( 'posts_per_page' => -1, 'post_status' => 'any' ) );

		$type = get_post_type_object( $rel->object_type );
?>
<div class="relationship">
	<a href="#rel-post-search-<?php echo $name; ?>" class="hide-if-no-js" style="float: right;">Search</a>
	<h4><?php echo $rel->label; ?><br><small><?php echo $rel->object_type; ?></small></h4>
	<div id="rel-post-search-<?php echo $name; ?>"  class="rel-post-search hide-if-js">
		<label><?php echo $type->labels->search_items; ?>:</label><br>
		<input type="text" name="rel_post_title[<?php echo $name; ?>]" id="rel-post-title-<?php echo $name; ?>" class="rel-post-title" style="display: block; width: 95%;">
		<input type="hidden" name="rel_post_type[<?php echo $name; ?>]" value="<?php echo $rel->object_type; ?>" class="rel-post-type">
		<input type="hidden" name="rel_post_qty[<?php echo $name; ?>]" value="<?php echo $rel->object_qty; ?>" class="rel-post-qty">
		<input type="button" value="Search" class="button">
		<a href="#" class="cancel">Close</a>
		<div id="ajax-post-search-<?php echo $name; ?>" class="ajax-post-search"></div>
	</div>
	<div id="related-posts-<?php echo $name; ?>" class="related-posts">
		<input type="hidden" name="rel_posts[<?php echo $name; ?>][]">
		<?php if ( ! empty( $rel_posts ) ) : ?>
		<?php if ( 'multiple' == $rel->object_qty ) : foreach ( $rel_posts as $rel_post ) :
			$input_id = "rel_posts-{$name}-{$rel_post->ID}"; ?>
		<div class="related-post">
			<input id="<?php esc_attr_e( $input_id ); ?>"
				type="checkbox" checked="checked"
				name="rel_posts[<?php echo $name; ?>][]"
				value="<?php echo $rel_post->ID; ?>">
			<label for="<?php esc_attr_e( $input_id ); ?>">
				<?php echo $rel_post->post_title; ?>
			</label>
		</div>
		<?php endforeach; elseif ( 'single' == $rel->object_qty ) :
			$input_id = "rel_posts-{$name}-{$rel_posts[0]->ID}"; ?>
		<div class="related-post">
			<input id="<?php esc_attr_e( $input_id ); ?>"
				type="checkbox" checked="checked"
				name="rel_posts[<?php echo $name; ?>][]"
				value="<?php echo $rel_posts[0]->ID; ?>">
			<label for="<?php esc_attr_e( $input_id ); ?>">
				<?php echo $rel_posts[0]->post_title; ?>
			</label>
		</div>
		<?php endif; ?>
		<?php else : ?>
		<span class="empty">none</span>
		<?php endif; ?>
	</div>
</div>
<?php
	}
}

function kickpress_relationships_ajax_box() {
	global $paged;

	$paged = isset( $_REQUEST['paged'] ) ? (int) $_REQUEST['paged'] : 1;

	$args = array(
		'orderby' => 'title',
		'order' => 'ASC',
		'offset' => ( $paged - 1 ) * 5,
		'posts_per_page' => 5,
		'posts_per_archive_page' => 5
	);

	if ( ! empty( $_REQUEST['s'] ) ) $args['s'] = $_REQUEST['s'];
	if ( ! empty( $_REQUEST['post_type'] ) ) $args['post_type'] = $_REQUEST['post_type'];

	remove_filter( 'posts_orderby', 'kickpress_query_orderby' );
	remove_filter( 'post_limits',   'kickpress_query_limits' );

	query_posts( $args );

	global $wp_query;

	if ( have_posts() ) {
?>
<ul>
	<?php while ( have_posts() ) : the_post(); ?>
	<li>+ <a rel="<?php the_ID(); ?>" href="#" class="rel-post-search-result <?php echo $_REQUEST['post_qty']; ?>"><?php the_title(); ?></a></li>
	<?php endwhile; ?>
</ul>
<div class="rel-post-search-pages">
	<div class="rel-post-search-previous"><?php previous_posts_link( '&larr; Prev' ); ?></div>
	<div class="rel-post-search-next"><?php next_posts_link( 'Next &rarr;' ); ?></div>
</div>
<?php
	} else {
?>
<ul>
	<li style="font-style: italic;">No Results</li>
</ul>
<?php
	}

	exit();
}

function kickpress_relationships_menu_box() {
	add_submenu_page( 'edit.php?post_type=custom-post-types', 'Post Type Relationships', 'Relationships',
		'manage_options', 'post-type-relationships', 'kickpress_relationships_callback' );
}

function kickpress_relationships_callback() {
	global $kickpress_post_types;

	if ( isset( $_REQUEST['action'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'single-relationship' ) ) {
		$option = get_option( 'kickpress_relationships', array() );

		$redirect = false;

		switch ( $_REQUEST['action'] ) {
			case 'save':
				$rel = $_REQUEST['rel'];

				if ( ! empty( $rel['name'] ) ) {
					kickpress_update_relationship( $rel['name'], array(
						'source_type' => $rel['source']['type'],
						'source_qty'  => $rel['source']['qty'],
						'target_type' => $rel['target']['type'],
						'target_qty'  => $rel['target']['qty']
					) );

					// $redirect = wp_get_referer();
					$redirect = 'edit.php?post_type=custom-post-types&page=post-type-relationships';
				} else {
					$msg = "You must specify a name for this relationship.";
				}

				break;
			case 'delete':
				kickpress_remove_relationship( $_REQUEST['name'] );

				$redirect = wp_get_referer();

				break;
		}

		if ( $redirect ) {
			echo sprintf( '<script type="text/javascript">document.location = "%s";</script>',
				$redirect
			);
		}
	}

	$table = new Relationship_List_Table();

	$post_types = array();

	foreach ( $kickpress_post_types as $post_type ) {
		if ( isset( $post_type['post_type'] ) ) {
			$post_types[] = get_post_type_object( $post_type['post_type'] );
		}
	}

	$option = get_option( 'kickpress_relationships', array() );
?>
<style type="text/css">
	.form-wrap input[type="radio"],
	.form-wrap label.radio {
		display: inline;
		width: auto;
	}
</style>
<?php if ( 'edit' == $_REQUEST['view'] && isset( $option[$_REQUEST['name']])) :
	$rel = $option[$_REQUEST['name']];
	$source_type = get_post_type_object($rel['source']['type']);
	$target_type = get_post_type_object($rel['target']['type']); ?>
<div class="wrap">
	<div id="icon-edit" class="icon32"><br></div>
	<h2>Post Type Relationships</h2>
	<div class="form-wrap">
		<h3>Edit Relationship</h3>
		<form action="" method="post">
			<input type="hidden" name="post_type" value="custom-post-types">
			<input type="hidden" name="page" value="post-type-relationships">
			<input type="hidden" name="action" value="save">
			<?php wp_nonce_field( 'single-relationship' ); ?>
			<div class="form-field form-required">
				<label>Name</label>
				<h4><?php echo $rel['name']; ?></h4>
				<input type="hidden" name="rel[name]" value="<?php esc_attr_e($rel['name']); ?>">
			</div>
			<div class="form-field form-required">
				<label>Source Type / Quantity</label>
				<h4><?php echo $source_type->label; ?></h4>
				<input type="hidden" name="rel[source][type]" value="<?php esc_attr_e($rel['source']['type']); ?>">
				<select name="rel[source][qty]">
					<option value="single"<?php if ( 'single' == $rel['source']['qty'] ) : ?> selected="selected"<?php endif; ?>>Single</option>
					<option value="multiple"<?php if ( 'multiple' == $rel['source']['qty'] ) : ?> selected="selected"<?php endif; ?>>Multiple</option>
				</select>
			</div>
			<div class="form-field form-required">
				<label>Target Type / Quantity</label>
				<h4><?php echo $target_type->label; ?></h4>
				<input type="hidden" name="rel[target][type]" value="<?php esc_attr_e($rel['target']['type']); ?>">
				<select name="rel[target][qty]">
					<option value="single"<?php if ( 'single' == $rel['target']['qty'] ) : ?> selected="selected"<?php endif; ?>>Single</option>
					<option value="multiple"<?php if ( 'multiple' == $rel['target']['qty'] ) : ?> selected="selected"<?php endif; ?>>Multiple</option>
				</select>
			</div>
			<p class="submit"><input id="submit" class="button" type="submit" name="submit" value="Update Relationship"></p>
		</form>
	</div>
</div>
<?php else : ?>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$('.delete-relationship').click(function() {
			return confirm('Are you sure you want to delete this relationship?');
		});
	});
</script>
<div class="wrap">
	<div id="icon-edit" class="icon32"><br></div>
	<h2>Post Type Relationships</h2>
	<?php if ( isset( $msg ) ) : ?>
	<div id="message" class="updated below-h2">
		<p><?php echo $msg; ?></p>
	</div>
	<?php endif; ?>
	<div id="ajax-response"></div>
	<div id="col-container">
		<div id="col-right">
			<div class="col-wrap">
				<form action="" method="post">
					<input type="hidden" name="post_type" value="custom-post-types">
					<input type="hidden" name="page" value="post-type-relationships">
					<?php $table->display(); ?>
				</form>
			</div>
		</div>
		<div id="col-left">
			<div class="col-wrap">
				<div class="form-wrap">
					<h3>Add New Relationship</h3>
					<form action="" method="post">
						<input type="hidden" name="post_type" value="custom-post-types">
						<input type="hidden" name="page" value="post-type-relationships">
						<input type="hidden" name="action" value="save">
						<?php wp_nonce_field( 'single-relationship' ); ?>
						<div class="form-field form-required">
							<label for="relationship-name">Name</label>
							<input id="relationship-name" type="text" name="rel[name]" class="regular-text">
							<p></p>
						</div>
						<div class="form-field form-required">
							<label for="source-type">Source Type</label>
							<select id="source-type" name="rel[source][type]" aria-required="true">
								<option value="">- Select Post Type -</option>
								<?php foreach ( $post_types as $post_type ) : ?>
								<option value="<?php echo $post_type->name; ?>"><?php echo $post_type->label; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-field form-required">
							<label>Source Quantity</label>
							<select name="rel[source][qty]">
								<option value="single">Single</option>
								<option value="multiple" selected="selected">Multiple</option>
							</select>
						</div>
						<div class="form-field form-required">
							<label for="target-type">Target Type</label>
							<select id="target-type" name="rel[target][type]" aria-required="true">
								<option value="">- Select Post Type -</option>
								<?php foreach ( $post_types as $post_type ) : ?>
								<option value="<?php echo $post_type->name; ?>"><?php echo $post_type->label; ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-field form-required">
							<label>Target Quantity</label>
							<select name="rel[target][qty]">
								<option value="single">Single</option>
								<option value="multiple" selected="selected">Multiple</option>
							</select>
						</div>
						<p class="submit"><input id="submit" class="button" type="submit" name="submit" value="Add New Relationship"></p>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php endif;
}

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Relationship_List_Table extends WP_List_Table {
	var $_menu_slug = 'custom-post-types';
	var $_page_slug = 'post-type-relationships';

	function __construct() {
		parent::__construct( array(
			'singular' => 'relationship',
			'plural'   => 'relationships'
		) );
	}

	function display() {
		if ( '' != $this->current_action() )
			$this->process_bulk_action();

		$this->prepare_items();

		parent::display();
	}

	function prepare_items() {
		$option = get_option( 'kickpress_relationships', array() );

		$relationships = array();

		foreach ( $option as $rel ) {
			$src_type = $rel['source']['type'];
			$src_qty  = $rel['source']['qty'];
			$tgt_type = $rel['target']['type'];
			$tgt_qty  = $rel['target']['qty'];

			$src_obj = get_post_type_object( $src_type );
			$tgt_obj = get_post_type_object( $tgt_type );

			if ( is_null( $src_obj ) || is_null( $tgt_obj ) ) continue;

			$relationships[] = (object) array(
				'name'        => $rel['name'],
				'slug'        => sanitize_title( $rel['name'] ),
				'source_type' => $src_obj,
				'source_qty'  => $src_qty,
				'target_type' => $tgt_obj,
				'target_qty'  => $tgt_qty
			);
		}

		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, array(), $sortable );

		$this->items = $relationships;

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
			'title'       => 'Relationship',
			'source_type' => 'Source Type',
			'source_qty' => 'Source Quantity',
			'target_type' => 'Target Type',
			'target_qty' => 'Target Quantity'
		);
	}

	function get_sortable_columns() {
		return array(
			array('source_type', true),
			array('target_type', true)
		);
	}

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%s[]" value="%s">',
			$this->_args['singular'],
			$item->slug
		);
	}

	function column_title( $item ) {
		$base_url = sprintf( 'edit.php?post_type=%s&page=%s', $this->_menu_slug, $this->_page_slug );
		$item_url = sprintf( '%s&name=%s', $base_url, $item->slug );

		$actions = array(
			'edit' => sprintf( '<a href="%s&view=%s" class="edit-relationship">Edit</a>',
				wp_nonce_url( $item_url, 'single-' . $this->_args['singular'] ),
				'edit'
			),
			'delete' => sprintf( '<a href="%s&action=%s" class="delete-relationship">Delete</a>',
				wp_nonce_url( $item_url, 'single-' . $this->_args['singular'] ),
				'delete'
			)
		);

		return sprintf( '%s %s',
			$item->name,
			$this->row_actions( $actions )
		);
	}

	function column_source_type( $item ) {
		return $item->source_type->label;
	}

	function column_source_qty( $item ) {
		return ucfirst( $item->source_qty );
	}

	function column_target_type( $item ) {
		return $item->target_type->label;
	}

	function column_target_qty( $item ) {
		return ucfirst( $item->target_qty );
	}

	function column_default( $item, $column_key ) {
		return '';
	}

	function get_bulk_actions() {
		return array(
			'delete' => 'Delete'
		);
	}

	function process_bulk_action() {
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) return;

		switch ( $this->current_action() ) {
			case 'delete':
				$names = $_REQUEST[$this->_args['singular']];

				foreach ( (array) $names as $name )
					kickpress_remove_relationship( $name );

				break;
		}

		echo sprintf( '<script type="text/javascript">document.location = "%s";</script>',
			wp_get_referer()
		);

		exit();
	}
}

?>