<?php

use CommentPopularity\HMN_Comment_Popularity;

defined( 'ABSPATH' ) || exit;

/**
 * Main controller for version upgrade routines.
 */
function hmn_cp_trigger_upgrades() {

	// Get actual plugin version.
	$current_version = HMN_Comment_Popularity::HMN_CP_PLUGIN_VERSION;

	// Get latest version stored in DB option.
	$hmn_cp_plugin_version = get_option( 'hmn_cp_plugin_version' );

	// If the option doesn't exist, then we are upgrading from a version older than 1.2.1
	if ( ! $hmn_cp_plugin_version ) {
		$hmn_cp_plugin_version = '1.2.0';

		// Start tracking versions.
		add_option( 'hmn_cp_plugin_version', $hmn_cp_plugin_version );
	}

	// Determine if we need to run upgrade routine for versions earlier than 1.2.1
	if ( version_compare( $hmn_cp_plugin_version, $current_version, '<' ) ) {
		hmn_cp_v121_upgrade();
	}

	// Bump the version number in the DB.
	update_option( 'hmn_cp_plugin_version', HMN_Comment_Popularity::HMN_CP_PLUGIN_VERSION );
}
add_action( 'admin_init', 'hmn_cp_trigger_upgrades' );

/**
 * Copy unprefixed option value to new prefixed option.
 */
function hmn_cp_v121_upgrade() {

	$users = get_users();

	foreach( $users as $user ) {

		$hmn_comments_voted_on = get_user_option( 'comments_voted_on', $user->ID );

		if ( ! $hmn_comments_voted_on )
			continue;

		update_user_option( $user->ID, 'hmn_comments_voted_on', $hmn_comments_voted_on );
		delete_user_option( $user->ID, 'comments_voted_on', true );

	}

}
