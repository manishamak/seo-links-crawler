<?php

namespace Slc\SeoLinksCrawler\Html_Parser;

use DomDocument;

/**
 *  Class for parsing the HTML.
 **/
class DomDocumentParser extends DOMDocument {

	/**
	 * Parses the HTML from string.
	 *
	 * @param string $html HTML contained in the string.
	 */
	public function loadHTMLDocument( $html ) {
		$this->loadHTML( $html, LIBXML_NOERROR | LIBXML_NOWARNING );
	}

	/**
	 * Parses the HTML from string.
	 *
	 * @return array $total_links contains all links present in the page.
	 */
	public function gather_links() {
		$total_links = [];
		$get_tags    = $this->getElementsByTagName( 'a' );
		if ( $get_tags->length ) {
			foreach ( $get_tags as $tag ) {
				array_push( $total_links, $tag->getAttribute( 'href' ) );
			}
			$total_links = \apply_filters( 'slc_filter_all_links', array_unique( $total_links ) );
		}
		return $total_links;
	}
}
