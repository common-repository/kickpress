<?php

class kickpress_termsbar_widget extends WP_Widget {
	public static $classname = __CLASS__;
	
	public function __construct() {
		parent::__construct( __CLASS__, 'Taxonomy Bar', array(
			'description' => 'Custom Taxonomy Widget',
			'classname'   => 'toolbar ' . self::$classname
		) );
	}

	public function widget( $args, $params ) {
		extract( $args );
		
		$taxonomy = get_taxonomy( $params['taxonomy'] );
		$top_level_only = (isset($params['top_level_only']) ? $params['top_level_only'] : false);

		$term_args = array(
			'hierarchical' => $taxonomy->hierarchical,
			'hide_empty'   => $params['hide_empty']
		);
		
		$terms = get_terms( $params['taxonomy'], $term_args );
		
		if ( $taxonomy->hierarchical ) {
			$nodes = array();
			$trees = array();
			
			foreach ( $terms as $term ) {
				$nodes[$term->term_id] = $term;
				$nodes[$term->term_id]->children = array();
			} 
			
			foreach ( $nodes as &$node ) {
				if ( 0 == $node->parent ) $trees[$node->term_id] =& $node;
				else if (! $top_level_only) $nodes[$node->parent]->children[$node->term_id] =& $node;
			}
			
			$terms = $trees;
		}
		
		echo $before_widget;

		$terms_array = array();
		$terms_array["term"] = array();

		$url = kickpress_api_url( $terms_array );
		$api = kickpress_init_api();
		if(isset($api->params['term'])) $active = "";
		else $active = " active ";
		
?>
		<ul class="termsbar nav nav-tabs" role="tablist">
			<li class="<?php echo $active ?>">
				<a href="<?php echo $url; ?>" class="<?php echo $active ?>">All</a>
			</li>	
			<?php $this->_display_nodes( $terms, $params['taxonomy'] ); ?>
		</ul>
<?php
		echo $after_widget;
	}

	private function _display_nodes( $nodes = array(), $taxonomy ) {
		if ( ! empty( $nodes ) ) {
			if ( $api = kickpress_init_api() ) {
				$terms = explode( ',', $api->params['term'][$taxonomy]['in'] );
			} else {
				$terms = array();
			}

			foreach ( $nodes as $node ) {
				$url = kickpress_api_url( array(
					'term' => array(
						$taxonomy => array( $node->slug )
					)
				) );


//TODO: Have child nodes leave a trail of "active" classes up to the top-level node
if ( in_array( $node->slug, $terms ) ) $active = " active ";
else $active = "";
?>


<li class="<?php echo $active ?>">
	<a href="<?php echo $url ?>" class="<?php echo $active ?>"><?php echo $node->name; ?></a>
	<?php if ( ! empty( $node->children ) ) : ?>
	<ul>
		<?php $this->_display_nodes( $node->children, $taxonomy ); ?>
	</ul>
	<?php endif; ?>
</li>
<?php
			}
		}
	}

	public function form( $instance ) {
		$taxonomies = get_taxonomies( null, 'objects' );
?>
<p>
	<label for="<?php echo $this->get_field_id( 'taxonomy' ); ?>"><?php _e( 'Taxonomy:', 'kickpress' ); ?></label> 
	<select id="<?php echo $this->get_field_id( 'taxonomy' ); ?>" name="<?php echo $this->get_field_name( 'taxonomy' ); ?>">
		<?php foreach ( $taxonomies as $taxonomy ) : $selected = $instance['taxonomy'] == $taxonomy->name ? ' selected="selected"' : ''; ?>
		<option value="<?php echo $taxonomy->name; ?>"<?php echo $selected; ?>><?php echo $taxonomy->label; ?></option>
		<?php endforeach; ?>
	</select>
</p>
<p>
	<input id="<?php echo $this->get_field_id( 'hide_empty' ); ?>" type="checkbox"
			name="<?php echo $this->get_field_name( 'hide_empty' ); ?>"
			<?php if ( $instance['hide_empty'] ) echo ' checked="checked"'; ?> />
	<label for="<?php echo $this->get_field_id( 'hide_empty' ); ?>">Hide terms with no posts</label>
</p>
<p>
	<input id="<?php echo $this->get_field_id( 'top_level_only' ); ?>" type="checkbox"
			name="<?php echo $this->get_field_name( 'top_level_only' ); ?>"
			<?php if ( $instance['top_level_only'] ) echo ' checked="checked"'; ?> />
	<label for="<?php echo $this->get_field_id( 'top_level_only' ); ?>">Show only top level</label>
</p>
<?php 
	}

	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['taxonomy']   = strip_tags( $new_instance['taxonomy'] );
		$instance['hide_empty'] = isset( $new_instance['hide_empty'] );
		$instance['top_level_only'] = isset( $new_instance['top_level_only'] );
		
		return $instance;
	}
}

?>