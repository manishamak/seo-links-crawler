<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Cron\CrawlLock;

#[CoversClass( CrawlLock::class )]
class CrawlLockTest extends TestCase {

	private CrawlLock $lock;

	protected function setUp(): void {
		parent::setUp();
		$this->lock = new CrawlLock();
	}

	public function test_acquire_returns_true_when_not_locked(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'slc_crawl_lock' )
			->andReturn( false );

		Functions\expect( 'set_transient' )
			->once()
			->with( 'slc_crawl_lock', \Mockery::type( 'int' ), 300 );

		$this->assertTrue( $this->lock->acquire() );
	}

	public function test_acquire_returns_false_when_already_locked(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'slc_crawl_lock' )
			->andReturn( time() );

		$this->assertFalse( $this->lock->acquire() );
	}

	public function test_release_deletes_transient(): void {
		Functions\expect( 'delete_transient' )
			->once()
			->with( 'slc_crawl_lock' );

		$this->lock->release();
	}

	public function test_is_locked_returns_true_when_lock_exists(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'slc_crawl_lock' )
			->andReturn( time() );

		$this->assertTrue( CrawlLock::is_locked() );
	}

	public function test_is_locked_returns_false_when_no_lock(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'slc_crawl_lock' )
			->andReturn( false );

		$this->assertFalse( CrawlLock::is_locked() );
	}

	// ---- VIP (object cache) paths -----------------------------------------

	public function test_vip_acquire_uses_wp_cache_add(): void {
		Functions\when( 'vip_safe_wp_remote_get' )->justReturn( [] );

		Functions\expect( 'wp_cache_add' )
			->once()
			->with( 'slc_crawl_lock', \Mockery::type( 'int' ), 'seo-links-crawler', 300 )
			->andReturn( true );

		$this->assertTrue( $this->lock->acquire() );
	}

	public function test_vip_acquire_returns_false_when_cache_add_fails(): void {
		Functions\when( 'vip_safe_wp_remote_get' )->justReturn( [] );

		Functions\expect( 'wp_cache_add' )
			->once()
			->andReturn( false );

		$this->assertFalse( $this->lock->acquire() );
	}

	public function test_vip_release_uses_wp_cache_delete(): void {
		Functions\when( 'vip_safe_wp_remote_get' )->justReturn( [] );

		Functions\expect( 'wp_cache_delete' )
			->once()
			->with( 'slc_crawl_lock', 'seo-links-crawler' );

		$this->lock->release();
	}

	public function test_vip_is_locked_uses_wp_cache_get(): void {
		Functions\when( 'vip_safe_wp_remote_get' )->justReturn( [] );

		Functions\expect( 'wp_cache_get' )
			->once()
			->with( 'slc_crawl_lock', 'seo-links-crawler' )
			->andReturn( time() );

		$this->assertTrue( CrawlLock::is_locked() );
	}

	public function test_vip_is_locked_returns_false_when_cache_empty(): void {
		Functions\when( 'vip_safe_wp_remote_get' )->justReturn( [] );

		Functions\expect( 'wp_cache_get' )
			->once()
			->with( 'slc_crawl_lock', 'seo-links-crawler' )
			->andReturn( false );

		$this->assertFalse( CrawlLock::is_locked() );
	}
}
