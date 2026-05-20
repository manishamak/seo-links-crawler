<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Cron\CrawlMeta;

#[CoversClass( CrawlMeta::class )]
class CrawlMetaTest extends TestCase {

	private CrawlMeta $meta;

	protected function setUp(): void {
		parent::setUp();
		$this->meta = new CrawlMeta();
	}

	public function test_update_calls_update_option(): void {
		$data = [
			'started_at' => 1700000000,
			'status'     => 'running',
			'source'     => 'admin',
		];

		Functions\expect( 'update_option' )
			->once()
			->with( 'slc_last_crawl', $data, false );

		$this->meta->update( $data );
	}

	public function test_record_finished_saves_success_metadata(): void {
		$result = [
			'links'      => [ 'https://a.com/', 'https://b.com/' ],
			'file_error' => '',
		];

		Functions\expect( 'update_option' )
			->once()
			->with(
				'slc_last_crawl',
				\Mockery::on(
					function ( $data ) {
						return 'success' === $data['status']
							&& 2 === $data['link_count']
							&& '' === $data['error']
							&& isset( $data['finished_at'] );
					}
				),
				false
			);

		$this->meta->record_finished( $result );
	}

	public function test_record_finished_saves_error_metadata(): void {
		$error = new \WP_Error( 'crawl_error', 'Something broke' );

		Functions\expect( 'update_option' )
			->once()
			->with(
				'slc_last_crawl',
				\Mockery::on(
					function ( $data ) {
						return 'error' === $data['status']
							&& 'Something broke' === $data['error']
							&& isset( $data['finished_at'] );
					}
				),
				false
			);

		$this->meta->record_finished( $error );
	}

	public function test_get_last_returns_stored_option(): void {
		$stored = [
			'status'     => 'success',
			'link_count' => 5,
		];

		Functions\expect( 'get_option' )
			->once()
			->with( 'slc_last_crawl', [] )
			->andReturn( $stored );

		$this->assertSame( $stored, CrawlMeta::get_last() );
	}
}
