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
}
