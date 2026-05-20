<?php

namespace Slc\SeoLinksCrawler\Contracts;

/**
 * Interface for HTML parsing operations.
 */
interface HtmlParserInterface {

	/**
	 * Load an HTML document from a string.
	 *
	 * @param string $html HTML content.
	 */
	public function load_html_document( $html );

	/**
	 * Extract all link href values from the loaded document.
	 *
	 * @return array List of href strings.
	 */
	public function gather_links();
}
