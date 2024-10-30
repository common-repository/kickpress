<?php

add_action( 'admin_menu', 'kickpress_app_menu' );

function kickpress_init_app_roles() {
	global $wpdb;

	$wpdb->oauth_consumers = $wpdb->base_prefix . 'oauth_consumers';
	$wpdb->oauth_tokens    = $wpdb->base_prefix . 'oauth_tokens';

	if ( is_null( get_role( 'app' ) ) ) {
		add_role( 'app', 'Remote App' );
		get_role( 'app' )->add_cap( 'read' );
	}
}

function kickpress_get_app( $key = null ) {
	global $wpdb;

	if ( empty( $key ) && kickpress_is_remote_app() )
		$key = REMOTE_APP_TOKEN;

	$query = "SELECT * FROM $wpdb->oauth_consumers WHERE blog_id = %d AND consumer_key = %s";
	$query = $wpdb->prepare( $query, get_current_blog_id(), $key );

	return $wpdb->get_row( $query );
}

function kickpress_add_app( $args = array() ) {
	if ( ! is_user_logged_in() ) return false;

	$defaults = array(
		'title'        => '',
		'description'  => '',
		'url'          => null,
		'callback_url' => null
	);

	extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

	if ( empty( $url ) ) return false;

	$host = preg_replace( '/^www\./i', '', parse_url( $url, PHP_URL_HOST ) );

	if ( ! empty( $callback_url ) ) {
		$callback_host = preg_replace( '/^www\./i', '',
			parse_url( $callback_url, PHP_URL_HOST ) );

		if ( $callback_host != $host )
			$callback_url = null;
	}

	$key    = kickpress_app_hash( 'md5' );
	$secret = kickpress_app_hash( 'sha1' );

	while ( $app = kickpress_get_app( $key ) )
		$key = kickpress_app_hash( 'md5' );

	$app = array(
		'blog_id'         => get_current_blog_id(),
		'user_id'         => get_current_user_id(),

		'consumer_key'    => $key,
		'consumer_secret' => $secret,
		'callback_url'    => esc_url( $callback_url ),

		'app_title'       => $title,
		'app_description' => $description,
		'app_url'         => esc_url( $url ),
		'app_status'      => 'enable'
	);

	global $wpdb;
	return $wpdb->insert( $wpdb->oauth_consumers, $app );
}

function kickpress_update_app( $args = array() ) {
	if ( ! is_user_logged_in() ) return false;

	extract( $args, EXTR_SKIP );

	if ( empty( $consumer_key ) ) {
		return kickpress_add_app( $args );
	}

	$app = kickpress_get_app( $consumer_key );

	if ( ! $app ) return false;

	if ( ! empty( $title ) ) {
		$app->app_title = $title;
	}

	if ( ! empty( $description ) ) {
		$app->app_description = $description;
	}

	if ( ! empty( $url ) ) {
		$app->app_url = $url;
	}

	if ( ! empty( $callback_url ) ) {
		$host = preg_replace( '/^www\./i', '',
			parse_url( $app->app_url, PHP_URL_HOST ) );

		$callback_host = preg_replace( '/^www\./i', '',
			parse_url( $callback_url, PHP_URL_HOST ) );

		if ( $host == $callback_host ) {
			$app->callback_url = $callback_url;
		}
	}

	global $wpdb;
	return $wpdb->replace( $wpdb->oauth_consumers, get_object_vars( $app ) );
}

function kickpress_delete_app( $key ) {
	global $wpdb;
	return $wpdb->delete( $wpdb->oauth_consumers, array( 'consumer_key' => $key ) );
}

function kickpress_app_raw_key( $hex ) {
	$raw = '';

	foreach ( str_split( $hex, 2 ) as $byte ) {
		$raw .= chr( intval( hexdec( $byte ) ) );
	}

	return $raw;
}

function kickpress_app_hex_key( $raw ) {
	$hex = '';

	foreach ( str_split( $raw, 1 ) as $byte ) {
		$hex .= sprintf( '%02x', ord( $byte ) );
	}

	return $hex;
}

function kickpress_app_hash( $algo = 'sha1', $data = null ) {
	if ( empty( $data ) )
		$data = uniqid( rand(), true );

	switch ( strtolower( $algo ) ) {
		case 'md5':
			return md5( $data );
		case 'sha1':
			return sha1( $data );
		case 'crc32':
			return sprintf( '%08x', crc32( $data ) & 0xffffffff );
		default:
			return $data;
	}
}

function kickpress_app_menu() {
	add_menu_page( 'KickPress Remote Applications', 'KickPress Apps',
		'manage_options', 'apps', 'kickpress_app_menu_page' );
}

function kickpress_app_menu_page() {
	global $wpdb;

	$nonce = @$_REQUEST['_wpnonce'];
	$url   = @$_REQUEST['_wp_http_referer'];

	if ( wp_verify_nonce( $nonce, 'single-application' ) ) {
		switch ( @$_REQUEST['action'] ) {
			case 'save':
				kickpress_update_app( $_REQUEST['app'] );
				break;
			case 'delete':
				kickpress_update_app( $_REQUEST['key'] );
				break;
		}

		if ( ! empty( $url ) ) {
			echo '<script type="text/javascript">'
			   . 'document.location = "' . $url . '";'
			   . '</script>';

			exit;
		}
	} elseif ( wp_verify_nonce( $nonce, 'oauth-token' ) ) {
		$user_id = get_current_user_id();

		$app = kickpress_get_app( $_REQUEST['key'] );

		switch ( @$_REQUEST['action'] ) {
			case 'authorize':
				$token  = md5(  uniqid( rand(), true ) );
				$secret = sha1( uniqid( rand(), true ) );

				$wpdb->insert( $wpdb->oauth_tokens, array(
					'consumer_key' => $app->consumer_key,
					'token'        => $token,
					'secret'       => $secret,
					'user_id'      => intval( $user_id ),
					'type'         => 'access',
					'date'         => date( 'Y-m-d H:i:s' )
				) );

				break;
			case 'deauthorize':
				$wpdb->delete( $wpdb->oauth_tokens, array(
					'consumer_key' => $app->consumer_key,
					'user_id'      => intval( $user_id )
				) );

				break;
		}

		if ( ! empty( $url ) ) {
			echo '<script type="text/javascript">'
			   . 'document.location = "' . $url . '";'
			   . '</script>';

			exit;
		}
	}

	$table = new kickpress_app_table();

	if ( 'item' == $_REQUEST['view'] ) {
		$app = kickpress_get_app( $_REQUEST['key'] );

		$user_id = get_current_user_id();
		$blog_id = get_current_blog_id();

		$user = get_userdata( $user_id );

		$user_name = ! empty( $user->display_name )
			? $user->display_name : $user->user_login;

		$query = "SELECT * FROM $wpdb->oauth_tokens "
			. "WHERE consumer_key = %s AND user_id = %d";

		$oauth = $wpdb->get_row( $wpdb->prepare( $query, $app->consumer_key, $user_id ) );

		if ( ! $oauth ) {
			$oauth = (object) array(
				'user_id' => get_current_user_id(),
				'blog_id' => get_current_blog_id(),
				'token'   => '',
				'secret'  => ''
			);
		}
?>
<div class="wrap">
	<div id="icon-plugins" class="icon32"><br></div>
	<h2>KickPress Remote Applications</h2>
	<div id="ajax-response"></div>
	<div id="col-container">
		<div id="col-right">
			<div class="col-wrap">
				<div class="form-wrap">
					<h3>Edit Application</h3>
					<form action="admin.php?page=apps" method="post">
						<input type="hidden" name="action" value="save">
						<input type="hidden" name="app[consumer_key]" value="<?php esc_attr_e( $app->consumer_key ); ?>">
						<?php wp_nonce_field( 'single-application' ); ?>
						<div class="form-field form-required">
							<label for="app-title">Title</label>
							<input id="app-title" type="text" size="40" name="app[title]"
									value="<?php esc_attr_e( $app->app_title ); ?>">
							<p>The name of your app</p>
						</div>
						<div class="form-field">
							<label for="app-descrip">Description</label>
							<textarea id="app-descrip" rows="8" name="app[description]"><?php echo $app->app_description; ?></textarea>
							<p>What does your app do? Why should readers try it?</p>
						</div>
						<div class="form-field form-required">
							<label for="app-url">Url</label>
							<input id="app-url" type="text" size="40" name="app[url]"
									value="<?php esc_attr_e( $app->app_url ); ?>">
							<p>The unique domain/host name for your app<br>ex: http://app.domain.com/</p>
						</div>
						<div class="form-field">
							<label for="app-callback-url">Callback URL</label>
							<input id="app-callback-url" type="text" size="40" name="app[callback_url]"
									value="<?php esc_attr_e( $app->callback_url ); ?>">
							<p>Where should we redirect after authenticating your app? This URL must have the same hostname as the website URL.</p>
						</div>
						<p class="submit">
							<input type="submit" value="Save Application" class="button">
						</p>
					</form>
				</div>
			</div>
		</div>
		<div id="col-left">
			<div class="col-wrap">
				<div class="form-wrap">
					<h3>API Keys</h3>
					<form>
						<div class="form-field">
							<label>Application Key</label>
							<input type="text" value="<?php esc_attr_e( $app->consumer_key ); ?>" disabled="disabled">
						</div>
						<div class="form-field">
							<label>Application Secret</label>
							<input type="text" value="<?php esc_attr_e( $app->consumer_secret ); ?>" disabled="disabled">
						</div>
					</form>
					<h3>OAuth Token for <?php echo $user_name; ?></h3>
					<form action="admin.php?page=apps" method="post">
						<?php if ( empty( $oauth->token ) ) : ?>
						<input type="hidden" name="action" value="authorize">
						<?php else : ?>
						<input type="hidden" name="action" value="deauthorize">
						<?php endif; ?>
						<input type="hidden" name="key" value="<?php esc_attr_e( $app->consumer_key ); ?>">
						<?php wp_nonce_field( 'oauth-token' ); ?>
						<div class="form-field">
							<label>OAuth Token</label>
							<input type="text" value="<?php esc_attr_e( $oauth->token ); ?>" disabled="disabled">
						</div>
						<div class="form-field">
							<label>OAuth Secret</label>
							<input type="text" value="<?php esc_attr_e( $oauth->secret ); ?>" disabled="disabled">
						</div>
						<p class="submit">
							<?php if ( empty( $oauth->token ) ) : ?>
							<input type="submit" value="Create OAuth Token" class="button">
							<?php else : ?>
							<input type="submit" value="Remove OAuth Token" class="button">
							<?php endif; ?>
						</p>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
	} else {
?>
<div class="wrap">
	<div id="icon-plugins" class="icon32"><br></div>
	<h2>KickPress Remote Applications</h2>
	<div id="ajax-response"></div>
	<div id="col-container">
		<div id="col-right">
			<div class="col-wrap">
				<div class="form-wrap">
					<form action="admin.php?page=apps" method="post">
						<?php $table->display(); ?>
					</form>
				</div>
			</div>
		</div>
		<div id="col-left">
			<div class="col-wrap">
				<div class="form-wrap">
					<h3>Add New Application</h3>
					<form action="admin.php?page=apps" method="post">
						<input type="hidden" name="action" value="save">
						<?php wp_nonce_field( 'single-application' ); ?>
						<div class="form-field form-required">
							<label for="app-title">Title</label>
							<input id="app-title" type="text" size="40" name="app[title]">
							<p>The name of your app</p>
						</div>
						<div class="form-field">
							<label for="app-descrip">Description</label>
							<textarea id="app-descrip" rows="8" name="app[description]"></textarea>
							<p>What does your app do? Why should readers try it?</p>
						</div>
						<div class="form-field form-required">
							<label for="app-url">Url</label>
							<input id="app-url" type="text" size="40" name="app[url]">
							<p>The unique domain/host name for your app<br>ex: http://app.domain.com/</p>
						</div>
						<div class="form-field form-required">
							<label for="app-callback-url">Callback Url</label>
							<input id="app-callback-url" type="text" size="40" name="app[callback_url]">
							<p>Where should we redirect after authenticating your app? This URL must have the same hostname as the website URL.</p>
						</div>
						<p class="submit">
							<input type="submit" value="Add New Application" class="button">
						</p>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
	}
}

class kickpress_app_table extends WP_List_Table {
	var $_menu_slug = 'apps';

	function __construct() {
		parent::__construct( array(
			'singular' => 'application',
			'plural'   => 'applications'
		) );
	}

	function display() {
		$this->process_bulk_action();
		$this->prepare_items();

		parent::display();
	}

	function prepare_items() {
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, array(), $sortable );

		$this->items = $this->get_items();

		$per_page = 20;

		$total_items = count( $this->items );
		$total_pages = ceil( $total_items / $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'total_pages' => $total_pages,
			'per_page'    => $per_page
		) );
	}

	function get_items() {
		global $wpdb;

		$query = "SELECT * FROM $wpdb->oauth_consumers WHERE blog_id = %d";
		$query = $wpdb->prepare( $query, get_current_blog_id() );

		return $wpdb->get_results( $query );
	}

	function get_columns() {
		return array(
			'cb'   => '<input type="checkbox">',
			'name' => 'Name',
			'url'  => 'URL',
			'user' => 'User'
		);
	}

	function get_sortable_columns() {
		return array(
			array( 'name', true ),
			array( 'url', true ),
			array( 'user', true )
		);
	}

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="app[%s]" value="%s">',
			$item->app_id,
			esc_attr( $item->app_key )
		);
	}

	function column_name( $item ) {
		$refurl = sprintf( 'admin.php?&page=%s', $this->_menu_slug );

		$itemurl = $refurl . '&view=item&key=' . $item->consumer_key;

		$actionurl = $refurl . '&action=delete&key=' . $item->consumer_key;

		$actions = array(
			'delete' => sprintf( '<a href="%s&_wp_http_referer=%s">Delete</a>',
				wp_nonce_url( $actionurl, 'single-application' ),
				urlencode( $refurl )
			)
		);

		return sprintf( '<a href="%s">%s</a> %s',
			esc_attr( $itemurl ),
			$item->app_title,
			$this->row_actions( $actions )
		);
	}

	function column_url( $item ) {
		return $item->app_url;
	}

	function column_user( $item ) {
		$user = get_userdata( $item->user_id );

		return $user->display_name;
	}

	function get_bulk_actions() {
		return array(
			'delete' => 'Delete'
		);
	}

	function process_bulk_action() {
		$nonce = @$_REQUEST['_wpnonce'];
		$url   = @$_REQUEST['_wp_http_referer'];

		if ( '' != $this->current_action() && wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) ) {
			switch ( $this->current_action() ) {
				case 'delete':
					foreach ( $_REQUEST['app'] as $app_id => $app_key ) {
						kickpress_delete_app( $app_key );
					}

					break;
			}

			if ( ! empty( $url ) ) {
				echo '<script type="text/javascript">'
				   . 'document.location = "' . $url . '";'
				   . '</script>';

				exit;
			}
		}
	}
}
