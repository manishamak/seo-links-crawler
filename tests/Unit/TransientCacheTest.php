<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Cache\TransientCache;

#[CoversClass( TransientCache::class )]
class TransientCacheTest extends TestCase {

	private TransientCache $cache;

	protected function setUp(): void {
		parent::setUp();
		$this->cache = new TransientCache();
	}

	public function test_initiate_cache_is_noop(): void {
		$this->cache->initiate_cache();
		$this->assertTrue( true );
	}

	public function test_cache_data_sets_transient(): void {
		$data = [ 'https://example.com/about/' ];

		Functions\expect( 'set_transient' )
			->once()
			->with( 'slc_cached_home_connected_links', $data, 3600 )
			->andReturn( true );

		$this->assertTrue( $this->cache->cache_data( $data ) );
	}

	public function test_cache_data_returns_false_on_failure(): void {
		Functions\expect( 'set_transient' )
			->once()
			->andReturn( false );

		$this->assertFalse( $this->cache->cache_data( [ 'link' ] ) );
	}

	public function test_get_cache_data_returns_array_on_success(): void {
		$data = [ 'https://example.com/page/' ];

		Functions\expect( 'get_transient' )
			->once()
			->with( 'slc_cached_home_connected_links' )
			->andReturn( $data );

		$this->assertSame( $data, $this->cache->get_cache_data() );
	}

	public function test_get_cache_data_returns_false_when_empty(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'slc_cached_home_connected_links' )
			->andReturn( false );

		$this->assertFalse( $this->cache->get_cache_data() );
	}

	public function test_get_cache_data_returns_false_for_non_array(): void {
		Functions\expect( 'get_transient' )
			->once()
			->with( 'slc_cached_home_connected_links' )
			->andReturn( 'not an array' );

		$this->assertFalse( $this->cache->get_cache_data() );
	}

	public function test_clean_up_cache_deletes_transient(): void {
		Functions\expect( 'delete_transient' )
			->once()
			->with( 'slc_cached_home_connected_links' );

		$this->cache->clean_up_cache();
	}
}
