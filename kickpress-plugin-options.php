<?php
/**
 * This file holds all of the plugin options
 */

$shortcode = 'kickpress_';

$kickpress_plugin_options = array(
	'facebook_app_id'  => array(
		'id'      => $shortcode . 'facebook_app_id',
		'caption' => 'Facebook App ID',
		'default' => '',
		'class'   => 'large-text',
		'notes'   => 'Enter the App ID/API Key for your Facebook app.'
	),
	'facebook_app_secret' => array(
		'id'      => $shortcode . 'facebook_app_secret',
		'caption' => 'Facebook App Secret',
		'default' => '',
		'class'   => 'large-text',
		'notes'   => 'Enter the App Secret for your Facebook app.'
	),
	'twitter_consumer_key'  => array(
		'id'      => $shortcode . 'twitter_consumer_key',
		'caption' => 'Twitter App Consumer Key',
		'default' => '',
		'class'   => 'large-text',
		'notes'   => 'Enter the Consumer Key for your Twitter app.'
	),
	'twitter_consumer_secret' => array(
		'id'      => $shortcode . 'twitter_consumer_secret',
		'caption' => 'Twitter App Consumer Secret',
		'default' => '',
		'class'   => 'large-text',
		'notes'   => 'Enter the Consumer Secret for your Twitter app.'
	),
	'api_trigger'       => array(
		'id'      => $shortcode . 'api_trigger',
		'caption' => 'API Trigger',
		'default' => 'api',
		'notes'   => 'The "trigger" in query string that tells KickPress that this is an API call, i.e. "/api/delete/".'
	),
	'use_post_type_cap' => array(
		'id'      => $shortcode . 'use_post_type_cap',
		'type'    => 'checkbox',
		'caption' => 'Turn on post type capabilities',
		'label'   => 'Post Type Capabilities',
		'default' => 'enable',
		'notes'   => 'KickPress allows you to edit the post type capabilities.'
	),
	'future_comments' => array(
		'id'      => $shortcode . 'future_comments',
		'type'    => 'checkbox',
		'caption' => 'Enable comments on schedule posts.',
		'label'   => 'Comments on Scheduled Posts',
		'default' => 'disable',
		'notes'   => 'KickPress allows users to comment on scheduled posts.'
	)
);

foreach ( $kickpress_plugin_options as $key => $value ) {
	$option_value = null;

	if ( isset( $value['id'] ) ) {
		if ( ! $option_value = get_option( $value['id'] ) ) {
			if ( isset( $value['default'] ) )
				$option_value = $value['default'];
		}

		// Set the value
		$kickpress_plugin_options[$key]['value'] = $option_value;
	}
}

function kickpress_plugin_menu() {
	global $kickpress_plugin_options, $shortcode, $kickpress_post_types;

	if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) )
		return;
		//die(__('Cheatin&#8217; uh?'));

	if ( ! empty( $_REQUEST['page'] ) && esc_attr( $_REQUEST['page'] ) == basename( __FILE__ ) ) {
		$postback_url = '/wp-admin/admin.php?page=' . esc_attr( $_REQUEST['page'] )
		              . '&on_' . esc_attr( $_REQUEST['action'] ) . '=true';

		if ( ! empty( $_REQUEST['action'] ) && 'save' == esc_attr( $_REQUEST['action'] ) ) {
			foreach ( $kickpress_plugin_options as $key => $value ) {
				if ( ! empty( $value['type'] ) && 'checkbox' == $value['type'] ) {
					if ( isset( $_REQUEST[$value['id']] ) )
						update_option( $value['id'], 'enable' );
					else
						update_option( $value['id'], 'disable' );
				} else {
					if ( ! empty( $value['id'] ) && ! empty( $_REQUEST[$value['id']] ) )
						update_option( $value['id'], esc_attr( $_REQUEST[$value['id']] ) );
					elseif ( isset( $value['id'] ) )
						delete_option( $value['id'] );
				}
			}

			$files = kickpress_get_module_files();

			foreach ( $files as $file => $path ) {
				$option = $shortcode . $file . '_module';

				$value = isset( $_REQUEST[$option] ) ? 'enable' : 'disable';

				update_option( $option, $value );

				if ( isset( $_REQUEST[$option] ) ) {
					if ( ! isset( $kickpress_post_types[$file] ) ) {
						$posts = get_posts( array(
							'post_type'   => 'custom-post-types',
							'name'        => $file,
							'post_status' => 'any'
						) );

						// echo '<pre>' . print_r( $posts, true ) . '</pre>';

						if ( empty( $posts ) ) {
							$post_data = array(
								'post_title'  => ucwords( str_replace( array( '_', '-' ), ' ', strtolower( $file ) ) ),
								'post_name'   => $file,
								'post_type'   => 'custom-post-types',
								'post_status' => 'publish'
							);

							wp_insert_post( $post_data );
						} elseif ( $post = array_shift( $posts ) ) {
							$post_data = (array) $post;
							$post_data['post_status'] = 'publish';

							wp_update_post( $post_data );
						}
					}
				} else {
					$posts = get_posts( array(
						'post_type'   => 'custom-post-types',
						'name'        => $file,
						'post_status' => 'any'
					) );

					if ( $post = array_shift( $posts ) ) {
						$post_data = (array) $post;
						$post_data['post_status'] = 'pending';

						wp_update_post( $post_data );
					}
				}
			}

			header( 'Location: ' . $postback_url );
			die;
		} elseif ( 'reset' == esc_attr( $_REQUEST['action'] ) ) {
			foreach ( $kickpress_plugin_options as $key => $value ) {
				delete_option( $value['id'] );
			}

			header( 'Location: ' . $postback_url);
			die;
		}
	}

	add_menu_page( 'KickPress', 'KickPress', 'manage_options', basename(__FILE__), 'kickpress_options_form' );
}

function kickpress_get_module_files() {
	global $kickpress_module_files;

	if ( empty( $kickpress_module_files ) ) {
		$paths = array(
			WP_PLUGIN_DIR . '/kickpress/modules/',
			TEMPLATEPATH . '/modules/',
			STYLESHEETPATH . '/modules/'
		);

		$builtin = array( 'post', 'custom-post-types' );

		foreach ( $paths as $path ) {
			if ( $dir = opendir( $path ) ) {
				while ( ( $file = readdir( $dir ) ) !== false ) {
					if ( preg_match( '/class-(.*)\.php/', $file, $match ) ) {
						if ( ! in_array( $match[1], $builtin ) ) {
							$kickpress_module_files[$match[1]] = $path . 'kickpress_' . str_replace( '-', '_', $match[1] );
						}
					}
				}

				closedir( $dir );
			}
		}

		ksort( $kickpress_module_files );
	}

	return $kickpress_module_files;
}

function kickpress_options_form() {
	global $kickpress_plugin_options, $kickpress_post_types, $shortcode;

	/* Add modules to options array */

	$files = kickpress_get_module_files();

	if ( ! empty( $files ) ) {
		$kickpress_plugin_options['modules_list'] = array(
			'type'    => 'title',
			'caption' => 'Custom Modules',
			'notes'   => 'Select the custom modules below to be loaded.'
		);

		foreach ( $files as $file => $path ) {
			$kickpress_plugin_options[$file . '_module'] = array(
				'id'      => $shortcode . $file . '_module',
				'type'    => 'checkbox',
				'caption' => ucwords( str_replace( array( '_', '-' ), ' ', strtolower( $file ) ) ),
				'default' => 'disable',
				'value'   => isset( $kickpress_post_types[$file] ) ? 'enable' : 'disable'
			);
		}
	}

	$current_page = esc_attr( $_GET['page'] );
	$elements = new kickpress_form_elements();
	$form_options = '<table class="form-table">';

	if ( count( $kickpress_plugin_options ) ) {
		foreach ( $kickpress_plugin_options as $key => $value ) {
			if ( ! isset( $value['type'] ) )
				$value['type'] = 'text';

			$form_element = kickpress_get_form_element( $value['type'] );

			$value['module'] = $current_page;
			if ( isset( $value['id'] ) && ! isset( $value['name'] ) )
				$value['name'] = $value['id'];

			$form_options .= $form_element->element( $value );
			//$form_options .= call_user_func( array( $elements, $value['type'] ), $value );

		}
	}

	$form_options .= '</table>';

?>
<div class="wrap">
	<div class="icon32" id="icon-themes"><br /></div>
	<h2>KickPress Plugin Options</h2>
	<?php if ( esc_attr( $_REQUEST['on_save'] ) ) : ?>
	<div id="message" class="updated fade"><p><strong>Plugin settings saved.</strong></p></div>
	<?php endif; ?>
	<form method="post">
		<input type="hidden" name="action" value="save">
		<input type="hidden" name="page" value="<?php echo $current_page; ?>" id="current_module">
		<?php echo $form_options; ?>
		<p class="submit"><input type="submit" name="Submit" value="Save Changes" class="button-primary"></p>
	</form>
</div>
<?php
	unset( $elements );
}

?>