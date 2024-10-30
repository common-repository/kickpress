<?php

class kickpress_viewbar_widget extends WP_Widget {
	public static $classname = __CLASS__;
	
	public function __construct() {
		parent::__construct( __CLASS__, 'View Bar', array(
			'description' => 'View Toolbar Widget',
			'classname'   => 'toolbar ' . self::$classname
		) );
	}
	
	public function widget( $args, $params ) {
		$post_type = ! empty( $params['post_type'] ) ? $params['post_type'] : get_post_type();
		
		$api = kickpress_init_api( $post_type );
		
		$views = $api->get_valid_views();
		
		extract( $args );
		
		echo $before_widget;
?>
<ul class="viewbar">
	<?php foreach ( $views as $view ) : if ( ! $view['hidden'] ) :
		$url = kickpress_api_url( array(
			'view' => $view['slug']
		) );
	?>
	<li<?php if ( kickpress_get_view() == $view['slug'] ) echo ' class="active"'; ?>>
		<a href="<?php echo $url; ?>"><?php echo $view['label']; ?></a>
	</li>
	<?php endif; endforeach; ?>
</ul>
<?php
		echo $after_widget;
	}
	
	public function form( $instance ) {
		global $wpdb;
		
		$sql = "SELECT post_title, post_name "
			 . "FROM {$wpdb->posts} AS posts "
			 . "WHERE post_type = 'custom-post-types' "
			 . "AND post_status = 'publish' "
			 . "ORDER BY post_title";
		
		$post_types = $wpdb->get_results( $sql );
?>
<p>
	<label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Post Type:', 'kickpress' ); ?></label> 
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
<?php 
	}
	
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['post_type'] = strip_tags( $new_instance['post_type'] );
		
		return $instance;
	}
}

?>