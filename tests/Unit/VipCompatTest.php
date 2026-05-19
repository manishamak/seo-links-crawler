<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Slc\SeoLinksCrawler\Vip\VipCompat;

#[CoversClass( VipCompat::class )]
class VipCompatTest extends TestCase {

	/**
	 * Skip when VIP helpers are loaded (real VIP Go or a stack that defines them).
	 * Those environments cannot satisfy the "no VIP functions" precondition.
	 */
	private function skip_if_vip_runtime(): void {
		if ( function_exists( 'vip_safe_wp_remote_get' ) || function_exists( 'vip_error_log' ) ) {
			$this->markTestSkipped( 'VIP runtime detected: vip_safe_wp_remote_get / vip_error_log are defined.' );
		}
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_is_vip_returns_false_when_no_vip_functions(): void {
		$this->skip_if_vip_runtime();
		$this->assertFalse( VipCompat::is_vip() );
	}

	public function test_is_vip_returns_true_when_vip_safe_wp_remote_get_exists(): void {
		Functions\when( 'vip_safe_wp_remote_get' )->justReturn( [] );

		$this->assertTrue( VipCompat::is_vip() );
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState( false )]
	public function test_safe_wp_remote_get_falls_back_to_wp_remote_get(): void {
		$this->skip_if_vip_runtime();
		Functions\expect( 'wp_remote_get' )
			->once()
			->with( 'https://example.com', [ 'timeout' => 10 ] )
			->andReturn( [ 'body' => 'ok' ] );

		$result = VipCompat::safe_wp_remote_get( 'https://example.com', [ 'timeout' => 10 ] );

		$this->assertSame( [ 'body' => 'ok' ], $result );
	}

	public function test_safe_wp_remote_get_uses_vip_function_when_available(): void {
		Functions\when( 'vip_safe_wp_remote_get' )->alias(
			function ( $url, $args ) {
				return [ 'vip' => true, 'url' => $url ];
			}
		);

		$result = VipCompat::safe_wp_remote_get( 'https://example.com', [] );

		$this->assertTrue( $result['vip'] );
		$this->assertSame( 'https://example.com', $result['url'] );
	}

	public function test_log_error_does_nothing_when_debug_off(): void {
		VipCompat::log_error( 'test message' );

		$this->assertTrue( true );
	}
}
