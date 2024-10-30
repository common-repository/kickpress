<?php
/** 
 * This file holds the API authentication calls
 */

function kickpress_authentication() {
	global $wpdb;
	
	if ( isset( $_POST['sig'] ) && isset( $_POST['iv'] ) ) {
		$token = kickpress_app_hex_key( base64_decode( $_POST['tok'] ) );
		
		if ( $app = kickpress_get_app( $token ) ) {
			$key = kickpress_app_raw_key( $app['app_secret'] );
			
			$sig = base64_decode( $_POST['sig'] );
			$iv  = base64_decode( $_POST['iv'] );
			
			$msg = mcrypt_decrypt( MCRYPT_RIJNDAEL_128, $key, $sig, MCRYPT_MODE_CFB, $iv );
			
			list( $username, $password, $timestamp ) = explode( ':', $msg );
			
			if ( time() - intval( $timestamp ) < 30 ) {
				define( 'REMOTE_APP_TOKEN', $app['app_token'] );
				
				$user = wp_authenticate( $username, $password );
				
				if ( ! is_null( $user ) && ! is_wp_error( $user ) )
					wp_set_current_user( $user->ID );
				
				return true;
			}
		}
		
		return false;
	}
	
	return is_user_logged_in();
}

// add_action('show_user_profile', 'kickpress_profile_api_fields');

/* function kickpress_authentication( ) {
	global $wpdb;
	$local_timestamp = time();
	$method = strtoupper( $_SERVER['REQUEST_METHOD'] );
	$host = strtolower( $_SERVER['HTTP_HOST'] );

	if ( kickpress_is_ajax() ) {
		define('JSON_RESPONSE', true);
		return true;
	}
	
	if ( isset( $_GET['s'] ) && isset( $_GET['t'] ) ) {
		global $wpdb;
		
		extract( $_GET );
		
		$query = "SELECT MD5(user_pass) FROM $wpdb->users WHERE MD5(user_login) LIKE '$t%'";
		
		$key = substr( $wpdb->get_var( $query ), 0, 16 );
		$raw = mcrypt_decrypt( MCRYPT_RIJNDAEL_128, $key, base64_decode( $s ), MCRYPT_MODE_CFB, $t );
		
		if ( preg_match( '/^([0-9a-z]+):([0-9]+):(.+):(.+)$/Ui', $raw, $matches ) ) {
			list( $raw, $salt, $time, $user, $pass ) = $matches;
			
			$user = wp_authenticate( $user, $pass );
			
			if ( ! is_wp_error( $user ) )
				wp_set_current_user( $user->ID );
		} else {
			// Request failed to validate
			exit;
		}
	}

	// Create an array of authentication parameters to create our signature match
	foreach( array('token', 'signature', 'timestamp') as $api_call_param ) {
		// Return true if this is a local api call and does not require authentication
		if ( empty($_GET[ $api_call_param ]) ) {
			return true;
		}
		$$api_call_param = str_replace(array(' '), array('+'), $_GET[$api_call_param]);
	}

	define('JSON_RESPONSE', true);
	define('REMOTE_API_REQUEST', true);

	// Validate the signature		
	if ( ! ctype_digit( $timestamp ) || 10 < strlen( $timestamp ) ) {
		return false;
	}

	// Make sure that this is somewhat current
	if ( $timestamp < ($local_timestamp - 600) || $timestamp > ($local_timestamp + 300) ) {
		return false;
	}

	// Make sure that this is a unique signature to prevent replay attack
	$sql = "SELECT * FROM $wpdb->options WHERE option_name = %s";
	if ( $api_nonce = $wpdb->get_row( $wpdb->prepare($sql, 'kickpress_nonce_'.$token.'_'.$timestamp) ) ) {
		return false;
	}

	// Raw query so we can avoid races: since the add_option will also update
	$sql = "INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES (%s, %s, 'no')";
	if ( $wpdb->query( $wpdb->prepare($sql, 'kickpress_nonce_'.$token.'_'.$timestamp, $local_timestamp) ) ) {
		// nonce has been stored in the database, do something clever...
	}

	// Perform some data cleanup with any old timestamps
	$sql = "DELETE FROM $wpdb->options WHERE option_name LIKE 'kickpress_nonce_%%' AND CAST(option_value AS SIGNED) < (%d - 600)";
	$wpdb->query( $wpdb->prepare( $sql, $local_timestamp ) );

	$sql = "SELECT MD5(user_pass) AS api_secret, ID AS user_ID FROM $wpdb->users WHERE MD5(user_login) = %s";
	if ( $api_vars = $wpdb->get_row( $wpdb->prepare($sql, $token), ARRAY_A ) ) {
		extract( $api_vars );

		$user = new WP_User( $user_ID );
		if ( is_wp_error($user) ) {
			return false;
		}

		// Create the string to sign
		$string_to_sign = $method . "\n" . $host . "\n" . $timestamp . "\n";

		// Calculate signature with SHA1 and base64-encoding
		$local_signature = base64_encode( hash_hmac( 'sha1', $string_to_sign, $api_secret, true ) );

		// If we have made it this far and the signatures match, go 
		// ahead and log the api request in as a valid user
		if ( $local_signature == $signature ) {
			wp_set_current_user( $user_ID );

			// Looks like everything work our just fine, return the user id
			return $user_ID;
		} else {
			return false;
		}
	}

	// Why are you still here? Go away!
	return false;
} */

/* function kickpress_profile_api_fields( $user ) {
	global $wpdb;

	// To generate the 'token' and 'secret' create MD5 hashs of the user_login 
	// and user_pass fields from the 'wp_users' database table.
	$sql = "SELECT MD5(user_login) AS api_token, MD5(user_pass) AS api_secret FROM $wpdb->users WHERE ID = %s";

	if ( $api_vars = $wpdb->get_row( $wpdb->prepare($sql, $user->ID), ARRAY_A ) ) {
		extract( $api_vars );
	} else {
		return false;
	}
?>
	<h3>KickPress API Authentication</h3>
	<p>The API token and secret are issued to you as an individual and should not be shared with others, every user gets their own token and secret, these are an API's equivalent of a username and password.</p>
	<p>These values cannot be edited, but by changing your password, your API secret will automatically be regenerated and any API calls using the API secret will need to be updated as well.</p>
	<p>For more information on using the API and authentication read the <a href="http://kickpress.org/documentation/api-authentication/" target="_blank">documentation</a>.</p>
	<table class="form-table">
		<tr>
			<th><label for="twitter">API Token</label></th>
			<td>
				<input type="text" value="<?php echo $api_token; ?>" class="regular-text" disabled="disabled" /><br />
				<span class="description">This is your API token, and is exposed in the URL.</span>
			</td>
		</tr>
		<tr>
			<th><label for="twitter">API Secret</label></th>
			<td>
				<input type="text" value="<?php echo $api_secret; ?>" class="regular-text" disabled="disabled" /><br />
				<span class="description">This is your API password, it is never exposed in the URL and it should never be shared.</span>
			</td>
		</tr>
	</table>
<?php
} */

?>