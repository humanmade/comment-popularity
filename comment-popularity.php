<?php
/*
Plugin Name: Comment Popularity
Plugin URI: https://github.com/humanmade/comment-popularity
Description: Allow visitors to vote on comments.
Version: 1.1.1
Author: Human Made Limited
Author URI: http://humanmade.co.uk
Text Domain: comment-popularity
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;

// Main plugin class
require_once trailingslashit( __DIR__ ) . 'inc/class-comment-popularity.php';

add_action( 'plugins_loaded', array( 'HMN_Comment_Popularity', 'get_instance' ) );

register_activation_hook( __FILE__, array( 'HMN_Comment_Popularity', 'activate' ) );

// Template tags
include_once trailingslashit( __DIR__ ) . 'inc/helpers.php';

// Admin class
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {

	require_once plugin_dir_path( __FILE__ ) . 'admin/class-comment-popularity-admin.php';
	add_action( 'plugins_loaded', array( 'HMN_Comment_Popularity_Admin', 'get_instance' ) );

}

// Widgets
require_once plugin_dir_path( __FILE__ ) . 'inc/widgets/class-widget-most-voted.php';

add_action( 'widgets_init', function() {
	register_widget( 'HMN_CP_Widget_Most_Voted' );
} );
