<?php
/*
Plugin Name: Comment Popularity
Plugin URI: https://github.com/humanmade/comment-popularity
Description: Allow visitors to vote on comments.
Version: 1.0.0
Author: Human Made Limited
Author URI: http://humanmade.co.uk
Text Domain: comment-popularity
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;

require_once trailingslashit( __DIR__ ) . 'inc/class-comment-popularity.php';

add_action( 'plugins_loaded', array( 'HMN_Comment_Popularity', 'get_instance' ) );

include_once trailingslashit( __DIR__ ) . 'inc/helpers.php';
