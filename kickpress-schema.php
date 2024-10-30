<?php global $kickpress_post_types, $kickpress_plugin_options; unset ( $kickpress_post_types['any'] ); ?>
<html>
	<head>
		<style type="text/css">
			body { font-family:arial; }
			a, a:visited { color: blue;text-decoration:none; }
			a:hover, a:focus { text-decoration:underline; }
			h1 { text-align:center; }
			h2 { background-color:#dedeee; }
			h3 { padding:0 20px; }
			#plugin-options { border:1px solid #ccc;width:1200px;backbround-color:#dedede;margin:20px auto; }
			#plugin-options h2 { border-bottom:1px solid #ccc;text-align:center;margin:0;padding:10px 0; }
			li span { font-weight:bold; }
			.nav-menu { border:1px solid #999;background-color:#deeeee;width:1200px;height:30px;margin:10px auto; }
			.nav-menu ul { list-style:none;padding:0;margin:0; }
			.nav-menu li { float:left;padding:0 10px;line-height:30px; }
			.post-types { border:1px solid #ccc;width:1200px;backbround-color:#dedede;margin:20px auto }
			.post-types h2 { border-bottom:1px solid #ccc;text-align:center;margin:0;padding:10px 0; }
			table { background-color:#333; margin:10px 20px; }
			th { background-color:#333;color:#fff;margin:1px;padding:5px;text-align:left;vertical-align:bottom;overflow:hidden; }
			td { background-color:#fff;margin:1px;padding:5px;vertical-align:top;overflow:hidden; }
			.post-types table table { margin:0;padding:0;border:none;border-collapse:collapse;white-space: nowrap; }
			.post-types table table td { margin:0;padding:2px;border:none;font-size:0.8em; }
			#plugin-options table table { padding:0;margin:0;width:100%;background-color:#777; }
			#plugin-options table table th { background-color:#777;width:150px; }
		</style>
	</head>
	<body>
		<h1 id="top">Schema</h1>
		<div id="plugin-options">
			<h2>Kickpress Plugin Options</h2>
			<table>
				<tr>
					<th>Option</th><th>Input</th>
				</tr>
				<?php foreach( $kickpress_plugin_options as $option_key => $option_values ) : ?>
				<tr>
					<td><?php echo $option_values[ 'caption' ]; ?></td>
					<td>
						<table>
							<tr><th>ID</th><td><?php echo $option_values[ 'id' ]; ?></td></tr>
							<tr><th>Type</th><td><?php echo isset( $option_values[ 'type' ] ) ? $option_values[ 'type' ] : 'text'; ?></td></tr><?php
								// Options
								if ( ! empty( $option_values[ 'options' ] ) ) {
									echo '<tr><th>Options</th><td>';
									if ( is_array( $option_values[ 'options' ] ) ) {
										$option_array = array();
										foreach ( $option_values[ 'options' ] as $option_name => $option_value ) {
											$option_array[] = "$option_name => $option_value";
										}
										echo implode( '<br />', $option_array );
									} elseif ( isset( $option_values[ 'options' ] ) ) {
										echo $option_values[ 'options' ]; 
									}
									echo '</td></tr>';
								}
	
								// Default
								if ( ! empty( $option_values[ 'default' ] ) ) {
									echo '<tr><th>Default(s)</th><td>';
									if ( is_array( $option_values[ 'default' ] ) ) {
										$default_array = array();
										foreach ( $option_values[ 'default' ] as $default_name => $default_value ) {
											$default_array[] = "$default_name => $default_value";
										}
										echo implode( '<br />', $default_array );
									} elseif ( isset( $option_values[ 'default' ] ) ) {
										echo $option_values[ 'default' ];
									}
									echo '</td></tr>';
								}
								
								// Value
								if ( isset( $option_values[ 'value' ] ) ) {
										echo '<tr><th>Value(s)</th><td>' . $option_values[ 'value' ] . '</td></tr>';
								} elseif ( isset( $option_values[ 'values' ] ) ) {
									echo '<tr><th>Value(s)</th><td>';
									if ( is_array( $option_values[ 'values' ] ) ) {
										$value_array = array();
										foreach ( $option_values[ 'values' ] as $value_name => $value ) {
											$value_array[] = "$value_name => $value";
										}
										echo implode( '<br />', $value_array );
									} else {
										echo $option_values[ 'values' ];
									}
									echo '</td></tr>';
								}
								
							?>
							<?php echo !empty ( $option_values[ 'class' ] ) ? '<tr><th>CSS Class</th><td>' . $option_values[ 'class' ] . '</td></tr>' : ''; ?>
							<?php echo !empty ( $option_values[ 'notes' ] ) ? '<tr><th>Notes</th><td>' . $option_values[ 'notes' ] . '</td></tr>' : ''; ?>
						</table>
					</td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div><!-- #plugin-options -->
		<?php foreach ( $kickpress_post_types as $post_type_name => $post_type_values ) :
			if ( $api = kickpress_init_api( $post_type_name ) ) : ?>
		<div class="nav-menu"><!-- Custom Post Type menu navigation -->
			<ul>
				<?php foreach ( $kickpress_post_types as $menu_name => $menu_value ) : ?>
				<li><a href="#<?php echo $menu_name; ?>"><?php echo isset( $menu_value[ 'post_type_title' ] ) ? $menu_value[ 'post_type_title' ] : kickpress_make_readable( $menu_name ); ?></a></li>
				<?php endforeach; ?>
				<li><a href="#top">Top</a></li>
			</ul>
		</div><!-- .nav-menu -->
		<div class="post-types"><!-- Custom Post Types -->
			<h2 id="<?php echo $post_type_name; ?>"><?php echo isset( $post_type_values[ 'post_type_title' ] ) ? $post_type_values[ 'post_type_title' ] : kickpress_make_readable( $post_type_name ); ?></h2>
			<h3>Table</h3>
			<table>
				<tr>
					<th>Field<br />Name</th><th>Caption</th><th>Required</th><th>Input<br />Type</th><th>Default<br />Value</th><th>Options</th><th>Validate</th><th>Autofill</th>
				</tr>
				<?php foreach ( $api->get_custom_fields() as $field_name => $field_values ) :
					if ( ! empty( $field_values[ 'type' ] ) && in_array( $field_values[ 'type' ], array( 'title', 'tags', 'categories' ) ) ) 
						continue; ?>
				<tr>
					<td><?php echo $field_name; ?></td>
					<td><?php echo $api->get_caption( $field_values, $field_name ); ?></td>
					<td><?php echo isset( $field_values[ 'required' ] ) && kickpress_boolean( $field_values[ 'required' ] ) ? 'true' : ''; ?></td>
					<td><?php echo isset( $field_values[ 'type' ] ) ? $field_values[ 'type' ] : 'text'; ?></td>
					<td><?php echo isset( $field_values[ 'default' ] ) ? $field_values[ 'default' ] : ''; ?></td>
					<td><table><?php 
						if ( isset( $field_values[ 'options' ] ) && is_array( $field_values[ 'options' ] ) ) {
							foreach ( $field_values[ 'options' ] as $option_key => $option_value )
								echo '<tr><td>' . $option_key . '</td><td> => ' . $option_value . '</td></tr>';
						}
					?></table></td>
					<td><?php echo isset( $field_values[ 'validate' ] ) ? $field_values[ 'validate' ] : ''; ?></td>
					<td><?php echo isset( $field_values[ 'autofill' ] ) ? $field_values[ 'autofill' ] : ''; ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
			<h3>Valid Actions</h3>
			<table>
				<tr>
					<th>Label</th><th>Slug</th><th>Method</th><th>Callback</th><th>Capability</th><th>Default<br />Format</th>
				</tr>
				<?php foreach ( $api->get_valid_actions() as $action_name => $action_values ) : ?>
				<tr>
					<td><?php echo $action_values['label']; ?></td>
					<td><?php echo $action_values['slug']; ?></td>
					<td><?php echo $action_values['method']; ?></td>
					<td><?php echo isset ( $action_values['callback'] ) ? $action_values['callback'] : '&nbsp;'; ?></td>
					<td><?php echo $action_values['capability']; ?></td>
					<td><?php echo isset ( $action_values['default_format'] ) ? $action_values['default_format'] : '&nbsp;'; ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
			<h3>Valid Views</h3>
			<table>
				<tr>
					<th>Label</th><th>Slug</th><th>Aliases</th><th>Order</th><th>Single</th><th>Hidden</th></th>
				</tr>
				<?php foreach ( $api->get_valid_views() as $view_name => $view_values ) : ?>
				<tr>
					<td><?php echo $view_values['label']; ?></td>
					<td><?php echo $view_values['slug']; ?></td>
					<td><pre><?php echo isset( $view_values['aliases'] ) && is_array( $view_values['aliases'] ) ? implode( '<br />', $view_values['aliases'] ) : ''; ?></pre></td>
					<td><?php echo $view_values['order']; ?></td>
					<td><?php echo $view_values['single']; ?></td>
					<td><?php echo $view_values['hidden']; ?></td>
				</tr>
				<?php endforeach; ?>
			</table>
		</div><!-- .post_types -->
		<?php endif; endforeach; ?>
	</body>
</html>