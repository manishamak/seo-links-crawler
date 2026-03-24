<?php
/**
 * PHPUnit bootstrap file.
 *
 * Loads Composer autoloader and Brain\Monkey setup.
 */

$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	echo 'Please run `composer install` before running tests.' . PHP_EOL;
	exit( 1 );
}

require_once $autoloader;
