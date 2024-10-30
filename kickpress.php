<?php
/*
Plugin Name: KickPress
Plugin URI: http://kickpress.org/
Description: Allows for apps, themes, and APIs to be built using WordPress as a php framework
Version: 0.3.5
Author: David S. Tufts
Author URI: http://kickpress.org
Text Domain: kickpress
License: GPL2
*/

/*	Copyright 2011	David S. Tufts	(email : david.tufts@rocketwood.com)

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License, version 2, as
		published by the Free Software Foundation.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA	02110-1301	USA
*/

define( 'KICKPRESS_DEBUG_TOKEN',
	md5( uniqid( rand(), true ) ) );

if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) )
	remove_action('init', 'wp_cron');

// Global plugin settings
global $wpdb;
$kickpress_api;
$kickpress_query_stack = array();
$kickpress_builtin_post_types = array( 'post', 'page', 'nav_menu_item', 'attachment' );
$kickpress_post_types = array( 'post' => array( 'post_type' => 'post', 'post_type_title' => 'Post' ) );

$sql = "
	SELECT
		{$wpdb->posts}.ID,
		{$wpdb->posts}.post_name,
		{$wpdb->posts}.post_title,
		{$wpdb->posts}.post_parent,
		{$wpdb->posts}.post_content
	FROM
		{$wpdb->posts}
	WHERE
		{$wpdb->posts}.post_type = 'custom-post-types'
		AND {$wpdb->posts}.post_status = 'publish'
	ORDER BY {$wpdb->posts}.post_name ASC";

foreach ( $custom_post_types = $wpdb->get_results($sql) as $custom_post_type ) {
	$kickpress_post_types[ (string) $custom_post_type->post_name ] = array(
		'post_type'             => (string) $custom_post_type->post_name,
		'post_type_id'          => (string) $custom_post_type->ID,
		'post_type_title'       => (string) $custom_post_type->post_title,
		'post_type_parent'      => (string) $custom_post_type->post_parent,
		'post_type_description' => (string) $custom_post_type->post_content
	);
}

// Allow kickpress to modify the WordPress query
add_filter( 'pre_get_posts', 'kickpress_set_post_types' );
add_filter( 'pre_get_posts', 'kickpress_query_filter'   );
add_filter( 'posts_fields',  'kickpress_query_fields',  10, 2 );
add_filter( 'posts_join',    'kickpress_query_join',    10, 2 );
add_filter( 'posts_where',   'kickpress_query_where',   10, 2 );
add_filter( 'posts_search',  'kickpress_query_search',  10, 2 );
add_filter( 'posts_groupby', 'kickpress_query_groupby', 10, 2 );
add_filter( 'posts_orderby', 'kickpress_query_orderby', 10, 2 );
add_filter( 'post_limits',   'kickpress_query_limits',  10, 2 );

add_action( 'init', 'kickpress_init_post_types' );
add_action( 'init', 'kickpress_init_taxonomies' );
add_action( 'init', 'kickpress_init_app_roles' );
add_action( 'parse_request', 'kickpress_parse_wp_request' );

add_action( 'widgets_init', 'kickpress_widgets_register');

add_action( 'plugins_loaded', 'kickpress_load_textdomain' );

define( 'KICKPRESSPATH', dirname( __FILE__ ) );
define( 'REQUEST_URI', $_SERVER['REQUEST_URI'] );

require_once(ABSPATH.'/wp-admin/includes/post.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-api.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-authentication.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-query-filters.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-functions.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-pagination.php');
// require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-categories-toolbar.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-redirects.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-excerpt.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-post-types.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-taxonomies.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-shortcodes.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-form-elements.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-plugin-options.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-widgets.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-sessions.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-capabilities.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-relationships.php');
//require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-workflows.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-bookmarks.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-series.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-application.php');
require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-oauth.php');
// require_once(WP_PLUGIN_DIR.'/kickpress/kickpress-curation.php');

/** Register the kickpress shortcodes */
add_shortcode( 'kickpress', 'kickpress_shortcode_handler' );
add_shortcode( 'kickpress-notes', 'kickpress_notes_shortcode' );
add_shortcode( 'kickpress-bookmarks', 'kickpress_bookmarks_shortcode' );
add_shortcode( 'kickpress-tasks', 'kickpress_tasks_shortcode' );
add_shortcode( 'kickpress-series', 'kickpress_series_shortcode' );

/** Run activation when the plugin is activated */
register_activation_hook(__FILE__, 'kickpress_activation');

/** Run deactivation when the plugin is deactivated */
register_deactivation_hook(__FILE__, 'kickpress_deactivation');

if ( is_admin() || strpos( REQUEST_URI, 'wp-admin' ) ) { //	|| strpos($url, 'index.php')
	// Called to save custom post attributes whenever post/page is saved
	add_action( 'save_post', 'kickpress_save_meta_fields' );

	// Use the admin_menu action to define the custom boxes for built in post and page
	add_action( 'admin_menu', 'kickpress_init_builtin_meta_boxes' );

	// Force permalinks to be rewritten when editing custom post types
	add_action( 'admin_head', 'kickpress_flush_rewrites' );
	add_action( 'admin_menu', 'kickpress_plugin_menu' );
	add_action( 'admin_print_scripts', 'kickpress_admin_scripts' );

	add_action( 'admin_menu', 'kickpress_taxonomies_menu_page' );
} else {
	add_action('init', 'kickpress_init_redirects');

	// Adds KickPress specific Styles and Scripts
	add_action( 'wp_enqueue_scripts', 'kickpress_enqueue_scripts' );
}

add_filter( 'the_posts', 'kickpress_terms_as_posts', 99, 2 );

function kickpress_terms_as_posts( $posts, $query ) {
	if ( is_admin() || ! $query->is_main_query() ) return $posts;

	global $kickpress_api;

	if ( ! empty( $kickpress_api->params['view'] ) && 'tax' == $kickpress_api->params['view'] ) {
		$taxonomy = $kickpress_api->params['view_alias'];

		if ( $tax = get_taxonomy( $taxonomy ) ) {
			$page  = intval( get_query_var( 'paged' ) );
			$limit = intval( get_query_var( 'posts_per_page' ) );

			if ( 0 < $page ) $page--;

			$terms = get_terms( $taxonomy, array(
				'number' => $limit,
				'offset' => $limit * $page,
				'hide_empty' => false,
				'search' => $kickpress_api->params['search']
			) );

			$fake_posts = array();

			foreach ( (array) $terms as $term ) {
				$fake_posts[] = (object) array(
					'ID'           => $term->term_id,
					'post_type'    => $term->taxonomy,
					'post_name'    => $term->slug,
					'post_title'   => $term->name,
					'post_content' => $term->description
				);
			}

			return $fake_posts;
		}
	}

	return $posts;
}

function kickpress_load_textdomain() {
	load_plugin_textdomain( 'kickpress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

function kickpress_activation() {
	global $wpdb, $wp_roles;

	if ( ! isset( $wp_roles ) )
		$wp_roles = new WP_Roles();

	foreach ( $wp_roles->get_names() as $key => $value ) {
		$role = get_role( $key );

		// Set the default capabilities for these based on the edit_posts capability
		foreach ( array( 'create_posts', 'edit_terms', 'edit_bookmarks', 'edit_notes', 'edit_tasks', 'edit_votes', 'edit_ratings' ) as $custom_cap ) {
			$role->add_cap( $custom_cap, $role->has_cap( 'edit_posts' ) );
		}
	}

	$wpdb->post_relationships = "{$wpdb->prefix}kp_post_relationships";
	$wpdb->oauth_consumers    = "{$wpdb->prefix}oauth_consumers";
	$wpdb->oauth_tokens       = "{$wpdb->base_prefix}auth_tokens";

	$charset = $collate = '';

	if ( ! empty( $wpdb->charset ) )
		$charset = "DEFAULT CHARACTER SET {$wpdb->charset}";

	if ( ! empty( $wpdb->collate ) )
		$collate = "COLLATE {$wpdb->collate}";

	// create table structure for relational connections
	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->post_relationships}` ("
	     . "`source_id` bigint(20) unsigned NOT NULL, "
	     . "`target_id` bigint(20) unsigned NOT NULL, "
	     . "`relationship` varchar(32) NOT NULL, "
	     . "PRIMARY KEY (`source_id`, `target_id`, `relationship`), "
	     . "KEY `target_id` (`target_id`), "
	     . "KEY `relationship` (`relationship`) "
	     . ") {$charset} {$collate}";

	$wpdb->query( $sql );

	// create table structure for oauth connections
	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->oauth_consumers}` ("
		. "  `blog_id` bigint(20) unsigned NOT NULL, "
		. "  `user_id` bigint(20) unsigned NOT NULL, "
		. "  `consumer_key` varchar(32) NOT NULL, "
		. "  `consumer_secret` varchar(40) NOT NULL, "
		. "  `callback_url` varchar(255) NOT NULL, "
		. "  `app_id` varchar(8) NOT NULL, "
		. "  `app_title` text NOT NULL, "
		. "  `app_description` text NOT NULL, "
		. "  `app_url` varchar(255) NOT NULL, "
		. "  `app_status` varchar(20) NOT NULL, "
		. "  PRIMARY KEY (`consumer_key`)"
		. ") {$charset} {$collate}";
	// For direct sql access to database:
	// CREATE TABLE IF NOT EXISTS `wp_oauth_consumers` (`blog_id` bigint(20) unsigned NOT NULL, `user_id` bigint(20) unsigned NOT NULL, `consumer_key` varchar(32) NOT NULL, `consumer_secret` varchar(40) NOT NULL, `callback_url` varchar(255) NOT NULL, `app_id` varchar(8) NOT NULL, `app_title` text NOT NULL, `app_description` text NOT NULL, `app_url` varchar(255) NOT NULL, `app_status` varchar(20) NOT NULL, PRIMARY KEY (`consumer_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	$wpdb->query( $sql );
	
	// create table structure for oauth tokens
	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->oauth_tokens}` ("
		. "  `user_id` bigint(20) unsigned NOT NULL, "
		. "  `consumer_key` varchar(32) NOT NULL, "
		. "  `token` varchar(32) NOT NULL, "
		. "  `secret` varchar(40) NOT NULL, "
		. "  `verifier` varchar(40) NOT NULL, "
		. "  `type` varchar(20) NOT NULL, "
		. "  `date` datetime NOT NULL, "
		. "  PRIMARY KEY (`token`), "
		. "  KEY `consumer_key` (`consumer_key`)"
		. ") {$charset} {$collate}";
	// For direct sql access to database:
	// CREATE TABLE IF NOT EXISTS `wp_oauth_tokens` (`user_id` bigint(20) unsigned NOT NULL, `consumer_key` varchar(32) NOT NULL, `token` varchar(32) NOT NULL, `secret` varchar(40) NOT NULL, `verifier` varchar(40) NOT NULL, `type` varchar(20) NOT NULL, `date` datetime NOT NULL, PRIMARY KEY (`token`), KEY `consumer_key` (`consumer_key`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;
	
	$wpdb->query( $sql );
}

function kickpress_deactivation() {

}

?>