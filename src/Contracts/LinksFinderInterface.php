<?php

namespace Slc\SeoLinksCrawler\Contracts;

/**
 * Interface for finding internal links on a page.
 */
interface LinksFinderInterface {

	/**
	 * Generate a list of internal links from the given page.
	 *
	 * @param string      $page_url     URL of the page to scan.
	 * @param string|null $file_content Pre-fetched HTML content, or null to fetch automatically.
	 *
	 * @return array|\WP_Error List of internal link URLs, or WP_Error on failure.
	 */
	public function create_internal_links( $page_url, $file_content = null );
}
