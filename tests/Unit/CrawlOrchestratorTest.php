<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Contracts\CacheInterface;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Contracts\LinksFinderInterface;
use Slc\SeoLinksCrawler\Contracts\StorageInterface;
use Slc\SeoLinksCrawler\Cron\CrawlOrchestrator;

#[CoversClass( CrawlOrchestrator::class )]
class CrawlOrchestratorTest extends TestCase {

	private $filesystem;
	private $links_finder;
	private $cache;
	private $storage;
	private CrawlOrchestrator $orchestrator;

	protected function setUp(): void {
		parent::setUp();

		$this->filesystem   = Mockery::mock( FileSystemInterface::class );
		$this->links_finder = Mockery::mock( LinksFinderInterface::class );
		$this->cache        = Mockery::mock( CacheInterface::class );
		$this->storage      = Mockery::mock( StorageInterface::class );

		$this->orchestrator = new CrawlOrchestrator(
			$this->filesystem,
			$this->links_finder,
			$this->cache,
			$this->storage
		);

		Functions\stubs( [ 'get_home_url' => 'https://example.com' ] );
	}

	public function test_crawl_returns_cached_links_when_available(): void {
		$cached = [ 'https://example.com/about/', 'https://example.com/contact/' ];

		$this->cache->shouldReceive( 'initiate_cache' )->once();
		$this->cache->shouldReceive( 'get_cache_data' )->once()->andReturn( $cached );
		$this->storage->shouldReceive( 'prepare' )->once()->andReturn( true );
		$this->storage->shouldReceive( 'get_location_label' )->andReturn( '/uploads/slc' );
		$this->storage->shouldReceive( 'home_snapshot_exists' )->andReturn( true );
		$this->storage->shouldReceive( 'save_sitemap' )->once()->with( $cached )->andReturn( true );

		$this->filesystem->shouldReceive( 'fetch_url' )->never();
		$this->links_finder->shouldReceive( 'create_internal_links' )->never();

		$result = $this->orchestrator->crawl();

		$this->assertIsArray( $result );
		$this->assertSame( $cached, $result['links'] );
		$this->assertEmpty( $result['file_error'] );
	}

	public function test_crawl_fetches_and_parses_when_no_cache(): void {
		$links = [ 'https://example.com/page/' ];

		$this->cache->shouldReceive( 'initiate_cache' )->once();
		$this->cache->shouldReceive( 'get_cache_data' )->once()->andReturn( false );
		$this->cache->shouldReceive( 'cache_data' )->once()->with( $links )->andReturn( true );
		$this->storage->shouldReceive( 'prepare' )->once()->andReturn( true );
		$this->storage->shouldReceive( 'get_location_label' )->andReturn( '/uploads/slc' );
		$this->storage->shouldReceive( 'home_snapshot_exists' )->andReturn( false );
		$this->storage->shouldReceive( 'save_home_snapshot' )->andReturn( true );
		$this->storage->shouldReceive( 'save_sitemap' )->with( $links )->andReturn( true );

		$this->filesystem
			->shouldReceive( 'fetch_url' )
			->once()
			->with( 'https://example.com' )
			->andReturn( '<html><body>Home</body></html>' );

		$this->links_finder
			->shouldReceive( 'create_internal_links' )
			->once()
			->andReturn( $links );

		$result = $this->orchestrator->crawl();

		$this->assertIsArray( $result );
		$this->assertSame( $links, $result['links'] );
	}

	public function test_crawl_returns_wp_error_when_links_finder_fails(): void {
		$wp_error = new \WP_Error( 'scan_page_error', 'Template missing' );

		$this->cache->shouldReceive( 'initiate_cache' )->once();
		$this->cache->shouldReceive( 'get_cache_data' )->once()->andReturn( false );
		$this->storage->shouldReceive( 'prepare' )->once()->andReturn( true );
		$this->storage->shouldReceive( 'get_location_label' )->andReturn( '/uploads/slc' );

		$this->filesystem->shouldReceive( 'fetch_url' )->once()->andReturn( '<html></html>' );
		$this->links_finder->shouldReceive( 'create_internal_links' )->once()->andReturn( $wp_error );

		$result = $this->orchestrator->crawl();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'Template missing', $result->get_error_message() );
	}

	public function test_crawl_returns_wp_error_when_no_links_found(): void {
		$this->cache->shouldReceive( 'initiate_cache' )->once();
		$this->cache->shouldReceive( 'get_cache_data' )->once()->andReturn( false );
		$this->storage->shouldReceive( 'prepare' )->once()->andReturn( true );
		$this->storage->shouldReceive( 'get_location_label' )->andReturn( '/uploads/slc' );

		$this->filesystem->shouldReceive( 'fetch_url' )->once()->andReturn( '<html></html>' );
		$this->links_finder->shouldReceive( 'create_internal_links' )->once()->andReturn( [] );

		$result = $this->orchestrator->crawl();

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_crawl_collects_file_creation_errors(): void {
		$links = [ 'https://example.com/page/' ];

		$this->cache->shouldReceive( 'initiate_cache' )->once();
		$this->cache->shouldReceive( 'get_cache_data' )->once()->andReturn( false );
		$this->cache->shouldReceive( 'cache_data' )->once()->andReturn( false );
		$this->storage->shouldReceive( 'prepare' )->once()->andReturn( true );
		$this->storage->shouldReceive( 'get_location_label' )->andReturn( '/uploads/slc' );
		$this->storage->shouldReceive( 'home_snapshot_exists' )->andReturn( false );
		$this->storage->shouldReceive( 'save_home_snapshot' )->andReturn( false );
		$this->storage->shouldReceive( 'save_sitemap' )->andReturn( false );

		$this->filesystem->shouldReceive( 'fetch_url' )->once()->andReturn( '<html></html>' );
		$this->links_finder->shouldReceive( 'create_internal_links' )->once()->andReturn( $links );

		$result = $this->orchestrator->crawl();

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result['file_error'] );
	}
}
