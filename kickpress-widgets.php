<?php

function kickpress_widgets_register() {
	$paths = array(
		WP_PLUGIN_DIR . '/kickpress/widgets/',
		TEMPLATEPATH . '/widgets/',
		STYLESHEETPATH . '/widgets/'
	);

	foreach ( $paths as $path ) {
		if ( is_dir( $path ) && $dir = opendir( $path ) ) {
			while ( ( $file = readdir( $dir ) ) !== false ) {
				if ( preg_match( '/class-(.*)\.php/', $file, $match ) ) {
					$class = sprintf( 'kickpress_%s_widget', str_replace( '-', '_', $match[1] ) );
					include_once $path . $file;
					register_widget( $class );
				}
			}

			closedir( $dir );
		}
	}

	global $kickpress_post_types, $kickpress_builtin_post_types;

	// Why is this in Widgets?
	if ( $kickpress_post_types ) {
		foreach ( $kickpress_post_types as $key=>$value ) {
			$post_type = (string) $value['post_type'];
			if ( ! isset($post_type) || in_array($post_type, $kickpress_builtin_post_types) )
				continue;

			register_sidebar( array(
				'id'            => $value['post_type'] . '-toolbar',
				'name'          => $value['post_type_title'] . ' Cards',
				'before_widget' => apply_filters( 'kickpress_before_widget', '<aside id="%1$s" class="widget %2$s">' ),
				'after_widget'  => apply_filters( 'kickpress_after_widget', '</aside>' ),
				'before_title'  => apply_filters( 'kickpress_before_title', '<h3 class="widget-title">' ),
				'after_title'   => apply_filters( 'kickpress_after_title', '</h3>' )
			) );
		}
	}
}

?>