<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Contracts\CacheInterface;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Cron\CrawlLock;
use Slc\SeoLinksCrawler\Cron\CrawlMeta;
use Slc\SeoLinksCrawler\Cron\CrawlOrchestrator;
use Slc\SeoLinksCrawler\Cron\CrawlScheduler;
use Slc\SeoLinksCrawler\Storage\StorageManager;

#[CoversClass( CrawlScheduler::class )]
class CrawlSchedulerTest extends TestCase {

	private $orchestrator;
	private $lock;
	private $meta;
	private $cache;
	private $filesystem;
	private $storage;
	private CrawlScheduler $scheduler;

	protected function setUp(): void {
		parent::setUp();

		$this->orchestrator = Mockery::mock( CrawlOrchestrator::class );
		$this->lock         = Mockery::mock( CrawlLock::class );
		$this->meta         = Mockery::mock( CrawlMeta::class );
		$this->cache        = Mockery::mock( CacheInterface::class );
		$this->filesystem   = Mockery::mock( FileSystemInterface::class );
		$this->storage      = Mockery::mock( StorageManager::class );

		$this->scheduler = new CrawlScheduler(
			$this->orchestrator,
			$this->lock,
			$this->meta,
			$this->cache,
			$this->filesystem,
			$this->storage
		);
	}

	public function test_register_hooks_adds_cron_action(): void {
		Actions\expectAdded( 'slc_crawl_internal_links_scheduler' )->once();
		$this->scheduler->register_hooks();
	}

	public function test_execute_returns_error_when_lock_unavailable(): void {
		$this->lock->shouldReceive( 'acquire' )->once()->andReturn( false );

		$result = $this->scheduler->execute();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertStringContainsString( 'already in progress', $result->get_error_message() );
	}

	public function test_execute_crawls_and_records_success(): void {
		$links        = [ 'https://example.com/page/' ];
		$crawl_result = [ 'links' => $links, 'file_error' => '' ];

		$this->lock->shouldReceive( 'acquire' )->once()->andReturn( true );
		$this->lock->shouldReceive( 'release' )->once();
		$this->meta->shouldReceive( 'update' )->once();
		$this->meta->shouldReceive( 'record_finished' )->once()->with( $crawl_result );
		$this->cache->shouldReceive( 'get_cache_data' )->once()->andReturn( false );
		$this->cache->shouldReceive( 'clean_up_cache' )->once();
		$this->storage->shouldReceive( 'get_home_html_path' )->once()->andReturn( '/path/home.html' );
		$this->storage->shouldReceive( 'get_sitemap_path' )->once()->andReturn( '/path/sitemap.html' );
		$this->filesystem->shouldReceive( 'delete_file' )->twice();
		$this->orchestrator->shouldReceive( 'crawl' )->once()->andReturn( $crawl_result );

		$result = $this->scheduler->execute();

		$this->assertSame( $crawl_result, $result );
	}

	public function test_execute_restores_cache_on_crawl_failure(): void {
		$previous_cache = [ 'https://example.com/old/' ];
		$wp_error       = new \WP_Error( 'crawl_error', 'Failed' );

		$this->lock->shouldReceive( 'acquire' )->once()->andReturn( true );
		$this->lock->shouldReceive( 'release' )->once();
		$this->meta->shouldReceive( 'update' )->once();
		$this->meta->shouldReceive( 'record_finished' )->once();
		$this->cache->shouldReceive( 'get_cache_data' )->once()->andReturn( $previous_cache );
		$this->cache->shouldReceive( 'clean_up_cache' )->once();
		$this->cache->shouldReceive( 'cache_data' )->once()->with( $previous_cache );
		$this->storage->shouldReceive( 'get_home_html_path' )->andReturn( '/path/home.html' );
		$this->storage->shouldReceive( 'get_sitemap_path' )->andReturn( '/path/sitemap.html' );
		$this->filesystem->shouldReceive( 'delete_file' )->twice();
		$this->orchestrator->shouldReceive( 'crawl' )->once()->andReturn( $wp_error );

		$result = $this->scheduler->execute();

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_execute_releases_lock_on_exception(): void {
		$this->lock->shouldReceive( 'acquire' )->once()->andReturn( true );
		$this->lock->shouldReceive( 'release' )->once();
		$this->meta->shouldReceive( 'update' )->twice();
		$this->cache->shouldReceive( 'get_cache_data' )->andReturn( false );
		$this->cache->shouldReceive( 'clean_up_cache' )->once();
		$this->storage->shouldReceive( 'get_home_html_path' )->andReturn( '/path/home.html' );
		$this->storage->shouldReceive( 'get_sitemap_path' )->andReturn( '/path/sitemap.html' );
		$this->filesystem->shouldReceive( 'delete_file' )->twice();

		$this->orchestrator
			->shouldReceive( 'crawl' )
			->once()
			->andThrow( new \Exception( 'Crash' ) );

		$result = $this->scheduler->execute();

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_schedule_creates_event_when_not_scheduled(): void {
		Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'slc_crawl_internal_links_scheduler' )
			->andReturn( false );

		Functions\expect( 'wp_schedule_event' )
			->once()
			->with(
				Mockery::type( 'int' ),
				'hourly',
				'slc_crawl_internal_links_scheduler'
			);

		CrawlScheduler::schedule();
	}

	public function test_schedule_skips_when_already_scheduled(): void {
		Functions\expect( 'wp_next_scheduled' )
			->once()
			->with( 'slc_crawl_internal_links_scheduler' )
			->andReturn( time() + 3600 );

		CrawlScheduler::schedule();
	}

	public function test_unschedule_clears_hook(): void {
		Functions\expect( 'wp_clear_scheduled_hook' )
			->once()
			->with( 'slc_crawl_internal_links_scheduler' );

		CrawlScheduler::unschedule();
	}
}
