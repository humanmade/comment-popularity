<?php
/*
Plugin Name: Sankaku Comment Popularity
Plugin URI: https://github.com/slavicd/comment-popularity
Description: A fork of Human Made Limited's Comment Popularity plugin
Version: 1.3.5+slavicd.0.5.0
Author: Slavic Dragovtev,  Human Made Limited
Author URI: https://wordpress.org/plugins/comment-popularity/
Text Domain: comment-popularity
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;

// Check PHP version. We need at least 5.3.2.
if ( version_compare( phpversion(), '5.3.2', '<' ) ) {
	deactivate_plugins( plugin_basename( __FILE__ ) );
	wp_die( sprintf( __( 'This plugin requires PHP Version %s. Sorry about that.', 'comment-popularity' ), '5.3.2' ), 'Comment Popularity', array( 'back_link' => true ) );
}

// Main plugin class
require_once plugin_dir_path( __FILE__ ) . 'inc/class-comment-popularity.php';

register_activation_hook( __FILE__, array( 'CommentPopularity\HMN_Comment_Popularity', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CommentPopularity\HMN_Comment_Popularity', 'deactivate' ) );

function hmn_cp_init() {
	global $comment_popularity;

	$comment_popularity = CommentPopularity\HMN_Comment_Popularity::get_instance();

	if ( is_user_logged_in() ) {
		$visitor = new CommentPopularity\HMN_CP_Visitor_Member( get_current_user_id() );
	} else {
		$visitor = new CommentPopularity\HMN_CP_Visitor_Guest( $_SERVER['REMOTE_ADDR'] );
	}

	if ( ! ( $comment_popularity->get_visitor() instanceof CommentPopularity\HMN_CP_Visitor ) ) {
		$comment_popularity->set_visitor( $visitor );
	}

}
add_action( 'plugins_loaded', 'hmn_cp_init' );

// Admin class
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once plugin_dir_path( __FILE__ ) . 'admin/class-comment-popularity-admin.php';
	add_action( 'plugins_loaded', array( 'CommentPopularity\HMN_Comment_Popularity_Admin', 'get_instance' ) );

}

// Template tags
include_once plugin_dir_path( __FILE__ ) . 'inc/helpers.php';

require_once plugin_dir_path( __FILE__ ) . 'inc/upgrade.php';

