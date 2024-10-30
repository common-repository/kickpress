<?php

/**
 * Sample file for accessing the KickPress API
 * Documentation available at http://kickpress.org/documentation/api-authentication/
 */ 

// Each user is assigned a 'token' and 'secret' by the KickPress API.
// These values can be found on the user's profile page.
$token = 'token-from-profile-page';
$secret = 'secret-from-profile-page';

// The 'timestamp' is the unix timestamp, generates a unique signature for every request
$timestamp = time();

// The method being used to access the API, either GET or POST
$method = 'GET';

// The host will need to match the value of $_SERVER['HTTP_HOST'] 
// on the receiving end of the API call, the base domain with no
// http or slashes
$host = 'www.yoursite.com';

// Create the string to sign
$string_to_sign = $method . "\n" . $host . "\n" . $timestamp . "\n";

// Calculate signature with SHA1 and base64-encoding
$signature = base64_encode( hash_hmac( 'sha1', $string_to_sign, $secret, true ) );

$kickpress_authentication_params = array(
	'signature' => $signature,
	'timestamp' => $timestamp,
	'token'     => $token
);

// Normalize the query string parameters
$query_string_parts = normalized_query_parameters( $kickpress_authentication_params );
$query_string = implode('&', $query_string_parts);

// Build the URL for the remote API request
$url = 'http://www.yoursite.com/{a custom post type}/api/{your api call}/?' . $query_string;

// Do the API request and capture the response using curl
$session = curl_init($url);
curl_setopt($session, CURLOPT_HEADER, false);
curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($session);
curl_close($session);

// Output the results for testing purposes
echo "<pre>API RESPONSE: ";
print_r($response);
echo "</pre>";

/**
 * Bellow are a set of functions borrowed and modified from Jetpack
 */ 
function normalized_query_parameters( $query_params ) {
	$names  = array_keys( $query_params );
	$values = array_values( $query_params );

	$names  = array_map( 'encode_3986', $names  );
	$values = array_map( 'encode_3986', $values );

	$pairs  = array_map( 'join_with_equal_sign', $names, $values );

	sort( $pairs );

	return $pairs;
}

function encode_3986( $string ) {
	$string = rawurlencode( $string );
	// prior to PHP 5.3, rawurlencode was RFC 1738
	return str_replace( '%7E', '~', $string ); 
}

function join_with_equal_sign( $name, $value ) {
	return "{$name}={$value}";
}

?>