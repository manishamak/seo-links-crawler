<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Storage\StorageManager;

#[CoversClass( StorageManager::class )]
class StorageManagerTest extends TestCase {

	private $filesystem;
	private StorageManager $manager;

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
		$this->manager    = new StorageManager( $this->filesystem );
	}

	public function test_get_directory_returns_uploads_path(): void {
		$this->assertSame(
			'/tmp/wp-uploads/seo-links-crawler',
			$this->manager->get_directory()
		);
	}

	public function test_get_sitemap_path(): void {
		$this->assertSame(
			'/tmp/wp-uploads/seo-links-crawler/sitemap.html',
			$this->manager->get_sitemap_path()
		);
	}

	public function test_get_home_html_path(): void {
		$this->assertSame(
			'/tmp/wp-uploads/seo-links-crawler/home.html',
			$this->manager->get_home_html_path()
		);
	}

	public function test_ensure_directory_calls_wp_mkdir_p_when_not_exists(): void {
		Functions\expect( 'wp_mkdir_p' )
			->once()
			->with( '/tmp/wp-uploads/seo-links-crawler' )
			->andReturn( true );

		$this->assertTrue( $this->manager->ensure_directory() );
	}

	public function test_save_home_html_writes_provided_content(): void {
		Functions\when( 'wp_mkdir_p' )->justReturn( true );

		$html = '<html><body>Hello</body></html>';

		$this->filesystem
			->shouldReceive( 'put_file_content' )
			->once()
			->with( '/tmp/wp-uploads/seo-links-crawler/home.html', $html )
			->andReturn( true );

		$this->assertTrue( $this->manager->save_home_html( $html ) );
	}

	public function test_save_home_html_fetches_when_null(): void {
		Functions\stubs( [ 'get_home_url' => 'https://example.com' ] );
		Functions\when( 'wp_mkdir_p' )->justReturn( true );

		$html = '<html><body>Fetched</body></html>';

		$this->filesystem
			->shouldReceive( 'fetch_url' )
			->once()
			->with( 'https://example.com' )
			->andReturn( $html );

		$this->filesystem
			->shouldReceive( 'put_file_content' )
			->once()
			->andReturn( true );

		$this->assertTrue( $this->manager->save_home_html() );
	}

	public function test_save_home_html_returns_false_on_empty_content(): void {
		Functions\stubs( [ 'get_home_url' => 'https://example.com' ] );
		Functions\when( 'wp_mkdir_p' )->justReturn( true );

		$this->filesystem
			->shouldReceive( 'fetch_url' )
			->once()
			->andReturn( false );

		$this->assertFalse( $this->manager->save_home_html() );
	}

	public function test_save_sitemap_html_skips_when_file_exists(): void {
		$this->filesystem
			->shouldReceive( 'file_exists' )
			->once()
			->with( '/tmp/wp-uploads/seo-links-crawler/sitemap.html' )
			->andReturn( true );

		$this->assertTrue( $this->manager->save_sitemap_html( [ 'link1' ] ) );
	}

	public function test_save_sitemap_html_generates_file(): void {
		Functions\when( 'wp_mkdir_p' )->justReturn( true );

		$this->filesystem
			->shouldReceive( 'file_exists' )
			->with( '/tmp/wp-uploads/seo-links-crawler/sitemap.html' )
			->andReturn( false );

		$this->filesystem
			->shouldReceive( 'get_file_content' )
			->once()
			->andReturn( 'body { color: red; }' );

		$this->filesystem
			->shouldReceive( 'put_file_content' )
			->once()
			->with(
				'/tmp/wp-uploads/seo-links-crawler/sitemap.html',
				Mockery::on(
					function ( $html ) {
						return str_contains( $html, 'body { color: red; }' )
							&& str_contains( $html, 'https://example.com/page/' )
							&& str_contains( $html, 'Sitemap of home page' );
					}
				)
			)
			->andReturn( true );

		$result = $this->manager->save_sitemap_html( [ 'https://example.com/page/' ] );

		$this->assertTrue( $result );
	}
}
