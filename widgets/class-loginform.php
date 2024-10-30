<?php

class kickpress_loginform_widget extends WP_Widget {
	public function __construct() {
		parent::__construct( __CLASS__, 'Login Form', array(
			'description' => 'Login Form Widget',
			'classname'   => __CLASS__
		) );
	}
	
	public function widget( $args, $params ) {
		global $current_user;
		
		extract( $args );
		
		echo $before_widget;
		
		if ( is_user_logged_in() ) {
?>
<div>Hello, <?php echo $current_user->display_name; ?></div>
<a href="<?php echo wp_logout_url( home_url() ); ?>">Logout</a>
<?php
		} else {
?>
<form name="loginform" id="loginform" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post">
	<p>
		<label for="user_login"><?php _e('Username', 'kickpress') ?></label><br>
		<input id="user_login" type="text" size="20" name="log">
	</p>
	<p>
		<label for="user_pass"><?php _e('Password', 'kickpress') ?></label><br>
		<input id="user_pass" type="password" size="20" name="pwd">
	</p>
	<?php do_action('login_form'); ?>
	<p class="forgetmenot">
		<input id="rememberme" type="checkbox" name="rememberme" value="forever"<?php checked( $rememberme ); ?>>
		<label for="rememberme"><?php esc_attr_e('Remember Me', 'kickpress'); ?></label>
	</p>
	<p class="submit">
		<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Log In'); ?>">
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( home_url() ); ?>">
		<input type="hidden" name="testcookie" value="1">
	</p>
</form>
<p id="nav">
	<?php if ( get_option('users_can_register') ) : ?>
	<a href="<?php echo esc_url( get_site_url( 1, 'wp-signup.php?site=' . get_current_blog_id(), 'login' ) ); ?>"><?php _e( 'Register', 'kickpress' ); ?></a> |
	<?php endif; ?>
	<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" title="<?php esc_attr_e( 'Password Lost and Found' ); ?>"><?php _e( 'Lost your password?', 'kickpress' ); ?></a>
</p>
<?php
		}
		
		echo $after_widget;
	}
	
	public function form( $params ) {
		// TODO
	}
	
	public function update( $new_params, $old_params ) {
		// TODO
	}
}

?>