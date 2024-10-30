<?php

class kickpress_alphabar_widget extends WP_Widget {
	public static $classname = __CLASS__;

	public function __construct() {
		parent::__construct( __CLASS__, 'Alpha Bar', array(
			'description' => 'Alpha Toolbar Widget',
			'classname'   => 'toolbar ' . self::$classname
		) );
	}

	public function widget( $args, $instance ) {
		$post_type = empty( $instance['post_type'] ) ? get_post_type() : $instance['post_type'];
		$letters   = array();
		$current   = '';

		if ( $api = kickpress_init_api( $post_type ) ) {
			global $wpdb;

			$current = $api->params['first_letter'];

			$field_name = empty( $api->params['alphabar'] ) ? 'post_title' : $api->params['alphabar'];

			if ( in_array( $field_name, array( 'post_title', 'post_content' ) ) ) {
				$alpha_field = "posts.{$field_name}";

				$meta_table = '';
			} else {
				$alpha_field = "postmeta.meta_value";

				$custom_fields = $api->get_custom_fields();
				$field_name = $custom_fields[$field_name]['name'];

				$meta_table = "LEFT JOIN {$wpdb->postmeta} AS postmeta "
							. "ON posts.ID = postmeta.post_id "
							. "AND postmeta.meta_key = '{$field_name}'";
			}

			$sql = "SELECT SUBSTRING(UPPER({$alpha_field}), 1, 1) AS first_letter, "
				 . "count(posts.ID) AS letter_total "
				 . "FROM {$wpdb->posts} AS posts {$meta_table} "
				 . "WHERE posts.post_status = 'publish' "
				 . "AND posts.post_type = '{$post_type}' ";

			if ( isset( $api->params['term'] ) && is_array( $api->params['term'] ) ) {
				$filter = array();

				foreach ( $api->params['term'] as $taxonomy => $term_data ) {
					if ( is_string( $term_data ) )
						$term_data = array( 'in' => $term_data );

					foreach ( $term_data as $term_operator => $term_string ) {
						$operator = strtoupper( str_replace( '-', ' ', $term_operator ) );

						$terms = explode( ',', $term_string );

						$term_taxonomy = array();

						foreach ( $terms as $term ) {
							if ( $term_meta = term_exists( $term, $taxonomy ) )
								$term_taxonomy[] = intval( $term_meta['term_taxonomy_id'] );
						}

						if ( ! empty( $term_taxonomy ) ) {
							$term_taxonomy = implode( ', ', $term_taxonomy );
							$subsql = "SELECT object_id FROM {$wpdb->term_relationships} "
								. "WHERE term_taxonomy_id IN ( $term_taxonomy )";

							$filter[] = "posts.ID $operator ( $subsql )";
						}
					}
				}

				if ( ! empty( $filter ) ) {
					$filter = implode( " AND ", $filter );
					$sql .= "AND {$filter} ";
				}
			}

			$sql .= "GROUP BY first_letter "
				 . "ORDER BY first_letter";

			$results = $wpdb->get_results( $sql );

			foreach ( $results as $result ) {
				$letters[$result->first_letter] = $result->letter_total;
			}

			$numeric = false;

			for ( $i = 0; $i < 10; $i++ ) {
				if ( $letters[$i] > 0 ) {
					$numeric = true;
					break;
				}
			}
		}

		extract( $args );

		echo $before_widget;
?>
<ul class="alphabar">
	<li<?php echo empty( $current ) ? ' class="active"' : ''; ?>>
		<a href="<?php echo kickpress_api_url( array( 'first_letter' => '' ) ); ?>">All</a>
	</li>
	<li<?php echo '0-9' == $current ? ' class="active"' : ''; ?>>
		<?php if ( $numeric ) :
			$url = kickpress_api_url( array( 'first_letter' => '0-9' ) );
		?>
		<a href="/<?php echo $post_type; ?>/api/first_letter/0-9/">0-9</a>
		<?php else : ?>
		<span>0-9</span>
		<?php endif; ?>
	</li>
	<?php for ( $i = 0; $i < 26; $i++ ) : $char = chr( $i + 65 ); ?>
	<li<?php echo $char == $current ? ' class="active"' : ''; ?>>
		<?php if ( $letters[$char] > 0 ) :
			$url = kickpress_api_url( array( 'first_letter' => $char ) );
		?>
		<a href="<?php echo $url; ?>"><?php echo $char; ?></a>
		<?php else : ?>
		<span><?php echo $char; ?></span>
		<?php endif; ?>
	</li>
	<?php endfor; ?>
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