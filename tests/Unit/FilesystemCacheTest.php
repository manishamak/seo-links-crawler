<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Cache\FilesystemCache;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;

#[CoversClass( FilesystemCache::class )]
class FilesystemCacheTest extends TestCase {

	private $filesystem;
	private FilesystemCache $cache;

	protected function setUp(): void {
		parent::setUp();

		Functions\stubs(
			[
				'wp_upload_dir' => function () {
					return [ 'basedir' => '/tmp/wp-uploads' ];
				},
			]
		);

		$this->filesystem = Mockery::mock( FileSystemInterface::class );
		$this->cache      = new FilesystemCache( $this->filesystem );
	}

	public function test_initiate_cache_creates_directory_when_missing(): void {
		Functions\expect( 'wp_mkdir_p' )
			->once()
			->with( '/tmp/wp-uploads/seo-links-crawler/' );

		$this->cache->initiate_cache();
	}

	public function test_cache_data_writes_json(): void {
		$data = [ 'https://example.com/about/', 'https://example.com/contact/' ];
		$json = json_encode( $data );

		$this->filesystem
			->shouldReceive( 'put_file_content' )
			->once()
			->with(
				'/tmp/wp-uploads/seo-links-crawler/cached-home-connected-links.json',
				$json,
				FS_CHMOD_FILE
			)
			->andReturn( true );

		$this->assertTrue( $this->cache->cache_data( $data ) );
	}

	public function test_cache_data_returns_false_when_write_fails(): void {
		$this->filesystem
			->shouldReceive( 'put_file_content' )
			->once()
			->andReturn( false );

		$this->assertFalse( $this->cache->cache_data( [ 'link' ] ) );
	}

	public function test_get_cache_data_returns_array_on_success(): void {
		$data = [ 'https://example.com/page1/' ];

		$this->filesystem
			->shouldReceive( 'get_file_content' )
			->once()
			->with( '/tmp/wp-uploads/seo-links-crawler/cached-home-connected-links.json' )
			->andReturn( json_encode( $data ) );

		$this->assertSame( $data, $this->cache->get_cache_data() );
	}

	public function test_get_cache_data_returns_false_when_file_missing(): void {
		$this->filesystem
			->shouldReceive( 'get_file_content' )
			->once()
			->andReturn( false );

		$this->assertFalse( $this->cache->get_cache_data() );
	}

	public function test_get_cache_data_returns_false_on_invalid_json(): void {
		$this->filesystem
			->shouldReceive( 'get_file_content' )
			->once()
			->andReturn( 'not valid json{{{' );

		$this->assertFalse( $this->cache->get_cache_data() );
	}

	public function test_clean_up_cache_deletes_file(): void {
		$this->filesystem
			->shouldReceive( 'delete_file' )
			->once()
			->with( '/tmp/wp-uploads/seo-links-crawler/cached-home-connected-links.json' );

		$this->cache->clean_up_cache();
	}
}
