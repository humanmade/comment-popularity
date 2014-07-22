<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN') )
	exit;

// Remove plugin settings
delete_option( 'comment_popularity_prefs' );

// Remove User meta
$args = array (
	'meta_query'     => array(
		array(
			'key'       => 'hmn_user_expert_status',
			'compare'   => 'EXISTS',
		),
	),
	'fields'         => 'all',
);

// The User Query
$user_query = new WP_User_Query( $args );

if ( ! empty( $user_query->results ) ) {

	foreach ( $user_query->results as $user ) {

		delete_user_meta( $user_id,  'hmn_user_expert_status' );

	}

}

$args = array (
	'meta_query'     => array(
		array(
			'key'       => 'hmn_user_karma',
			'compare'   => 'EXISTS',
		),
	),
	'fields'         => 'all',
);

// The User Query
$user_query = new WP_User_Query( $args );

if ( ! empty( $user_query->results ) ) {

	foreach ( $user_query->results as $user ) {

		delete_user_meta( $user_id,  'hmn_user_karma' );

	}

}