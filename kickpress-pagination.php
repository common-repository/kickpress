<?php

/** 
 * This file holds the pagination logic used within kickpress custom post types
 */

// Defines the action hook named 'kickpress_pagination'
add_action( 'kickpress_pagination', 'kickpress_pagination', 10, 1 );

//do_action( 'kickpress_pagination', $a );

// add_action( $tag, $function_to_add, $priority, $accepted_args );

function kickpress_pagination( $args = array() ) {
	$defaults = array(
		'widget_wrapper' => false
	);
	
	$pagination_args = array_merge($defaults, $args);
/*
	if ( !empty( $args['post_type'] ) )
		$pagination_args['post_type'] = $args['post_type'];

	if ( !empty( $args['pagination_type'] ) )
		$pagination_args['pagination_type'] = $args['pagination_type'];

	if ( !empty( $args['posts_per_page'] ) )
		$pagination_args['posts_per_page'] = $args['posts_per_page'];

	if ( !empty( $args['target'] ) )
		$pagination_args['target'] = $args['target'];

	if ( !empty( $args['class'] ) )
		$pagination_args['class'] = $args['class'];
*/
	$before_widget = sprintf(
		'<aside id="%1$s" class="widget %2$s">',
		'kickpress_pagination_widget-0',
		'kickpress_pagination_widget'
	);

	the_widget( 'kickpress_pagination_widget', $pagination_args, array(
		'before_widget' => apply_filters( 'kickpress_before_widget', $before_widget ),
		'after_widget'  => apply_filters( 'kickpress_after_widget', '</aside>' ),
		'before_title'  => apply_filters( 'kickpress_before_title', '<h3 class="widget-title">' ),
		'after_title'   => apply_filters( 'kickpress_after_title', '</h3>' )
	) );
}

function kickpress_get_pagination( $args = array() ) {
	global $wp_query;

	if ( ! ( $wp_query->max_num_pages > 1 ) ) return;

	$page = get_query_var( 'paged' );
	$page = ! empty( $page ) ? intval( $page ) : 1;
	$posts_per_page = intval( get_query_var( 'posts_per_page' ) );

	$default_args = array(
		'pagination_type'        => 'default',
		'path'                   => '',
		'show_results_per_page'  => true,
		'results'                => array(
			'-1'  => 'ALL',
			'5'   => '5',
			'10'  => '10',
			'20'  => '20',
			'50'  => '50',
			'100' => '100'
		),
		'posts_per_page'         => $posts_per_page,
		'post_type'              => null,
		'page'                   => $page,
		'max_num_pages'          => intval( ceil( $wp_query->found_posts / $posts_per_page ) ),
		'offset_by_id'           => null,
		'found_posts'            => $wp_query->found_posts,
		'target'                 => 'content'
	);
	
	$args = array_merge( $default_args, $args );

	$pagination_type = 'kickpress_pagination_' . $args['pagination_type'];
	
	$html = function_exists( $pagination_type )
		  ? call_user_func( $pagination_type, $args )
		  : '';

	return $html;
}

function kickpress_pagination_wp( $args = array() ) {
	$nav_id = 'test';
?>
	<nav id="<?php echo $nav_id; ?>">
		<h3 class="assistive-text"><?php _e( 'Post navigation', 'kickpress' ); ?></h3>
		<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts', 'kickpress' ) ); ?></div>
		<div class="nav-next"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>', 'kickpress' ) ); ?></div>
	</nav><!-- #nav-above -->
<?php
}

function kickpress_pagination_default($args=array()) {
	extract($args);
	
	$max_num_pages = ( $posts_per_page != -1 ? ceil($found_posts / $posts_per_page) : 1 );

	if ( $show_results_per_page && ($found_posts > $results[1]) ) {
		$pages = '<label>'.__('Results', 'kickpress').':</label>';
		
		$filter_pairs = kickpress_filter_pairs();
		
		foreach ( $results as $key=>$value ) {
			if ( $found_posts > $key || $value == 'ALL' ) {
				if ( $posts_per_page != $key ) {
					$query_string = kickpress_query_pairs($args, array_merge($filter_pairs, array('page'=>'1','posts_per_page'=>$key)));

					$pages .= sprintf('<a href="%2$s" rel="%1$s-wrapper">%3$s</a>',
						$post_type,
						$query_string,
						$value
					);
				} else {
					$pages .= sprintf('<label class="active">%1$s</label>', $value);
				}
			}
		}
	} else {
		$pages = "&nbsp;";
	}

	$html = sprintf('
		<div class="toolbar">
			<div class="grid-col left-col">%1$s</div>
			<div class="grid-col right-col">',
			$pages
	);

	if ( $max_num_pages > 1 ) {
		if ( $page > 1 ) {
			$html .= kickpress_active_button($args, $path, $page, $page-1, 'previous', 'previous');
			if ( $max_num_pages > 2 ) {
				$html .= kickpress_active_button($args, $path, $page, 1, 1, 'first');
			}
		} else {
			$html .= kickpress_active_button($args, $path, $page, 1, 'previous', 'previous');
			if ( $max_num_pages > 2 ) {
				$html .= kickpress_active_button($args, $path, $page, 1, 1, 'first');
			}
		}

		// adjacents are the number of pages on either side of a selected page number
		$adjacents = 1;
		$leading = "";
		$trailing = "";

		if ( $max_num_pages <= (5 + ($adjacents * 2)) ) {
			$startCount = 2;
			$endCount = ($max_num_pages - 1);
		} elseif ( $max_num_pages > (5 + ($adjacents * 2)) ) {
			if ( $page < (2 + ($adjacents * 2)) ) {
				$startCount = 2;
				$endCount = (3 + ($adjacents * 2));
				$trailing = "<label>&hellip;</label>";
			} elseif ( ($max_num_pages - ($adjacents * 2) > $page) && ($page > ($adjacents * 2)) ) {
				$leading = "<label>&hellip;</label>";
				$startCount = ($page - $adjacents);
				$endCount = ($page + $adjacents);
				$trailing = "<label>&hellip;</label>";
			} else {
				$leading = "<label>&hellip;</label>";
				$startCount = ($max_num_pages - (2 + ($adjacents * 2)));
				$endCount = ($max_num_pages - 1);
			}
		}

		$html .= $leading;
		for ( $counter = $startCount; $counter <= $endCount; $counter++ ) {
			$html .= kickpress_active_button($args, $path, $page, $counter, $counter);
		}
		$html .= $trailing;

		if ( $page < $max_num_pages ) {
			if ( $max_num_pages > 2 ) {
				$html .= kickpress_active_button($args, $path, $page, $max_num_pages, $max_num_pages, 'last');
			}
			$html .= kickpress_active_button($args, $path, $page, $page+1, 'next', 'next');
		} else {
			if ( $max_num_pages > 2 ) {
				$html .= kickpress_active_button($args, $path, $page, $max_num_pages, $max_num_pages, 'last');
			}
			$html .= kickpress_active_button($args, $path, $page, $max_num_pages, 'next', 'next');
		}
	}
	$html .= '<br style="float: none; clear: both; width: 0px; height: 0px;">';
	$html .= '</div></div>';
	return $html;
}

function kickpress_active_button($args, $path, $page, $next_page_number, $text, $cssClass="") {
	global $wp_query;
	$filter_pairs = kickpress_filter_pairs();

	if ( ! is_array($wp_query->query_vars['post_type']) )
		$post_type = $wp_query->query_vars['post_type'];
	else
		$post_type = 'any';

	if ( $page != $next_page_number ) {
		$filter_pairs['page'] = $next_page_number;
/*
		if ( $path == null || trim($path) == '' )
			$path = esc_url(get_pagenum_link($next_page_number));
*/
		$query_string = kickpress_query_pairs($args, $filter_pairs, $path, true);

		$html = sprintf('<a href="%2$s" title="%3$s" rel="%1$s-wrapper"><span%4$s>%3$s</span></a>',
			$post_type,
			$query_string,
			__($text, 'kickpress'),
			( $cssClass != '' ? ' class="'.$cssClass.'"' : '' )
		);
	} else {
		$html = sprintf('<label class="active"><span%2$s>%1$s</span></label>',
			__($text, 'kickpress'),
			( $cssClass != '' ? ' class="'.$cssClass.'"' : '' )
		);
	}
	return $html;
}

function kickpress_pagination_more($args=array()) {
	global $wp_query;
	extract($args);

	$filter_pairs = kickpress_filter_pairs();
	$max_num_pages = ( $posts_per_page != -1 ? ceil($found_posts / $posts_per_page) : 1 );

	if ( $max_num_pages > 1 && $page < $max_num_pages ) {
		$filter_pairs['page'] = ($page + 1);

		$query_string = kickpress_query_pairs($args, $filter_pairs, $path, (empty($path) ? true : false));

		$html = sprintf( '
			<div class="load-more-toolbar">
				<a href="%1$s" rel="%2$s" class="%3$s" title="%4$s">%4$s</a>
			</div>',
			$query_string,
			$target,
			! empty ($class) ? $class : 'ajax-append',
			__('Load More', 'kickpress')
		);

		add_action( 'wp_footer', 'kickpress_ajax_footer' );

		return $html;
	}

	return '';
}

?>