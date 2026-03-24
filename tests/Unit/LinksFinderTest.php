<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use Brain\Monkey\Functions;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Contracts\HtmlParserInterface;
use Slc\SeoLinksCrawler\LinksFinder;

/**
 * @covers \Slc\SeoLinksCrawler\LinksFinder
 */
class LinksFinderTest extends TestCase {

	/**
	 * @var LinksFinder
	 */
	private $finder;

	/**
	 * @var FileSystemInterface|\Mockery\MockInterface
	 */
	private $filesystem;

	/**
	 * @var HtmlParserInterface|\Mockery\MockInterface
	 */
	private $parser;

	protected function setUp(): void {
		parent::setUp();

		$this->filesystem = \Mockery::mock( FileSystemInterface::class );
		$this->parser     = \Mockery::mock( HtmlParserInterface::class );
		$this->finder     = new LinksFinder( $this->filesystem, $this->parser );

		Functions\stubs( [
			'home_url'          => 'https://example.com',
			'wp_parse_url'      => function ( $url, $component = -1 ) {
				return ( -1 === $component )
					? parse_url( $url )
					: parse_url( $url, $component );
			},
			'trailingslashit'   => function ( $str ) {
				return rtrim( $str, '/' ) . '/';
			},
			'esc_html__'        => function ( $text ) {
				return $text;
			},
		] );
	}

	// ------------------------------------------------------------------
	// should_skip_href
	// ------------------------------------------------------------------

	/** @dataProvider skippable_hrefs_provider */
	public function test_should_skip_href_returns_true( $href ) {
		$this->assertTrue( $this->finder->should_skip_href( $href ) );
	}

	public function skippable_hrefs_provider() {
		return [
			'empty string'       => [ '' ],
			'whitespace only'    => [ '   ' ],
			'fragment only'      => [ '#section' ],
			'fragment with path' => [ '#' ],
			'mailto'             => [ 'mailto:user@example.com' ],
			'tel'                => [ 'tel:+1234567890' ],
			'javascript'         => [ 'javascript:void(0)' ],
			'data uri'           => [ 'data:text/html,<h1>hi</h1>' ],
			'ftp'                => [ 'ftp://files.example.com/doc' ],
		];
	}

	/** @dataProvider non_skippable_hrefs_provider */
	public function test_should_skip_href_returns_false( $href ) {
		$this->assertFalse( $this->finder->should_skip_href( $href ) );
	}

	public function non_skippable_hrefs_provider() {
		return [
			'absolute http'     => [ 'http://example.com/page' ],
			'absolute https'    => [ 'https://example.com/page' ],
			'root relative'     => [ '/about' ],
			'plain relative'    => [ 'about' ],
			'protocol relative' => [ '//example.com/page' ],
		];
	}

	// ------------------------------------------------------------------
	// is_internal_link
	// ------------------------------------------------------------------

	/** @dataProvider internal_links_provider */
	public function test_is_internal_link_returns_true( $href ) {
		$this->assertTrue( $this->finder->is_internal_link( $href ) );
	}

	public function internal_links_provider() {
		return [
			'same host https'          => [ 'https://example.com/about' ],
			'same host http'           => [ 'http://example.com/contact' ],
			'same host uppercase'      => [ 'https://EXAMPLE.COM/page' ],
			'root relative'            => [ '/about' ],
			'plain relative'           => [ 'about/team' ],
			'protocol relative same'   => [ '//example.com/page' ],
			'home url itself'          => [ 'https://example.com/' ],
			'trailing slash'           => [ 'https://example.com/about/' ],
			'with query string'        => [ 'https://example.com/search?q=test' ],
		];
	}

	/** @dataProvider external_links_provider */
	public function test_is_internal_link_returns_false( $href ) {
		$this->assertFalse( $this->finder->is_internal_link( $href ) );
	}

	public function external_links_provider() {
		return [
			'different host'           => [ 'https://other-site.com/page' ],
			'protocol relative other'  => [ '//other-site.com/page' ],
			'ftp scheme'               => [ 'ftp://example.com/file' ],
			'malformed url'            => [ 'http:///bad' ],
		];
	}

	// ------------------------------------------------------------------
	// is_internal_link — subdirectory install
	// ------------------------------------------------------------------

	public function test_is_internal_link_with_subdirectory_install() {
		Functions\stubs( [
			'home_url' => 'https://example.com/blog',
		] );

		$this->assertTrue( $this->finder->is_internal_link( 'https://example.com/blog/post-1' ) );
		$this->assertTrue( $this->finder->is_internal_link( 'https://example.com/blog/' ) );
		$this->assertFalse( $this->finder->is_internal_link( 'https://example.com/other-path' ) );
	}

	// ------------------------------------------------------------------
	// normalize_url
	// ------------------------------------------------------------------

	public function test_normalize_url_strips_fragment() {
		$this->assertStringNotContainsString(
			'#',
			$this->finder->normalize_url( 'https://example.com/page#section' )
		);
	}

	public function test_normalize_url_lowercases_host_and_scheme() {
		$result = $this->finder->normalize_url( 'HTTPS://EXAMPLE.COM/Page' );
		$this->assertStringStartsWith( 'https://example.com/', $result );
	}

	public function test_normalize_url_preserves_query_string() {
		$result = $this->finder->normalize_url( 'https://example.com/search?q=test' );
		$this->assertStringContainsString( '?q=test', $result );
	}

	public function test_normalize_url_adds_trailing_slash() {
		$result = $this->finder->normalize_url( 'https://example.com/page' );
		$this->assertStringEndsWith( '/', explode( '?', $result )[0] );
	}

	// ------------------------------------------------------------------
	// create_internal_links (integration of all filtering)
	// ------------------------------------------------------------------

	public function test_create_internal_links_filters_and_deduplicates() {
		Functions\stubs( [
			'apply_filters' => function ( $tag, $value ) {
				return $value;
			},
		] );

		$html = '<html><body><a href="/about">A</a></body></html>';

		$this->filesystem
			->shouldReceive( 'fetch_url' )
			->never();

		$this->parser
			->shouldReceive( 'load_html_document' )
			->once()
			->with( $html );

		$this->parser
			->shouldReceive( 'gather_links' )
			->once()
			->andReturn( [
				'https://example.com/about',
				'https://example.com/about#section',
				'https://example.com/contact',
				'https://other.com/page',
				'mailto:test@example.com',
				'#fragment',
				'',
				'/relative-page',
			] );

		$result = $this->finder->create_internal_links( 'https://example.com', $html );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );

		foreach ( $result as $link ) {
			$this->assertStringContainsString( 'example.com', $link );
			$this->assertStringNotContainsString( '#', $link );
		}

		$this->assertCount( count( $result ), array_unique( $result ), 'Results should be deduplicated' );
	}

	public function test_create_internal_links_fetches_url_when_no_content() {
		Functions\stubs( [
			'apply_filters' => function ( $tag, $value ) {
				return $value;
			},
		] );

		$html = '<html><body><a href="/page">Link</a></body></html>';

		$this->filesystem
			->shouldReceive( 'fetch_url' )
			->once()
			->with( 'https://example.com' )
			->andReturn( $html );

		$this->parser
			->shouldReceive( 'load_html_document' )
			->once();

		$this->parser
			->shouldReceive( 'gather_links' )
			->once()
			->andReturn( [ '/page' ] );

		$result = $this->finder->create_internal_links( 'https://example.com' );

		$this->assertIsArray( $result );
		$this->assertNotEmpty( $result );
	}

	public function test_create_internal_links_returns_wp_error_on_empty_content() {
		$this->filesystem
			->shouldReceive( 'fetch_url' )
			->once()
			->andReturn( false );

		$result = $this->finder->create_internal_links( 'https://example.com' );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}
}
