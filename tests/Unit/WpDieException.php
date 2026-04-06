<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

/**
 * Exception thrown by wp_send_json_success/error stubs to halt
 * execution in AJAX handler tests, mimicking wp_die() behaviour.
 */
class WpDieException extends \Exception {

	/**
	 * The JSON response payload passed to wp_send_json_*.
	 *
	 * @var mixed
	 */
	public $response;

	/**
	 * @param mixed $response Response data.
	 */
	public function __construct( $response = null ) {
		$this->response = $response;
		parent::__construct( 'wp_send_json called' );
	}
}
