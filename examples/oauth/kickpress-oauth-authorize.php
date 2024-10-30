<?php

/**
 * OAuth Credentials
 * 
 * These values should be changed to reflect your own key/secret pair
 */
define('OAUTH_CONSUMER_KEY',    'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('OAUTH_CONSUMER_SECRET', 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');

require_once 'kickpress-oauth-functions.php';

header('Content-type: text/plain');
die( sign_request('http://provider.domain.com/api/oauth[request-token]/', array(
	'oauth_callback' => 'http://your.domain.com/kickpress-oauth-authorize.php'
) ) );

session_start();

/**
 * OAuth Access Token
 * 
 * This token/secret pair is used to authenticate the user who has authorized
 * your app. Non-user-related resources can be accessed without this token.
 *
 * See related comment below
 */
if (isset($_SESSION['oauth_token']) && isset($_SESSION['oauth_token_secret'])) {
	define('OAUTH_TOKEN',        $_SESSION['oauth_token']);
	define('OAUTH_TOKEN_SECRET', $_SESSION['oauth_token_secret']);
}

if (!isset($_REQUEST['oauth_token'])) {
	$uri = 'http://provider.domain.com/api/oauth[request-token]/';
	
	$callback_uri = 'http://your.domain.com/kickpress-oauth-authorize.php';
	
	$query = array('oauth_callback' => $callback_uri);
	
	if ($oauth = send_request($uri, $query)) {
		if (is_string($oauth)) {
			parse_str($oauth, $oauth);
			
			if ('true' == @$oauth['oauth_callback_confirmed']) {
				$_SESSION['oauth_token']        = $oauth['oauth_token'];
				$_SESSION['oauth_token_secret'] = $oauth['oauth_token_secret'];
				
				$uri = 'http://provider.domain.com/api/oauth[authorize]/';
				$url = $uri . '?oauth_token=' . $oauth['oauth_token'];
				
				header('Location: ' . $url);
			} else {
				/**
				 * OAuth Error
				 * 
				 * Examine $oauth array for details
				 */
			}
		} else {
			/**
			 * cURL Error
			 * 
			 * Examine $oauth array for details
			 */
		}
	} else {
		/**
		 * Signature Error
		 * 
		 * URI, Query, and Method could not be properly signed
		 */
	}
} elseif (isset($_REQUEST['oauth_verifier'])) {
	define('OAUTH_TOKEN',        $_SESSION['oauth_token']);
	define('OAUTH_TOKEN_SECRET', $_SESSION['oauth_token_secret']);
	
	$uri = 'http://provider.domain.com/api/oauth[access-token]/';
	
	$query = array('oauth_verifier' => $_REQUEST['oauth_verifier']);
	
	if ($oauth = send_request($uri, $query)) {
		if (is_string($oauth)) {
			parse_str($oauth, $oauth);
			
			if (0 == @$oauth['errno']) {
				$_SESSION['oauth_token']        = $oauth['oauth_token'];
				$_SESSION['oauth_token_secret'] = $oauth['oauth_token_secret'];
				
				/**
				 * OAuth Access Token
				 * 
				 * This token/secret pair can now be used to authenticate the
				 * user for accessing protected resources. These credentials
				 * should be stored in a secure location for future use.
				 *
				 * See related comment above
				 */
			} else {
				/**
				 * OAuth Error
				 * 
				 * Examine $oauth array for details
				 */
			}
		} else {
			/**
			 * cURL Error
			 * 
			 * Examine $oauth array for details
			 */
		}
	} else {
		/**
		 * Signature Error
		 * 
		 * URI, Query, and Method could not be properly signed
		 */
	}
}

?>