<?php

/**
 * OAuth Credentials
 * 
 * These values should be changed to reflect your own key/secret pair
 */
define('OAUTH_CONSUMER_KEY',    'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('OAUTH_CONSUMER_SECRET', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

require_once 'kickpress-oauth-functions.php';

session_start();

/**
 * OAuth Access Token
 * 
 * This token/secret pair is used to authenticate the user who has authorized
 * your app. Non-user-related resources can be accessed without this token.
 */
if (isset($_SESSION['oauth_token']) && isset($_SESSION['oauth_token_secret'])) {
	define('OAUTH_TOKEN',        $_SESSION['oauth_token']);
	define('OAUTH_TOKEN_SECRET', $_SESSION['oauth_token_secret']);
}

/**
 * We'll fetch all the posts for September 2012 as a JSON string
 */
$uri = 'http://provider.domain.com/api/export.json'
     . '/date[min]/2012-09-01/date[max]/2012-09-30';

if ($json_data = send_request($uri)) {
	if (is_string($oauth)) {
		$posts = json_decode($json_data);
		
		/**
		 * Now, do something with the content.
		 */
	} else {
		/**
		 * cURL Error
		 * 
		 * Examine $json_data array for details
		 */
	}
} else {
	/**
	 * Signature Error
	 * 
	 * URI, Query, and Method could not be properly signed
	 */
}

?>