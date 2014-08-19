<?php
/**
 * Bootstrap the plugin unit testing environment.
 *
 * @package WordPress
 * @subpackage JSON API
 */
require_once getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/functions.php';
$plugin_path = basename( dirname( dirname( __FILE__ ) ) ) . '/comment-popularity.php';
// Activates this plugin in WordPress so it can be tested.
function _manually_load_plugin() {

	require dirname( dirname( __FILE__ ) ) . '/comment-popularity.php';
	HMN_Comment_Popularity::get_instance();

	// Make sure plugin is installed here ...
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
// If the develop repo location is defined (as WP_DEVELOP_DIR), use that
// location. Otherwise, we'll just assume that this plugin is installed in a
// WordPress develop SVN checkout.

if( false !== getenv( 'WP_DEVELOP_DIR' ) ) {
	require getenv( 'WP_DEVELOP_DIR' ) . '/tests/phpunit/includes/bootstrap.php';
} else {
	require '../../../../tests/phpunit/includes/bootstrap.php';
}
