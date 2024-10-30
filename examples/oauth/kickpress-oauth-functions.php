<?php

function send_request($uri, $query = array(), $method = 'GET') {
	if ($signature = sign_request($uri, $query, $method)) {
		$url = $uri;
		
		if (!empty($query))
			$url .= '?' . http_build_query($query);
		
		$curl = curl_init($url);
		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'Authorization: ' . $signature
		));
		
		$response = curl_exec($curl);
		$error    = curl_error($curl);
		$errno    = curl_errno($curl);
		
		curl_close($curl);
		
		if (0 < $error)
			return array('errno' => $errno, 'error' => $error);
		
		return $response;
	}
	
	return false;
}

function sign_request($uri, $query = array(), $method = 'GET') {
	$oauth = array(
		'oauth_consumer_key'     => OAUTH_CONSUMER_KEY,
		'oauth_nonce'            => md5(uniqid(rand(), true)),
		'oauth_signature_method' => 'HMAC-SHA1',
		'oauth_timestamp'        => time(),
		'oauth_version'          => '1.0'
	);
	
	if (defined('OAUTH_TOKEN'))
		$oauth['oauth_token'] = OAUTH_TOKEN;
	
	$method = strtoupper($method);
	
	$items = array();
	
	foreach ($query as $key => $value) {
		if (!is_scalar($value)) continue;
		
		$value   = rawurlencode($value);
		$items[] = "{$key}={$value}";
	}
	
	if (!sort($items)) return false;
	
	$query_string = implode('&', $items);
	
	$key = OAUTH_CONSUMER_SECRET . '&';

	$msg = rawurlencode($method) . '&'
	     . rawurlencode($uri) . '&'
	     . rawurlencode($query_string);
	
	if (defined('OAUTH_TOKEN_SECRET'))
		$key .= OAUTH_TOKEN_SECRET;
	
	$sig = hash_hmac('sha1', $msg, $key, true);
	
	$oauth['oauth_signature'] = base64_encode($sig);
	
	$items = array();
	
	foreach ($oauth as $key => $value) {
		$value   = rawurlencode($value);
		$items[] = "{$key}=\"{$value}\"";
	}
	
	if (!sort($items)) return false;
	
	return 'OAuth ' . implode(', ', $items);
}

?>