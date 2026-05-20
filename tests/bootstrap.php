<?php
/**
 * PHPUnit bootstrap file.
 *
 * Loads Composer autoloader, defines WordPress constants, and provides
 * lightweight stubs for WP_Error and is_wp_error() that are not
 * automatically handled by Brain\Monkey.
 */

$autoloader = dirname( __DIR__ ) . '/vendor/autoload.php';

if ( ! file_exists( $autoloader ) ) {
	echo 'Please run `composer install` before running tests.' . PHP_EOL;
	exit( 1 );
}

// WordPress constants required by source files (many check `defined('ABSPATH') || exit`).
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/' );
}
if ( ! defined( 'SLC_PLUGIN_PATH' ) ) {
	define( 'SLC_PLUGIN_PATH', dirname( __DIR__ ) );
}
if ( ! defined( 'SLC_PLUGIN_FILE' ) ) {
	define( 'SLC_PLUGIN_FILE', dirname( __DIR__ ) . '/seo-links-crawler.php' );
}
if ( ! defined( 'FS_CHMOD_FILE' ) ) {
	define( 'FS_CHMOD_FILE', 0644 );
}
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

require_once $autoloader;

// Lightweight WP_Error stub — Brain\Monkey does not provide one.
if ( ! class_exists( 'WP_Error' ) ) {
	// phpcs:disable
	class WP_Error {
		private $code;
		private $message;
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = (string) $code;
			$this->message = (string) $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
	// phpcs:enable
}

// is_wp_error() checks instanceof and is not auto-stubbed by Brain\Monkey.
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return ( $thing instanceof WP_Error );
	}
}
