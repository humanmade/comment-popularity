<?php

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/srv/www/wordpress-develop.dev/tests';

require_once $_tests_dir . '/phpunit/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../comment-popularity.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/phpunit/includes/bootstrap.php';
require dirname( __FILE__ ) . '/testcase.php';

