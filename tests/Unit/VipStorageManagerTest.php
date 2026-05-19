<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Storage\VipStorageManager;

#[CoversClass( VipStorageManager::class )]
class VipStorageManagerTest extends TestCase {

	private $filesystem;
	private VipStorageManager $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->filesystem = Mockery::mock( FileSystemInterface::class );
		$this->manager    = new VipStorageManager( $this->filesystem );
	}

	public function test_get_location_label_returns_database_label(): void {
		$this->assertSame( 'database/object cache', $this->manager->get_location_label() );
	}

	public function test_prepare_returns_true(): void {
		$this->assertTrue( $this->manager->prepare() );
	}

	public function test_home_snapshot_exists_returns_true_when_post_found(): void {
		$post     = new \stdClass();
		$post->ID = 42;

		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-home', \OBJECT, 'slc_artifact' )
			->andReturn( $post );

		$this->assertTrue( $this->manager->home_snapshot_exists() );
	}

	public function test_home_snapshot_exists_returns_false_when_no_post(): void {
		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-home', \OBJECT, 'slc_artifact' )
			->andReturn( null );

		$this->assertFalse( $this->manager->home_snapshot_exists() );
	}

	public function test_save_home_snapshot_with_provided_html(): void {
		$html = '<html><body>Home</body></html>';

		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-home', \OBJECT, 'slc_artifact' )
			->andReturn( null );

		Functions\expect( 'wp_insert_post' )
			->once()
			->with(
				Mockery::on(
					function ( $arr ) use ( $html ) {
						return 'slc_artifact' === $arr['post_type']
							&& 'slc-home' === $arr['post_name']
							&& $html === $arr['post_content']
							&& 'publish' === $arr['post_status'];
					}
				),
				true
			)
			->andReturn( 1 );

		Functions\expect( 'wp_cache_set' )
			->once()
			->with( 'slc_artifact_home_html', $html, 'seo-links-crawler', 300 );

		$this->assertTrue( $this->manager->save_home_snapshot( $html ) );
	}

	public function test_save_home_snapshot_fetches_when_null(): void {
		Functions\stubs( [ 'get_home_url' => 'https://example.com' ] );

		$html = '<html><body>Fetched</body></html>';

		$this->filesystem
			->shouldReceive( 'fetch_url' )
			->once()
			->with( 'https://example.com' )
			->andReturn( $html );

		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-home', \OBJECT, 'slc_artifact' )
			->andReturn( null );

		Functions\expect( 'wp_insert_post' )
			->once()
			->andReturn( 1 );

		Functions\expect( 'wp_cache_set' )->once();

		$this->assertTrue( $this->manager->save_home_snapshot() );
	}

	public function test_save_home_snapshot_returns_false_when_no_html(): void {
		Functions\stubs( [ 'get_home_url' => 'https://example.com' ] );

		$this->filesystem
			->shouldReceive( 'fetch_url' )
			->once()
			->andReturn( false );

		$this->assertFalse( $this->manager->save_home_snapshot() );
	}

	public function test_save_home_snapshot_updates_existing_post(): void {
		$html         = '<html><body>Updated</body></html>';
		$existing     = new \stdClass();
		$existing->ID = 99;

		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-home', \OBJECT, 'slc_artifact' )
			->andReturn( $existing );

		Functions\expect( 'wp_update_post' )
			->once()
			->with(
				Mockery::on( function ( $arr ) {
					return 99 === $arr['ID'];
				} ),
				true
			)
			->andReturn( 99 );

		Functions\expect( 'wp_cache_set' )->once();

		$this->assertTrue( $this->manager->save_home_snapshot( $html ) );
	}

	public function test_save_home_snapshot_returns_false_on_wp_error(): void {
		$html = '<html><body>Fail</body></html>';

		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-home', \OBJECT, 'slc_artifact' )
			->andReturn( null );

		Functions\expect( 'wp_insert_post' )
			->once()
			->andReturn( new \WP_Error( 'insert_error', 'DB fail' ) );

		$this->assertFalse( $this->manager->save_home_snapshot( $html ) );
	}

	public function test_save_sitemap_creates_post_with_rendered_html(): void {
		$links = [ 'https://example.com/page/' ];

		$this->filesystem
			->shouldReceive( 'get_file_content' )
			->once()
			->andReturn( 'body { color: red; }' );

		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-sitemap', \OBJECT, 'slc_artifact' )
			->andReturn( null );

		Functions\expect( 'wp_insert_post' )
			->once()
			->with(
				Mockery::on(
					function ( $arr ) {
						return 'slc-sitemap' === $arr['post_name']
							&& str_contains( $arr['post_content'], 'Sitemap of home page' )
							&& str_contains( $arr['post_content'], 'https://example.com/page/' )
							&& str_contains( $arr['post_content'], 'body { color: red; }' );
					}
				),
				true
			)
			->andReturn( 2 );

		Functions\expect( 'wp_cache_set' )->once();

		$this->assertTrue( $this->manager->save_sitemap( $links ) );
	}

	public function test_save_sitemap_returns_false_on_insert_failure(): void {
		$this->filesystem
			->shouldReceive( 'get_file_content' )
			->once()
			->andReturn( '' );

		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-sitemap', \OBJECT, 'slc_artifact' )
			->andReturn( null );

		Functions\expect( 'wp_insert_post' )
			->once()
			->andReturn( new \WP_Error( 'insert_error', 'fail' ) );

		$this->assertFalse( $this->manager->save_sitemap( [ 'https://example.com/' ] ) );
	}

	public function test_clear_artifacts_deletes_posts_and_cache(): void {
		$home_post     = new \stdClass();
		$home_post->ID = 10;

		$sitemap_post     = new \stdClass();
		$sitemap_post->ID = 11;

		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-home', \OBJECT, 'slc_artifact' )
			->andReturn( $home_post );

		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-sitemap', \OBJECT, 'slc_artifact' )
			->andReturn( $sitemap_post );

		Functions\expect( 'wp_delete_post' )->once()->with( 10, true );
		Functions\expect( 'wp_delete_post' )->once()->with( 11, true );

		Functions\expect( 'wp_cache_delete' )
			->once()
			->with( 'slc_artifact_home_html', 'seo-links-crawler' );

		Functions\expect( 'wp_cache_delete' )
			->once()
			->with( 'slc_artifact_sitemap_html', 'seo-links-crawler' );

		$this->manager->clear_artifacts();
	}

	public function test_clear_artifacts_handles_missing_posts(): void {
		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-home', \OBJECT, 'slc_artifact' )
			->andReturn( null );

		Functions\expect( 'get_page_by_path' )
			->once()
			->with( 'slc-sitemap', \OBJECT, 'slc_artifact' )
			->andReturn( null );

		Functions\expect( 'wp_cache_delete' )
			->once()
			->with( 'slc_artifact_home_html', 'seo-links-crawler' );

		Functions\expect( 'wp_cache_delete' )
			->once()
			->with( 'slc_artifact_sitemap_html', 'seo-links-crawler' );

		$this->manager->clear_artifacts();
	}
}
