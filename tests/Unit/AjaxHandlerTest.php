<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Admin\AjaxHandler;
use Slc\SeoLinksCrawler\Contracts\CacheInterface;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Cron\CrawlLock;
use Slc\SeoLinksCrawler\Cron\CrawlMeta;
use Slc\SeoLinksCrawler\Cron\CrawlOrchestrator;
use Slc\SeoLinksCrawler\Storage\StorageManager;

#[CoversClass( AjaxHandler::class )]
class AjaxHandlerTest extends TestCase {

	private $orchestrator;
	private $lock;
	private $meta;
	private $cache;
	private $filesystem;
	private $storage;
	private AjaxHandler $handler;

	protected function setUp(): void {
		parent::setUp();

		$this->orchestrator = Mockery::mock( CrawlOrchestrator::class );
		$this->lock         = Mockery::mock( CrawlLock::class );
		$this->meta         = Mockery::mock( CrawlMeta::class );
		$this->cache        = Mockery::mock( CacheInterface::class );
		$this->filesystem   = Mockery::mock( FileSystemInterface::class );
		$this->storage      = Mockery::mock( StorageManager::class );

		$this->handler = new AjaxHandler(
			$this->orchestrator,
			$this->lock,
			$this->meta,
			$this->cache,
			$this->filesystem,
			$this->storage
		);

		Functions\when( 'wp_send_json_success' )->alias(
			function ( $data = null ) {
				throw new WpDieException( [ 'success' => true, 'data' => $data ] );
			}
		);
		Functions\when( 'wp_send_json_error' )->alias(
			function ( $data = null ) {
				throw new WpDieException( [ 'success' => false, 'data' => $data ] );
			}
		);
		Functions\when( 'check_ajax_referer' )->justReturn( true );
	}

	// ---- register_hooks ---------------------------------------------------

	public function test_register_hooks_adds_ajax_actions(): void {
		Actions\expectAdded( 'wp_ajax_slc_admin_display_links' )->once();
		Actions\expectAdded( 'wp_ajax_slc_crawl_status' )->once();
		Actions\expectAdded( 'wp_ajax_slc_clear_cache' )->once();

		$this->handler->register_hooks();
	}

	// ---- handle_crawl -----------------------------------------------------

	public function test_handle_crawl_rejects_without_permission(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		try {
			$this->handler->handle_crawl();
			$this->fail( 'Expected WpDieException' );
		} catch ( WpDieException $e ) {
			$this->assertFalse( $e->response['success'] );
		}
	}

	public function test_handle_crawl_rejects_when_locked(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		$this->lock->shouldReceive( 'acquire' )->once()->andReturn( false );

		try {
			$this->handler->handle_crawl();
			$this->fail( 'Expected WpDieException' );
		} catch ( WpDieException $e ) {
			$this->assertFalse( $e->response['success'] );
		}
	}

	public function test_handle_crawl_returns_error_on_crawl_failure(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		$this->lock->shouldReceive( 'acquire' )->once()->andReturn( true );
		$this->lock->shouldReceive( 'release' )->once();
		$this->meta->shouldReceive( 'update' )->twice();

		$this->orchestrator
			->shouldReceive( 'crawl' )
			->once()
			->andReturn( new \WP_Error( 'crawl_error', 'Oops' ) );

		try {
			$this->handler->handle_crawl();
			$this->fail( 'Expected WpDieException' );
		} catch ( WpDieException $e ) {
			$this->assertFalse( $e->response['success'] );
			$this->assertSame( 'Oops', $e->response['data'] );
		}
	}

	public function test_handle_crawl_returns_success(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		$links = [ 'https://example.com/about/' ];

		$this->lock->shouldReceive( 'acquire' )->once()->andReturn( true );
		$this->lock->shouldReceive( 'release' )->once();
		$this->meta->shouldReceive( 'update' )->twice();

		$this->orchestrator
			->shouldReceive( 'crawl' )
			->once()
			->andReturn( [ 'links' => $links, 'file_error' => '' ] );

		try {
			$this->handler->handle_crawl();
			$this->fail( 'Expected WpDieException' );
		} catch ( WpDieException $e ) {
			$this->assertTrue( $e->response['success'] );
			$this->assertSame( $links, $e->response['data']['result'] );
		}
	}

	// ---- handle_status ----------------------------------------------------

	public function test_handle_status_returns_lock_and_meta(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\stubs(
			[
				'get_transient' => false,
				'get_option'    => [],
			]
		);

		try {
			$this->handler->handle_status();
			$this->fail( 'Expected WpDieException' );
		} catch ( WpDieException $e ) {
			$this->assertTrue( $e->response['success'] );
			$this->assertArrayHasKey( 'is_locked', $e->response['data'] );
			$this->assertArrayHasKey( 'meta', $e->response['data'] );
		}
	}

	// ---- handle_clear_cache -----------------------------------------------

	public function test_handle_clear_cache_clears_all_artifacts(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->cache->shouldReceive( 'clean_up_cache' )->once();
		$this->storage->shouldReceive( 'get_home_html_path' )->once()->andReturn( '/path/home.html' );
		$this->storage->shouldReceive( 'get_sitemap_path' )->once()->andReturn( '/path/sitemap.html' );
		$this->filesystem->shouldReceive( 'delete_file' )->twice();

		try {
			$this->handler->handle_clear_cache();
			$this->fail( 'Expected WpDieException' );
		} catch ( WpDieException $e ) {
			$this->assertTrue( $e->response['success'] );
		}
	}
}
