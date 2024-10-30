<?php

class kickpress_pagination_widget extends WP_Widget {
	public static $classname = __CLASS__;
	
	private static $_results = array(
		'-1'  => 'ALL',
		'5'   => '5',
		'10'  => '10',
		'20'  => '20',
		'50'  => '50',
		'100' => '100'
	);
	
	public function __construct() {
		parent::__construct( __CLASS__, 'Pagination', array(
			'description' => 'Pagination Widget',
			'classname'   => 'toolbar ' . self::$classname
		) );
	}
	
	public function form( $params ) {
		extract( $params );
		
		$types = array(
			'default' => 'Standard',
			'wp'      => 'Previous/Next',
			'more'    => 'Live'
		);
		
		$ajax_types = array(
			'ajax-append'  => 'Append',
			'ajax-replace' => 'Replace',
			'masonry-append' => 'Masonry'
		);
?>
<p>
	<label for="<?php echo $this->get_field_id( 'pagination_type' ); ?>"><?php _e( 'Pagination Type:', 'kickpress' ); ?></label>
	<select id="<?php echo $this->get_field_id( 'pagination_type' ); ?>"
		name="<?php echo $this->get_field_name( 'pagination_type' ); ?>">
		<?php foreach ( $types as $type => $title ) :
			$selected = $type == $pagination_type ? ' selected="selected"' : '';
		?>
		<option value="<?php echo $type; ?>"><?php echo $title; ?></option>
		<?php endforeach; ?>
	</select>
</p>
<h4>"Standard" Pagination Options</h4>
<p>
	<label for="<?php echo $this->get_field_id( 'max_num_links' ); ?>"><?php _e( 'Maximum Page Links:', 'kickpress' ); ?></label>
	<input id="<?php echo $this->get_field_id( 'max_num_links' ); ?>" type="text"
		name="<?php echo $this->get_field_name( 'max_num_links' ); ?>">
</p>
<h4>"Live" Pagination Options</h4>
<p>
	<label for="<?php echo $this->get_field_id( 'target' ); ?>"><?php _e( 'Reload Target:', 'kickpress' ); ?></label>
	<input id="<?php echo $this->get_field_id( 'target' ); ?>" type="text"
		name="<?php echo $this->get_field_name( 'target' ); ?>">
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'class' ); ?>"><?php _e( 'Reload Type:', 'kickpress' ); ?></label>
	<select id="<?php echo $this->get_field_id( 'class' ); ?>"
		name="<?php echo $this->get_field_name( 'class' ); ?>">
		<?php foreach ( $ajax_types as $type => $title ) :
			$selected = $type == $class ? ' selected="selected"' : '';
		?>
		<option value="<?php echo $type; ?>"><?php echo $title; ?></option>
		<?php endforeach; ?>
	</select>
</p>
<?php 
	}

	public function update( $new_params, $old_params ) {
		$params = $old_params;
		$params['pagination_type'] = strip_tags( $new_params['pagination_type'] );
		
		return $params;
	}
	
	public function widget( $args, $params ) {
		global $wp_query;

		$page = intval( get_query_var( 'paged' ) );
		
		if ( 0 == $page ) $page = 1;
		
		$posts_per_page = intval( get_query_var( 'posts_per_page' ) );
		
		$defaults = array(
			'widget_wrapper'         => true,
			'pagination_type'        => 'default',
			'show_results_per_page'  => true,
			'posts_per_page'         => $posts_per_page,

			//'post_type'              => null,

			'page'                   => $page,
			'max_num_pages'          => (int) $wp_query->max_num_pages,
			'max_num_links'          => 5,
			'offset_by_id'           => null,
			'found_posts'            => (int) $wp_query->found_posts,
			'target'                 => 'content',
			'class'                  => 'ajax-append', // ajax-append, ajax-replace, masonry-append
			'extra_pairs'            => array()
		);
		
		//$params = wp_parse_args( $params, $defaults );
		$params = array_merge( $defaults, $params );
		
		if ( -1 == $posts_per_page ) {
			$params['max_num_pages'] = 1;
			$params['found_posts'] = count( $wp_query->posts );
		}
		
		if ( $params['widget_wrapper'] )
			echo $args['before_widget'];

		$widget_method = 'widget_' . $params['pagination_type'];
		
		if ( method_exists( $this, $widget_method ) )
			call_user_func( array( $this, $widget_method ), $args, $params );
		
		
		if ( $params['widget_wrapper'] )
			echo $args['after_widget'];
	}

	private function widget_default( $args = array(), $params = array() ) {
		global $kickpress_api;
		
		extract( $args );
		extract( $params );
		
		printf( '<div class="toolbar"><div class="grid-col left-col">' );
		
		if ( $show_results_per_page ) {
			printf( '<ul><li>%s:</li>', __( 'Results', 'kickpress' ) );
			foreach ( self::$_results as $key => $value ) {
				if ( $found_posts > $key || $value == 'ALL' ) {
					if ( $posts_per_page != $key ) {
						$url = kickpress_api_url( array(
							'page' => 1,
							'posts_per_page' => $key,
							'sort' => @$kickpress_api->params['sort'],
							'extra_pairs' => $extra_pairs
						) );
	
						printf( '<li><a href="%s">%s</a></li>', $url, $value );
					} else {
						printf( '<li class="active">%s</li>', $value );
					}
				}
			}
			
			printf( '</ul>' );
		}
		
		printf( '</div><div class="grid-col right-col">' );
		
		if ( 1 < $max_num_pages ) {
			printf( '<ul>' );
			
			if ( 1 == $page ) {
				printf( '<li>%s</li>', __( 'First', 'kickpress' ) );
			} else {
				$url = kickpress_api_url( array( 'page' => 1 ) );
				printf( '<li><a href="%1$s">%2$s</a></li>', $url, __( 'First', 'kickpress' ) );
			}
			
			if ( 2 < $max_num_pages ) {
				if ( 1 == $page ) {
					printf( '<li>%s</li>', __( 'Previous', 'kickpress' ) );
				} else {
					$url = kickpress_api_url( array( 'page' => $page - 1 ) );
					printf( '<li><a href="%1$s">%2$s</a></li>', $url, __( 'Previous', 'kickpress' ) );
				}
			}
			
			if ( $max_num_pages > $max_num_links ) {
				$min_page_num = $page - (int) floor( ( $max_num_links - 1 ) / 2 );
				$max_page_num = $page + (int) ceil( ( $max_num_links - 1 ) / 2 );
				
				if ( 1 > $min_page_num ) {
					$min_page_num = 1;
					$max_page_num = $max_num_links;
				} elseif ( $max_num_pages < $max_page_num ) {
					$min_page_num = $max_num_pages - $max_num_links + 1;
					$max_page_num = $max_num_pages;
				}
				
				$leading  = "<li>&hellip;</li>";
				$trailing = "<li>&hellip;</li>";
			} else {
				$min_page_num = 1;
				$max_page_num = $max_num_pages;
			}
			
			if ( 1 < $min_page_num )
				printf( '<li>&hellip;</li>' );
			
			for ( $page_num = $min_page_num; $page_num <= $max_page_num; $page_num++ ) {
				if ( $page != $page_num ) {
					$url = kickpress_api_url( array(
						'page' => $page_num,
						'extra_pairs' => $extra_pairs
					) );
					
					printf( '<li><a href="%s">%d</a></li>', $url, $page_num );
				} else {
					printf( '<li class="active">%d</li>', $page_num );
				}
			}
			
			if ( $max_num_pages > $max_page_num )
				printf( '<li>&hellip;</li>' );
			
			if ( 2 < $max_num_pages ) {
				if ( $page < $max_num_pages ) {
					$url = kickpress_api_url( array(
						'page' => $page + 1,
						'extra_pairs' => $extra_pairs
					) );
					
					printf( '<li><a href="%1$s">%2$s</a></li>', $url, __( 'Next', 'kickpress' ) );
				} else {
					printf( '<li>%s</li>', __( 'Next', 'kickpress' ) );
				}
			}
			
			if ( $page < $max_num_pages ) {
				$url = kickpress_api_url( array(
					'page' => $max_num_pages,
					'extra_pairs' => $extra_pairs
				) );
				
				printf( '<li><a href="%1$s">%2$s</a></li>', $url, __( 'Last', 'kickpress' ) );
			} else {
				printf( '<li>%s</li>', __( 'Last', 'kickpress' ) );
			}
			
			printf( '</ul>' );
		}
		
		printf( '</div><div class="grid-break"></div></div>' );
	}
	
	private function widget_wp( $args = array(), $params = array() ) {
		extract( $args );
		extract( $params );
		
		if ( 1 < $max_num_pages ) {
			printf( '<div class="toolbar"><ul>' );
			
			if ( 1 < $page ) {
				$url = kickpress_api_url( array( 'page' => $page - 1 ) );
				printf( '<li class="page-previous"><a href="%s">Previous Page</a></li>', $url );
			}
			
			if ( $max_num_pages > $page ) {
				$url = kickpress_api_url( array( 'page' => $page + 1 ) );
				printf( '<li class="page-next"><a href="%s">Next Page</a></li>', $url );
			}
			
			printf( '</ul></div>' );
		}
	}
	
	private function widget_more( $args = array(), $params = array() ) {
		extract( $args );
		extract( $params );
		
		if ( $max_num_pages > 1 && $page < $max_num_pages ) {
			$params['page'] = $page + 1;
			$url = kickpress_api_url( $params );

			printf(
				'<div class="btn-toolbar load-more-toolbar clearfix" role="toolbar">' .
				'<a href="%1$s" rel="%2$s" class="btn btn-primary btn-spinner %3$s" title="%4$s"><i class="glyphicon glyphicon-refresh"></i> %4$s</a>' .
				'</div>',
				$url,
				$target,
				! empty( $class ) ? $class : 'ajax-append',
				__('Load More', 'kickpress')
			);

			add_action( 'wp_footer', 'kickpress_ajax_footer' );
		}
	}
	
	private function widget_masonry( $args = array(), $params = array() ) {
		extract( $args );
		extract( $params );
		
		if ( $max_num_pages > 1 && $page < $max_num_pages ) {
			$params['page'] = $page + 1;
			$url = kickpress_api_url( $params );
			
			printf(
				'<div class="load-more-toolbar">' .
				'<a href="%1$s" rel="%2$s" class="%3$s" title="%4$s">%4$s</a>' .
				'</div>',
				$url,
				$target,
				! empty( $class ) ? $class : 'masonry-append',
				__('Load More', 'kickpress')
			);
			
			add_action( 'wp_footer', 'kickpress_ajax_footer' );
		}
	}
}

?>