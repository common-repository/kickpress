<?php

class kickpress_customizable_widget extends WP_Widget {
	public static $classname = __CLASS__;

	public function __construct() {
		parent::__construct( __CLASS__, 'Customizable Widget', array(
			'description' => 'Customizable Widget for KickPress',
			'classname'   => self::$classname // str_replace('_', '-', self::$classname) 
		) );
	}

	public function widget( $args, $instance ) {
		// Get the post type
		$post_type = empty( $instance['post_type'] ) ? 'post' : $instance['post_type'];

		// Set values or default values
		$params = array(
			'title' => empty( $instance['title'] ) ? '' : $instance['title'],
			'show_title' => empty( $instance['show_title'] ) ? true : $instance['show_title'],
			'post_id' => empty( $instance['post_id'] ) ? null : $instance['post_id'],
			'view' => empty( $instance['view'] ) ? null : $instance['view'],
			'terms' => empty( $instance['terms'] ) ? null : $instance['terms'],
			'posts_per_page' => empty( $instance['posts_per_page'] ) ? '10' : $instance['posts_per_page'],
			'excerpt_length' => empty( $instance['excerpt_length'] ) ? '200' : $instance['excerpt_length'],
			'top_pagination_type' => empty( $instance['top_pagination_type'] ) ? 'none' : $instance['top_pagination_type'],
			'bottom_pagination_type' => empty( $instance['bottom_pagination_type'] ) ? 'none' : $instance['bottom_pagination_type'],
			'show_thumbnail' => empty( $instance['show_thumbnail'] ) ? true : $instance['show_thumbnail'],
			'thumb_size' => empty( $instance['thumb_size'] ) ? null : $instance['thumb_size'],
			'sort_direction' => empty( $instance['sort_direction'] ) ? 'DESC' : $instance['sort_direction'],
			'sort_field' => empty( $instance['sort_field'] ) ? 'date' : $instance['sort_field'],
			'callback' => empty( $instance['callback'] ) ? null : $instance['callback']
		);
		
		if ( $api = kickpress_init_api( $post_type, $params ) ) {
			extract( $api->params );

			echo $args['before_widget'];
			if ( $show_title ) {
				echo $args['before_title'];
				echo $title;
				echo $args['after_title'];
			}

			// Create a new query instance
			if ( ! empty($id) ) {
				$query_args = array(
					'p'=>$post_id,
					'posts_per_page'=>1
				);
			} else {
				$query_args = array(
					'orderby'=>$sort_field,
					'order'=>$sort_direction,
					'posts_per_page'=>$posts_per_page,
					'paged' => get_query_var('paged')
				);
				
				// Find an instance of the term array and parse it.
				if ( ! empty( $terms ) ) {
					$tax_query = array( 'relation' => 'AND' );
					$terms_array = explode(',', $terms);
					foreach ( $terms_array as $term ) {
						if ( $try_term = kickpress_parse_term_pair($term, $api->params['terms']) ) {
							$tax_query[] = array(
								'taxonomy' => $try_term[0],
								'field'    => 'slug',
								'terms'    => explode( ',', $try_term[1] )
							);
						}
					}
					$query_args['tax_query'] = $tax_query;
				}
			}

			$query_args['post_type'] = $post_type;
			$query_args['suppress_filters'] = true;

			kickpress_query($query_args);

			if ( isset($post_id) && method_exists($api, $callback) ) {
				echo $api->{$callback}();
			} else {
				if ( have_posts() ) {
					if ( ! empty( $instance['top_pagination_type'] ) && 'none' != $instance['top_pagination_type'] )
						kickpress_pagination( array( 'post_type' => $post_type, 'pagination_type' => $instance['top_pagination_type'] ) );
	
					echo '<div id="-wrapper">';
					while ( have_posts() ) {
						the_post();
						get_template_part( 'excerpt', $post_type );
					}
	
					if ( ! empty( $instance['bottom_pagination_type'] ) && 'none' != $instance['bottom_pagination_type'] )
						kickpress_pagination( array( 'post_type' => $post_type, 'pagination_type' => $instance['bottom_pagination_type'] ) );
	
					echo '</div><!-- #-wrapper -->';
				}
			}

			kickpress_reset_query();

			echo $args['after_widget'];
		}
	}

	public function form( $instance ) {
		global $wpdb;

		$sql = "
			SELECT
				post_title,
				post_name
			FROM 
				{$wpdb->posts} AS posts
			WHERE 
				post_type = 'custom-post-types'
			AND 
				post_status = 'publish'
			ORDER BY 
				post_title";

		$post_types = $wpdb->get_results( $sql );

		$pagination_options = array('none' => __('None', 'kickpress'),'default' => __('Standard', 'kickpress'),'wp' => __('Previous/Next', 'kickpress'),'more' => __('Load More', 'kickpress'));
?>
<p>
	<label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Post Type', 'kickpress' ); ?>:</label> 
	<select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>">
		<option value="">Active Post Type</option>
		<option value="post">Posts</option>
		<option value="page">Pages</option>
		<?php foreach ( $post_types as $post_type ) :
			$selected = $instance['post_type'] == $post_type->post_name ? ' selected="selected"' : '';
		?>
		<option value="<?php echo $post_type->post_name; ?>"<?php echo $selected; ?>><?php echo $post_type->post_title; ?></option>
		<?php endforeach; ?>
	</select>
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'kickpress' ); ?>:</label> 
	<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'show_title' ); ?>">
	<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_title' ); ?>" name="<?php echo $this->get_field_name( 'show_title' ); ?>"<?php echo ( 'on' == $instance['show_title'] ? ' checked="checked"' : '' ); ?> /> <?php _e( 'Show Title', 'kickpress' ); ?></label>
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'excerpt_length' ); ?>"><?php _e( 'Excerpt Length', 'kickpress' ); ?>:</label> 
	<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'excerpt_length' ); ?>" name="<?php echo $this->get_field_name( 'excerpt_length' ); ?>" value="<?php echo $instance['excerpt_length']; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Post ID', 'kickpress' ); ?>:</label> 
	<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'post_id' ); ?>" name="<?php echo $this->get_field_name( 'post_id' ); ?>" value="<?php echo $instance['post_id']; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'view' ); ?>"><?php _e( 'View', 'kickpress' ); ?>:</label> 
	<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'view' ); ?>" name="<?php echo $this->get_field_name( 'view' ); ?>" value="<?php echo $instance['view']; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'terms' ); ?>"><?php _e( 'Categories', 'kickpress' ); ?>:</label> 
	<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'terms' ); ?>" name="<?php echo $this->get_field_name( 'terms' ); ?>" value="<?php echo $instance['terms']; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'posts_per_page' ); ?>"><?php _e( 'Number of posts to show', 'kickpress' ); ?>:</label> 
	<input type="text" size="3" id="<?php echo $this->get_field_id( 'posts_per_page' ); ?>" name="<?php echo $this->get_field_name( 'posts_per_page' ); ?>" value="<?php echo $instance['posts_per_page']; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'top_pagination_type' ); ?>"><?php _e( 'Top Pagination', 'kickpress' ); ?>:</label>
	<select class="widefat" id="<?php echo $this->get_field_id( 'top_pagination_type' ); ?>" name="<?php echo $this->get_field_name( 'top_pagination_type' ); ?>">
		<option value="">Pagination Type</option>
		<?php foreach ( $pagination_options as $option_key => $option_value ) :
			$selected = $instance['bottom_pagination_type'] == $option_key ? ' selected="selected"' : '';
		?>
		<option value="<?php echo $option_key; ?>"<?php echo $selected; ?>><?php echo $option_value; ?></option>
		<?php endforeach; ?>
	</select>
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'bottom_pagination_type' ); ?>"><?php _e( 'Bottom Pagination', 'kickpress' ); ?>:</label><br />
	<select class="widefat" id="<?php echo $this->get_field_id( 'bottom_pagination_type' ); ?>" name="<?php echo $this->get_field_name( 'bottom_pagination_type' ); ?>">
		<option value="">Pagination Type</option>
		<?php foreach ( $pagination_options as $option_key => $option_value ) :
			$selected = $instance['bottom_pagination_type'] == $option_key ? ' selected="selected"' : '';
		?>
		<option value="<?php echo $option_key; ?>"<?php echo $selected; ?>><?php echo $option_value; ?></option>
		<?php endforeach; ?>
	</select>
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>">
	<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id( 'show_thumbnail' ); ?>" name="<?php echo $this->get_field_name( 'show_thumbnail' ); ?>"<?php echo ( 'on' == $instance['show_thumbnail'] ? ' checked="checked"' : '' ); ?> /> <?php _e( 'Show Thumbnail', 'kickpress' ); ?></label>
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'thumb_size' ); ?>"><?php _e( 'Thumb Size', 'kickpress' ); ?>:</label> 
	<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'thumb_size' ); ?>" name="<?php echo $this->get_field_name( 'thumb_size' ); ?>" value="<?php echo $instance['thumb_size']; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'sort_direction' ); ?>"><?php _e( 'Sort Direction', 'kickpress' ); ?>:</label> 
	<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'sort_direction' ); ?>" name="<?php echo $this->get_field_name( 'sort_direction' ); ?>" value="<?php echo $instance['sort_direction']; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'sort_field' ); ?>"><?php _e( 'Sort Field', 'kickpress' ); ?>:</label> 
	<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'sort_field' ); ?>" name="<?php echo $this->get_field_name( 'sort_field' ); ?>" value="<?php echo $instance['sort_field']; ?>" />
</p>
<p>
	<label for="<?php echo $this->get_field_id( 'callback' ); ?>"><?php _e( 'Callback Action', 'kickpress' ); ?>:</label> 
	<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'callback' ); ?>" name="<?php echo $this->get_field_name( 'callback' ); ?>" value="<?php echo $instance['callback']; ?>" />
</p>
<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['show_title'] = strip_tags( $new_instance['show_title'] );
		$instance['post_type'] = strip_tags( $new_instance['post_type'] );
		$instance['view'] = strip_tags( $new_instance['view'] );
		$instance['post_id'] = strip_tags( $new_instance['post_id'] );
		$instance['terms'] = strip_tags( $new_instance['terms'] );
		$instance['posts_per_page'] = strip_tags( $new_instance['posts_per_page'] );
		$instance['excerpt_length'] = strip_tags( $new_instance['excerpt_length'] );
		$instance['top_pagination_type'] = strip_tags( $new_instance['top_pagination_type'] );
		$instance['bottom_pagination_type'] = strip_tags( $new_instance['bottom_pagination_type'] );
		$instance['show_thumbnail'] = strip_tags( $new_instance['show_thumbnail'] );
		$instance['thumb_size'] = strip_tags( $new_instance['thumb_size'] );
		$instance['sort_direction'] = strip_tags( $new_instance['sort_direction'] );
		$instance['sort_field'] = strip_tags( $new_instance['sort_field'] );
		$instance['callback'] = strip_tags( $new_instance['callback'] );

		return $instance;
	}
}