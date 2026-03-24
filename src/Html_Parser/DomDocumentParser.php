<?php

namespace Slc\SeoLinksCrawler\Html_Parser;

use DOMDocument;
use Slc\SeoLinksCrawler\Contracts\HtmlParserInterface;

/**
 * DOMDocument-based HTML parser.
 */
class DomDocumentParser extends DOMDocument implements HtmlParserInterface {

	/**
	 * Load an HTML document from a string.
	 *
	 * @param string $html HTML content.
	 */
	public function load_html_document( $html ) {
		$this->loadHTML( $html, LIBXML_NOERROR | LIBXML_NOWARNING );
	}

	/**
	 * Extract all unique link href values from the loaded document.
	 *
	 * @return array List of href strings.
	 */
	public function gather_links() {
		$total_links = [];
		$get_tags    = $this->getElementsByTagName( 'a' );

		if ( $get_tags->length ) {
			foreach ( $get_tags as $tag ) {
				$total_links[] = $tag->getAttribute( 'href' );
			}
			$total_links = \apply_filters( 'slc_filter_all_links', array_unique( $total_links ) );
		}

		return $total_links;
	}
}
