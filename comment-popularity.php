<?php
/*
Plugin Name: Comment Popularity
Description: Allow visitors to vote on comments.
Version: 1.0
Author: Human Made
Author URI: http://hm.md
License: GPLv2
*/

defined( 'ABSPATH' ) || exit;

require_once trailingslashit( __DIR__ ) . 'inc/class-comment-popularity.php';

add_action( 'plugins_loaded', array( 'HMN_Comment_Popularity', 'get_instance' ) );
