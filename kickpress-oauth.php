<?php

//add_action( 'login_form', 'kickpress_login_form' );

function kickpress_get_oauth_signature( $uri, $query = array(), $args = array() ) {
	$defaults = array(
		'method'          => "GET",
		'consumer_key'    => "",
		'consumer_secret' => ""
	);

	$args = wp_parse_args( $args, $defaults );

	extract( $args );

	$key = rawurlencode( $consumer_secret ) . '&'
		 . rawurlencode( $access_token_secret );

	$oauth = array(
		'oauth_consumer_key'     => $consumer_key,
		'oauth_nonce'            => md5(uniqid(rand(), true)),
		'oauth_signature_method' => "HMAC-SHA1",
		'oauth_timestamp'        => time(),
		'oauth_version'          => "1.0"
	);

	if ( ! empty( $access_token ) )
		$oauth['oauth_token'] = $access_token;

	$params = $oauth + $query;

	ksort( $params );

	$message = strtoupper( $method ) . '&'
			 . rawurlencode( $uri ) . '&'
			 . rawurlencode( http_build_query( $params ) );

	$oauth['oauth_signature'] = base64_encode( hash_hmac( 'sha1', $message, $key, true ) );

	$params = array();

	foreach ( $oauth as $key => $value ) {
		$params[] = $key .'="' . rawurlencode( $value ) . '"';
	}

	return 'OAuth ' . implode( ', ', $params );
}

function kickpress_login_form() {
	global $kickpress_plugin_options;

	if ( ! empty( $kickpress_plugin_options['facebook_app_id']['value'] ) ) {
		$fb_url = kickpress_api_url( array(
			'action'     => 'login',
			'action_key' => 'facebook'
		) );

		$fb_icon = plugins_url( 'includes/images/facebook.png', __FILE__ );

		printf( '
			<p style="line-height: 48px; font-size: 16px;">
				<a href="%s" title="Connect with Facebook">
					<img src="%s" style="vertical-align: middle;"><span>Connect with Facebook</span>
				</a>
			</p>',
			$fb_url,
			$fb_icon
		);
	}

	if ( ! empty( $kickpress_plugin_options['twitter_consumer_key']['value'] ) ) {
		$tw_url = kickpress_api_url( array(
			'action'     => 'login',
			'action_key' => 'twitter'
		) );

		$tw_icon = plugins_url( 'includes/images/twitter.png', __FILE__ );

		printf( '
			<p style="line-height: 48px; font-size: 16px;">
				<a href="%s" title="Sign in with Twitter">
					<img src="%s" style="vertical-align: middle;"><span>Sign in with Twitter</span>
				</a>
			</p>',
			$tw_url,
			$tw_icon
		);
	}
}

function kickpress_oauth_authenticate() {
	kickpress_oauth_provider::authenticate();
}

function kickpress_oauth_clear_request_tokens() {
	global $wpdb;

	$sql = "DELETE FROM `$wpdb->oauth_tokens` WHERE `type` = 'request' AND `date` < %s";

	$wpdb->query( $wpdb->prepare( $sql, date( 'Y-m-d H:i:s', time() - 300 ) ) );
}

class kickpress_oauth {
	public static function get_args() {
		$method = strtoupper( $_SERVER['REQUEST_METHOD'] );
		$path   = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		$uri    = get_bloginfo( 'url' ) . $path;
		$query  = 'POST' == $method ? $_POST : $_GET;

		unset( $query['q'] );

		return compact( 'method', 'uri', 'query' );
	}

	/**
	 * @param	array	$vars	array of query variables
	 * @param	array	$args	array of output modifiers
	 */
	public static function normalize_params( $vars = array(), $args = array() ) {
		$defaults = array(
			'sorted'       => true,
			'keyed'        => true,
			'delimiter'    => '&',
			'concatenater' => '=',
			'quotes'       => ''
		);

		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		if ( $sorted )
			ksort( $vars );

		$items = array();

		foreach ( $vars as $key => $value ) {
			if ( is_string( $value ) ) {
				$item = $quotes . rawurlencode( $value ) . $quotes;

				if ( $keyed )
					$item = $key . $concatenater . $item;

				$items[] = $item;
			} elseif ( is_array( $value ) ) {
				//$items += self::normalize_array( $value, $args, $key );

				if ( $sorted )
					ksort( $value );

				foreach ( $value as $subkey => $subvalue ) {
					if ( is_string( $subvalue ) ) {
						$item = $quotes . rawurlencode( $subvalue ) . $quotes;

						if ( $keyed )
							$item = rawurlencode( $key . '[' . $subkey . ']' ) . $concatenater . $item;

						$items[] = $item;
					}
				}
			}
		}

		return implode( $delimiter, $items );
	}

	public static function normalize_array( $vars = array(), $args = array(), $prefix = '') {
		$defaults = array(
			'sorted'       => true,
			'keyed'        => true,
			'delimiter'    => '&',
			'concatenater' => '=',
			'quotes'       => ''
		);

		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		if ( $sorted )
			ksort( $vars );

		$items = array();

		foreach ( $vars as $key => $value ) {
			if ( is_string( $value ) ) {
				$item = $quotes . rawurlencode( $value ) . $quotes;

				if ( $keyed )
					$item = rawurlencode( $prefix . '[' . $key . ']' ) . $concatenater . $item;

				$items[] = $item;
			} elseif ( is_array( $value ) ) {
				$items += self::normalize_array( $value, $args, $prefix . '[' . $key . ']' );
			}
		}

		return $items;
	}

	protected $_consumer_key;
	protected $_consumer_secret;
	protected $_token;
	protected $_token_secret;

	public function __construct( $args = array() ) {
		extract( $args, EXTR_SKIP );

		@$this->set_consumer( $consumer_key, $consumer_secret );
		@$this->set_token( $token, $token_secret );
	}

	public function __get( $key ) {
		$property = '_' . $key;

		return property_exists( $this, $property ) ? $this->$property : null;
	}

	public function __isset( $key ) {
		return property_exists( $this, '_' . $key );
	}

	public function set_consumer( $consumer_key, $consumer_secret = null ) {
		$this->_consumer_key    = $consumer_key;
		$this->_consumer_secret = $consumer_secret;
	}

	public function set_token( $token, $token_secret = null ) {
		$this->_token        = $token;
		$this->_token_secret = $token_secret;
	}

	public function sign_request( $args = array() ) {
		$defaults = array(
			'method' => 'GET',
			'uri'    => null,
			'query'  => array()
		);

		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		if ( empty( $uri ) ) return false;

		$oauth = $this->_get_params();
		$oauth['oauth_signature'] = $this->_get_signature( array(
			'method' => $method,
			'uri'    => $uri,
			'query'  => $oauth + $query
		) );

		return 'OAuth ' . self::normalize_params( $oauth, array(
			'delimiter' => ', ',
			'quotes'    => '"'
		) );
	}

	protected function _get_signature( $args = array() ) {
		$defaults = array(
			'method' => 'GET',
			'uri'    => null,
			'query'  => array()
		);

		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		$method = strtoupper( $method );

		if ( isset( $query['data'] ) )
			$query['data'] = stripslashes( $query['data'] );

		$query = self::normalize_params( $query );

		$key = sprintf( "%s&%s", $this->consumer_secret, $this->token_secret );

		$msg = self::normalize_params( array(
			$method,
			$uri,
			$query
		), array(
			'sorted' => false,
			'keyed'  => false
		) );

		return base64_encode( hash_hmac( 'sha1', $msg, $key, true ) );
	}

	protected function _get_params() {
		$params = array(
			'oauth_consumer_key'     => $this->_consumer_key,
			'oauth_nonce'            => md5( uniqid( rand(), true ) ),
			'oauth_signature_method' => 'HMAC-SHA1',
			'oauth_timestamp'        => time(),
			'oauth_version'          => '1.0'
		);

		if ( ! empty( $this->_token ) )
			$params['oauth_token'] = $this->_token;

		return $params;
	}
}

class kickpress_oauth_consumer extends kickpress_oauth {
	public static function get_instance( $consumer_key ) {
		if ( $app = kickpress_get_app( $consumer_key ) ) {
			return new self( array(
				'consumer_key'    => $app->consumer_key,
				'consumer_secret' => $app->consumer_secret,
				'callback'        => $app->callback_url
			) );
		}

		return false;
	}

	protected $_callback;
	protected $_verifier;

	protected $_user_id;

	public function __construct( $args = array() ) {
		parent::__construct( $args );

		extract( $args, EXTR_SKIP );

		@$this->set_callback( $callback );
		@$this->set_verifier( $verifier );
	}

	public function set_callback( $callback ) {
		$this->_callback = $callback;
	}

	public function set_verifier( $verifier ) {
		$this->_verifier = $verifier;
	}

	public function set_user_id( $user_id ) {
		$this->_user_id = $user_id;
	}

	public function get_app() {
		return kickpress_get_app( $this->_consumer_key );
	}

	public function is_authorized() {
		global $wpdb;

		$sql = "SELECT * FROM $wpdb->oauth_tokens WHERE user_id = %d AND consumer_key = %s";

		return $wpdb->get_row( $wpdb->prepare($sql, get_current_user_id(), $this->_consumer_key ) );
	}

	protected function _get_params() {
		$params = parent::_get_params();

		if ( ! empty( $this->_callback ) )
			$params['oauth_callback'] = $this->_callback;

		if ( ! empty( $this->_verifier ) )
			$params['oauth_verifier'] = $this->_verifier;

		ksort( $params );

		return $params;
	}
}

class kickpress_oauth_provider extends kickpress_oauth {
	protected static $_action;

	protected static function get_token( $token, $type = '' ) {
		global $wpdb;

		if ( empty( $type ) ) {
			$sql = "SELECT * FROM $wpdb->oauth_tokens WHERE token = %s";
			return $wpdb->get_row( $wpdb->prepare( $sql, $token ) );
		} elseif ( 'request' == $type ) {
			$sql = "SELECT * FROM $wpdb->oauth_tokens WHERE token = %s AND `type` = %s AND `date` > %s";
			return $wpdb->get_row( $wpdb->prepare( $sql, $token, $type,
				date( 'Y-m-d H:i:s', time() - 300 ) ) );
		} else {
			$sql = "SELECT * FROM $wpdb->oauth_tokens WHERE token = %s AND `type` = %s";
			return $wpdb->get_row( $wpdb->prepare( $sql, $token, $type ) );
		}
	}

	public static function request_token( $args = null ) {
		self::$_action = 'request-token';

		if ( is_null( $args ) ) $args = self::get_args();

		header( 'Content-type: text/plain' );

		$consumer = self::validate_consumer( $args );

		if ( is_wp_error( $consumer ) ) {
			die( self::normalize_params( array(
				'oauth_error'             => $consumer->get_error_code(),
				'oauth_error_description' => $consumer->get_error_message()
			) ) );
		} else {
			$token  = md5(  uniqid( rand(), true ) );
			$secret = sha1( uniqid( rand(), true ) );

			global $wpdb;

			$wpdb->insert( $wpdb->oauth_tokens, array(
				'consumer_key' => $consumer->consumer_key,
				'token'        => $token,
				'secret'       => $secret,
				'type'         => 'request',
				'date'         => date( 'Y-m-d H:i:s' )
			) );

			die( self::normalize_params( array(
				'oauth_token'              => $token,
				'oauth_token_secret'       => $secret,
				'oauth_callback_confirmed' => 'true'
			) ) );
		}
	}

	public static function access_token( $args = null ) {
		self::$_action = 'access-token';

		if ( is_null( $args ) ) $args = self::get_args();

		header( 'Content-type: text/plain' );

		$consumer = self::validate_consumer( $args );

		if ( is_wp_error( $consumer ) ) {
			die( self::normalize_params( array(
				'oauth_error'             => $consumer->get_error_code(),
				'oauth_error_description' => $consumer->get_error_message()
			) ) );
		} elseif ( $request_token = self::get_token( $consumer->token, 'request' ) ) {
			global $wpdb;

			$wpdb->delete( $wpdb->oauth_tokens, array(
				'token' => $consumer->token
			) );

			$query = "SELECT * FROM $wpdb->oauth_tokens WHERE consumer_key = %s AND user_id = %d AND `type` = 'access'";
			$query = $wpdb->prepare( $query, $consumer->consumer_key, $request_token->user_id );

			if ( $access_token = $wpdb->get_row( $query ) ) {
				$token  = $access_token->token;
				$secret = $access_token->secret;
			} else {
				$token  = md5(  uniqid( rand(), true ) );
				$secret = sha1( uniqid( rand(), true ) );

				$wpdb->insert( $wpdb->oauth_tokens, array(
					'user_id'      => intval( $request_token->user_id ),
					'consumer_key' => $consumer->consumer_key,
					'token'        => $token,
					'secret'       => $secret,
					'type'         => 'access',
					'date'         => date( 'Y-m-d H:i:s' )
				) );
			}

			die( self::normalize_params( array(
				'oauth_token'        => $token,
				'oauth_token_secret' => $secret
			) ) );
		} else {
			die( self::normalize_params( array(
				'oauth_error' => 'invalid_request'
			) ) );
		}
	}

	public static function authorize( $args = null ) {
		self::$_action = 'authorize';

		if ( is_null( $args ) ) $args = self::get_args();

		if ( kickpress_is_remote_app() && ! is_user_logged_in() ) {
			if ( empty( $_REQUEST['login'] ) ) {
				die( self::normalize_params( array(
					'oauth_error'      => 'Please enter a username.',
					'oauth_error_type' => 'user_name'
				) ) );
			} elseif ( empty( $_REQUEST['password'] ) ) {
				die( self::normalize_params( array(
					'oauth_error'      => 'Please enter a password.',
					'oauth_error_type' => 'user_pass'
				) ) );
			}

			$user = wp_authenticate( $_REQUEST['login'], $_REQUEST['password'] );

			if ( is_wp_error( $user ) ) {
				$message = $user->get_error_message();
				$code    = $user->get_error_code();

				if ( 'invalid_username' == $code )
					$message = 'Invalid username.';
				elseif ( 'incorrect_password' == $code )
					$message = 'Incorrect password.';

				die( self::normalize_params( array(
					'oauth_error'      => $message,
					'oauth_error_type' => $code
				) ) );
			} else {
				wp_set_current_user( $user->ID );
			}
		}

		if ( $user_id = get_current_user_id() ) {
			$token = $args['query']['oauth_token'];

			if ( $oauth = self::get_token( $token, 'request' ) ) {
				if ( $consumer = kickpress_oauth_consumer::get_instance( $oauth->consumer_key ) ) {
					extract( $args, EXTR_SKIP );

					$authorize = strtolower( $query['authorize'] );

					$return_query = array( 'oauth_token' => $query['oauth_token'] );

					// Check for prior authorization
					if ( 'yes' == $authorize || $consumer->is_authorized() ) {
						$verifier = sha1( uniqid( rand(), true ) );

						global $wpdb;

						$wpdb->update( $wpdb->oauth_tokens, array(
							'verifier' => $verifier,
							'user_id'  => $user_id
						), array(
							'token' => $token
						) );

						$return_query['oauth_verifier'] = $verifier;

						$is_blog_user = false;

						$blog_id = get_current_blog_id();

						$blogs = get_blogs_of_user( $user_id );

						foreach ( $blogs as $blog ) {
							if ( $blog_id == $blog->userblog_id ) {
								$is_blog_user = true;
								break;
							}
						}

						if ( ! $is_blog_user )
							add_user_to_blog( $blog_id, $user_id, 'subscriber' );
					} elseif ( 'no' == $authorize ) {
						$return_query['oauth_error'] = 'Denied access by user';
					} else {
						self::authorize_form( $args );
						exit;
					}

					if ( kickpress_is_remote_app() ) {
						die( self::normalize_params( $return_query ) );
					}

					$url = $consumer->callback . '?'
					     . http_build_query( $return_query );

					header( 'Location: ' . $url );
				} else {
					die( '<b>Error:</b> Failed to validate oauth token and consumer key' );
				}
			} else {
				die( '<b>Error:</b> Failed to validate oauth token' );
			}
		} else {
			$url = str_replace( array( '[', ']' ), array( '%5B', '%5D' ),
				$args['uri'] ) . '?' . http_build_query( $args['query'] );

			header( 'Location: ' . wp_login_url( $url ) );
		}
	}

	public static function authorize_form( $args ) {
		get_header();

		printf( '<form action="%s" method="post">' .
			'<input type="hidden" name="oauth_token" value="%s">' .
			'<label>Authorize this app?</label>' .
			'<input type="submit" name="authorize" value="Yes">' .
			'<input type="submit" name="authorize" value="No">' .
			'</form>',
			esc_attr( $args['uri'] ),
			esc_attr( $args['query']['oauth_token'] )
		);

		get_footer();
	}

	public static function authenticate( $args = null ) {
		self::$_action = 'authenticate';

		if ( ! isset( $_SERVER['HTTP_AUTHORIZATION'] ) &&
			function_exists( 'apache_request_headers' ) ) {
			$headers = apache_request_headers();
			if ( isset( $headers['Authorization'] ) )
				$_SERVER['HTTP_AUTHORIZATION'] = $headers['Authorization'];
		}

		$auth = @trim( stripslashes( $_SERVER['HTTP_AUTHORIZATION'] ) );

		if ( stripos( $auth, 'OAuth' ) === 0 ) {
			$consumer = self::validate_consumer( $args );

			if ( ! is_wp_error( $consumer ) ) {
				define( 'REMOTE_APP_TOKEN', $consumer->consumer_key );

				if ( 0 < $consumer->user_id )
					wp_set_current_user( $consumer->user_id );
			}
		}
	}

	public static function validate_consumer( $args = null ) {
		if ( ! isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			$realm = $_SERVER['HTTP_HOST'];

			header( 'HTTP/1.1 401 Unauthorized' );
			header( 'WWW-Authenticate: OAuth realm="' . $realm . '"' );

			exit;
		}

		if ( is_null( $args ) ) $args = self::get_args();

		$provider = new self();

		if ( $oauth = $provider->parse_signature() ) {
			$key = $oauth['oauth_consumer_key'];

			if ( $consumer = kickpress_oauth_consumer::get_instance( $key ) ) {
				$provider->set_consumer( $key, $consumer->consumer_secret );

				$token_actions = array(
					'access-token',
					'authenticate'
				);

				if ( in_array( self::$_action, $token_actions ) ) {
					$token_approved = false;

					if ( 'access-token' == self::$_action ) {
						if ( $token = self::get_token( @$oauth['oauth_token'], 'request' ) ) {
							$verifier = @$args['query']['oauth_verifier'];

							if ( ! empty( $verifier ) && $token->verifier == $verifier ) {
								$consumer->set_verifier( $verifier );

								$token_approved = true;
							} else {
								return new WP_Error( 'invalid_request',
									'Failed to verify oauth request' );
							}
						}
					} elseif ( 'authenticate' == self::$_action ) {
						if ( $token = self::get_token( @$oauth['oauth_token'] ) ) {
							if ( 'access' == $token->type ) {
								$consumer->set_user_id( $token->user_id );
							}

							$token_approved = true;
						}
					}

					if ( $token_approved ) {
						$provider->set_token( $token->token, $token->secret );
						$consumer->set_token( $token->token, $token->secret );
					} else {
						/* return new WP_Error( 'invalid_request',
							'Failed to validate oauth token' ); */
					}
				}

				if ( $provider->check_signature( null, $args ) ) {
					if ( isset( $args['query']['oauth_callback'] ) ) {
						$oauth_callback    = $args['query']['oauth_callback'];
						$consumer_callback = $consumer->callback;

						$oauth_host    = parse_url( $oauth_callback, PHP_URL_HOST );
						$consumer_host = parse_url( $consumer_callback, PHP_URL_HOST );

						if ( $consumer_host == $oauth_host ) {
							$consumer->set_callback( $oauth_callback );
						} else {
							return new WP_Error( 'callback_uri_mismatch',
								'Failed to validate oauth callback' );
						}
					}

					return $consumer;
				} else {
					return new WP_Error( 'invalid_client',
						'Failed to validate oauth signature and token' );
				}
			} else {
				return new WP_Error( 'invalid_client',
					'Failed to validate oauth signature' );
			}
		} else {
			return new WP_Error( 'invalid_request',
				'Failed to validate oauth signature' );
		}
	}

	public function parse_signature( $signature = null ) {
		if ( empty( $signature ) )
			$signature = stripslashes( $_SERVER['HTTP_AUTHORIZATION'] );

		if ( preg_match_all( '/([a-z_]+)="(.*)"/Ui', $signature, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$oauth[$match[1]] = rawurldecode( $match[2] );
			}

			unset( $oauth['realm'] );
			ksort( $oauth );

			if ( isset( $oauth['oauth_consumer_key'] ) && empty( $this->_consumer_key ) )
				$this->set_consumer( $oauth['oauth_consumer_key'] );

			if ( isset( $oauth['oauth_token'] ) && empty( $this->_token ) )
				$this->set_token( $oauth['oauth_token'] );

			return $oauth;
		}

		return false;
	}

	public function check_signature( $signature = null, $args = array() ) {
		$defaults = array(
			'method' => 'GET',
			'uri'    => null,
			'query'  => array()
		);

		extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

		if ( empty( $uri ) ) return false;

		$uri = str_replace( array( '%5B', '%5D' ), array( '[', ']' ), $uri );

		if ( $oauth = $this->parse_signature( $signature ) ) {
			$oauth_signature = $oauth['oauth_signature'];

			unset( $oauth['oauth_signature'] );

			$proof_signature = $this->_get_signature( array(
				'method' => $method,
				'uri'    => $uri,
				'query'  => $oauth + $query
			) );

			if ( $oauth_signature != $proof_signature && isset( $_SERVER['HTTP_X_SOURCE'] ) ) {
				$logger = new mysqli( LOGGING_DB_HOST, LOGGING_DB_USER, LOGGING_DB_PASSWORD, LOGGING_DB_NAME );

				if ( $logger->connect_error )
					error_log( $logger->connect_error );

				$sql = 'INSERT INTO `oauth_failure` (`source`, `request_id`, `authorization`, `expected_signature`, `method`, `uri`, `query`, `secret`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

				$query = http_build_query( $oauth + $query );
				$secret = sprintf( "%s&%s", $this->consumer_secret, $this->token_secret );

				$stmt = $logger->prepare( $sql );
				$stmt->bind_param( 'ssssssss', $_SERVER['HTTP_X_SOURCE'], $_SERVER['HTTP_X_REQUEST_ID'], $_SERVER['HTTP_AUTHORIZATION'],
					$proof_signature, $method, $uri, $query, $secret );

				if ( ! $stmt->execute() )
					error_log( $stmt->error );

				$logger->close();
			}

			return $oauth_signature == $proof_signature;
		}

		return false;
	}
}

?>
