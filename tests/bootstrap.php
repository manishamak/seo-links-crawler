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

// Lightweight WP_Error stub for isolated unit tests.
// PHPUnit/Brain Monkey doesn't automatically define WP_Error.
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		/**
		 * @var string
		 */
		private $code;

		/**
		 * @var string
		 */
		private $message;

		public function __construct( $code = '', $message = '' ) {
			$this->code    = (string) $code;
			$this->message = (string) $message;
		}

		public function get_error_message() {
			return $this->message;
		}
	}
}
