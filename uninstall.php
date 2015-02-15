<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin settings
delete_option( 'comment_popularity_prefs' );
delete_option( 'hmn_cp_plugin_version' );
delete_option( 'hmn_cp_guests_logged_votes' );

global $wpdb;

// Remove User meta
$args = array(
	'meta_query' => array(
		array(
			'key'     => $wpdb->get_blog_prefix() . 'hmn_user_expert_status',
			'compare' => 'EXISTS',
		),
	),
	'fields'     => 'all',
);

// Delete user expert status
$user_query = new WP_User_Query( $args );

if ( ! empty( $user_query->results ) ) {

	foreach ( $user_query->results as $user ) {

		delete_user_meta( $user->ID, 'hmn_user_expert_status' );
		delete_user_option( $user->ID, 'hmn_user_expert_status' );

	}

}

$args = array(
	'meta_query' => array(
		array(
			'key'     => $wpdb->get_blog_prefix() . 'hmn_user_karma',
			'compare' => 'EXISTS',
		),
	),
	'fields'     => 'all',
);

$user_query = new WP_User_Query( $args );

if ( ! empty( $user_query->results ) ) {

	foreach ( $user_query->results as $user ) {

		delete_user_meta( $user->ID, 'hmn_user_karma' );
		delete_user_option( $user->ID, 'hmn_user_karma' );

	}

}

$args = array(
	'meta_query' => array(
		array(
			'key'     => $wpdb->get_blog_prefix() .  'hmn_comments_voted_on',
			'compare' => 'EXISTS',
		),
	),
	'fields'     => 'all',
);

$user_query = new WP_User_Query( $args );

if ( ! empty( $user_query->results ) ) {

	foreach ( $user_query->results as $user ) {

		delete_user_option( $user->ID, 'hmn_comments_voted_on' );
	}

}

// Select all comments with karma > 0, and reset value to zero.

$wpdb->query(
	$wpdb->prepare(
		"UPDATE wp_comments SET comment_karma=0 WHERE comment_karma > %d", 0
	)
);

// Remove custom capabilities
require_once plugin_dir_path( __FILE__ ) . 'inc/class-comment-popularity.php';
$cp_plugin = CommentPopularity\HMN_Comment_Popularity::get_instance();
