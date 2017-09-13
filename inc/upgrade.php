<?php

use CommentPopularity\HMN_Comment_Popularity;

defined( 'ABSPATH' ) || exit;

/**
 * Main controller for version upgrade routines.
 */
function hmn_cp_trigger_upgrades() {

	// Get latest version stored in DB option.
	$hmn_cp_plugin_version = get_option( 'hmn_cp_plugin_version' );

	// No need to do anything if current version.
	if ( version_compare( $hmn_cp_plugin_version, HMN_Comment_Popularity::HMN_CP_PLUGIN_VERSION, '>=' ) ) {
		return;
	}

	// If DB option not present, treat as new install.
	if ( ! $hmn_cp_plugin_version ) {
		update_option( 'hmn_cp_plugin_version', HMN_Comment_Popularity::HMN_CP_PLUGIN_VERSION );
	}

	// Do we have users with the old option key? Upgrade in batches of 100.
	$user_query = new WP_User_Query( array(
		'number'   => 100,
		'meta_key' => 'comments_voted_on',
	) );
	$users = $user_query->get_results();
	if ( ! empty( $users ) ) {
		hmn_cp_v121_upgrade( $users );
	}
}

add_action( 'admin_init', 'hmn_cp_trigger_upgrades' );

/**
 * Copy unprefixed option value to new prefixed option.
 *
 * @param array $users Users who need to be upgraded.
 */
function hmn_cp_v121_upgrade( array $users ) {

	foreach ( $users as $user ) {

		$old_option = get_user_option( 'comments_voted_on', $user->ID );

		if ( ! $old_option ) {
			continue;
		}

		update_user_option( $user->ID, 'hmn_comments_voted_on', $old_option );
		delete_user_option( $user->ID, 'comments_voted_on', true );

	}

}
