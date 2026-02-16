<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createUnsafeImmutable( dirname( __DIR__, 3 ) )->safeLoad();

$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: getenv( 'WP_PHPUNIT__DIR' );

if ( ! $_tests_dir ) {
	exit( 'WP_TESTS_DIR or WP_PHPUNIT__DIR are not set.' . PHP_EOL );
}

/**
 * The WordPress tests functions.
 *
 * Clearly, WP_TESTS_DIR should be the path to the WordPress PHPUnit tests checkout.
 *
 * We are loading this so that we can add our tests filter to load the plugin, using
 * tests_add_filter().
 *
 * @since 1.0.0
 */
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter( 'muplugins_loaded', function () {
	$plugin_file = dirname( __DIR__, 3 ) . '/plugin.php';
	include( $plugin_file );
	do_action( 'activate_' . plugin_basename( $plugin_file ) );
} );

/**
 * Sets up the WordPress test environment.
 *
 * We've got our action set up, so we can load this now, and viola, the tests begin.
 * Again, WordPress' PHPUnit test suite needs to be installed under the given path.
 *
 * @since 1.0.0
 */
require $_tests_dir . '/includes/bootstrap.php';

include( __DIR__ . '/export-testcase.php' );
include( __DIR__ . '/testcases/import.php' );
