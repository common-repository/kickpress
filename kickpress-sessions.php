<?php

define( 'KICKPRESS_SESSION_NAME', 'KPSESSID' );

function kickpress_start_session( $timeout = 86400 ) {
	if ( empty( $GLOBALS['kickpress_session'] ) )
		$GLOBALS['kickpress_session'] = new kickpress_session( $timeout );

	global $kickpress_session;
	return $kickpress_session->start();
}

function kickpress_destroy_session() {
	return kickpress_start_session()->destroy();
}

function kickpress_get_session_var( $key ) {
	return kickpress_start_session()->get_var( $key );
}

function kickpress_set_session_var( $key, $value ) {
	return kickpress_start_session()->set_var( $key, $value );
}

function kickpress_delete_session_var( $key ) {
	return kickpress_start_session()->delete_var( $key );
}

class kickpress_session {
	public $_session_id;
	private $_option_name;
	private $_expire_name;
	private $_timeout;
	private $_vars;

	public function __construct( $timeout = 86400 ) {
		$this->_timeout = $timeout;
		$this->_vars    = array();
	}

	public function start() {
		if ( empty( $this->_session_id ) ) {
			if ( isset( $_COOKIE[ KICKPRESS_SESSION_NAME ] ) ) {
				$this->_session_id = $_COOKIE[ KICKPRESS_SESSION_NAME ];
			} else {
				$this->_session_id = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
					mt_rand( 0, 0xFFFF ),
					mt_rand( 0, 0xFFFF ),
					mt_rand( 0, 0xFFFF ),
					mt_rand( 0, 0x0FFF ) | 0x4000, // 4 most significant bits hold version number 4
					mt_rand( 0, 0x3FFF ) | 0x8000, // 2 most significant bits hold 10 for variant DCE1.1
					mt_rand( 0, 0xFFFF ),
					mt_rand( 0, 0xFFFF ),
					mt_rand( 0, 0xFFFF )
				);

				$this->_set_cookie();
			}

			$this->_option_name = 'kpsess_' . $this->_session_id;
			$this->_expire_name = $this->_option_name . '_expire';

			if ( ( $vars = get_option( $this->_option_name ) ) !== false ) {
				$this->_vars = $vars;
			} else {
				delete_option( $this->_expire_name );

				add_option( $this->_option_name, $this->_vars, null, 'no' );
				add_option( $this->_expire_name, time() + $this->_timeout, null, 'no' );
			}
		}

		return $this;
	}

	public function clean() {
		global $wpdb;

		$timestamp = time();

		$sql = <<<SQL
SELECT option_name, option_value
FROM $wpdb->options
WHERE option_name LIKE 'kpsess\_%\_expire'
AND option_value < $timestamp
SQL;

		if ( $wpdb->query( $sql ) ) {
			foreach ( $wpdb->last_result as $row ) {
				$option_name = substr( $row->option_name, 0, -7 );

				delete_option( $row->option_name );
				delete_option( $option_name );
			}
		}
	}

	public function destroy() {
		if ( defined( 'DOING_CRON' ) )
			return false;

		if ( $this->_delete_option() ) {
			$this->_unset_cookie();
			$this->_session_id = null;
			$this->_option_name = null;
			$this->_expire_name = null;
			$this->_vars = array();
		}
	}

	public function get_var( $key ) {
		return $this->_vars[$key];
	}

	public function set_var( $key, $value ) {
		if ( defined( 'DOING_CRON' ) )
			return false;

		$this->_vars[$key] = $value;
		$this->_update_option();
	}

	public function delete_var( $key ) {
		if ( defined( 'DOING_CRON' ) )
			return false;

		unset( $this->_vars[$key] );
		$this->_update_option();
	}

	private function _update_option() {
		if ( defined( 'DOING_CRON' ) )
			return false;

		return update_option( $this->_option_name, $this->_vars ) &&
			update_option( $this->_expire_name, time() + $this->_timeout );
	}

	private function _delete_option() {
		if ( defined( 'DOING_CRON' ) )
			return false;

		return delete_option( $this->_option_name ) &&
			delete_option( $this->_expire_name );
	}

	private function _set_cookie() {
		if ( defined( 'DOING_CRON' ) )
			return false;

		if ( ! headers_sent() ) {
			setcookie( KICKPRESS_SESSION_NAME, $this->_session_id, time() + $this->_timeout, '/' );

			$_COOKIE[ KICKPRESS_SESSION_NAME ] = $this->_session_id;
		} else {
			printf(
				'<script type="text/javascript">document.cookie = "%s";</script>',
				$this->_serialize_cookie( array(
					KICKPRESS_SESSION_NAME => $this->_session_id,
					'expires' => gmdate( DATE_COOKIE, time() + $this->_timeout ),
					'path' => '/'
				) )
			);
		}
	}

	private function _unset_cookie() {
		if ( defined( 'DOING_CRON' ) )
			return false;

		if ( isset( $_COOKIE[ KICKPRESS_SESSION_NAME ] ) ) {
			if ( ! headers_sent() ) {
				setcookie( KICKPRESS_SESSION_NAME, null, time() - 3600, '/' );
			} else {
				printf(
					'<script type="text/javascript">document.cookie = "%s";</script>',
					$this->_serialize_cookie( array(
						KICKPRESS_SESSION_NAME => null,
						'expires' => gmdate( DATE_COOKIE, time() - 3600 ),
						'path' => '/'
					) )
				);
			}

			unset( $_COOKIE[ KICKPRESS_SESSION_NAME ] );
		}
	}

	private function _serialize_cookie( $cookie ) {
		$pairs = array();

		foreach ( $cookie as $key => $value )
			$pairs[] = rawurlencode( $key ) . '=' . rawurlencode( $value );

		return implode( '; ', $pairs );
	}
}

?>