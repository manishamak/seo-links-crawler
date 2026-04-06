<?php

namespace Slc\SeoLinksCrawler\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use Slc\SeoLinksCrawler\Html_Parser\DomDocumentParser;

#[CoversClass( DomDocumentParser::class )]
class DomDocumentParserTest extends TestCase {

	private DomDocumentParser $parser;

	protected function setUp(): void {
		parent::setUp();
		$this->parser = new DomDocumentParser();
	}

	public function test_gather_links_extracts_hrefs(): void {
		$html = '<html><body>'
			. '<a href="https://example.com/about">About</a>'
			. '<a href="/contact">Contact</a>'
			. '</body></html>';

		$this->parser->load_html_document( $html );
		$links = $this->parser->gather_links();

		$this->assertCount( 2, $links );
		$this->assertContains( 'https://example.com/about', $links );
		$this->assertContains( '/contact', $links );
	}

	public function test_gather_links_returns_empty_for_no_anchors(): void {
		$this->parser->load_html_document( '<html><body><p>No links here</p></body></html>' );
		$links = $this->parser->gather_links();

		$this->assertEmpty( $links );
	}

	public function test_gather_links_deduplicates_hrefs(): void {
		$html = '<html><body>'
			. '<a href="/page">Link 1</a>'
			. '<a href="/page">Link 2</a>'
			. '<a href="/other">Link 3</a>'
			. '</body></html>';

		$this->parser->load_html_document( $html );
		$links = $this->parser->gather_links();

		$this->assertCount( 2, $links );
	}

	public function test_load_html_document_handles_malformed_html(): void {
		$html = '<html><body><a href="/valid">Link</a><p>Unclosed paragraph';

		$this->parser->load_html_document( $html );
		$links = $this->parser->gather_links();

		$this->assertCount( 1, $links );
		$this->assertContains( '/valid', $links );
	}
}
