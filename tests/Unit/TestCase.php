<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base test case with Brain\Monkey setup/teardown and common WP stubs.
 */
abstract class TestCase extends PHPUnitTestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Auto-stub WordPress translation functions (__(), esc_html__(), etc.)
		// and escaping functions (esc_html(), esc_url(), esc_attr(), etc.)
		Functions\stubTranslationFunctions();
		Functions\stubEscapeFunctions();

		Functions\stubs(
			[
				'trailingslashit' => function ( $str ) {
					return rtrim( $str, '/' ) . '/';
				},
				'wp_json_encode'  => function ( $data, $options = 0, $depth = 512 ) {
					return json_encode( $data, $options, $depth );
				},
				'wp_parse_url'    => function ( $url, $component = -1 ) {
					if ( null === $url ) {
						return ( -1 === $component ) ? [] : null;
					}

					return ( -1 === $component )
						? parse_url( $url )
						: parse_url( $url, $component );
				},
				'absint'          => function ( $val ) {
					return abs( (int) $val );
				},
			]
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
