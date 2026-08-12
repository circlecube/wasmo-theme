<?php
$_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';

if ( ! file_exists( "$_tests_dir/includes/functions.php" ) ) {
    echo "ERROR: wordpress-tests-lib not found at $_tests_dir\n";
    echo "Run: composer setup:wpunit\n";
    exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' );

require $_tests_dir . '/includes/bootstrap.php';
