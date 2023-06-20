<?php
namespace Slc\SeoLinksCrawler;

use Slc\SeoLinksCrawler\File_Operation\WPFilesystem;
use Slc\SeoLinksCrawler\Html_Parser\DomDocumentParser;

/**
 *  Class for finding links from pages.
 **/
class LinksFinder {

	/**
	 * Instance of the WPFilesystem.
	 *
	 * @var WPFilesystem
	 */
	private $wp_filesystem;

	/**
	 * Instance of the DomDocumentParser.
	 *
	 * @var DomDocumentParser
	 */
	private $dom_document_parser;

	/**
	 * Constructor.
	 *
	 * @param WPFilesystem      $wp_filesystem       instance of WPFilesystem class.
	 * @param DomDocumentParser $dom_document_parser instance of DomDocumentParser class.
	 */
	public function __construct( WPFilesystem $wp_filesystem, DomDocumentParser $dom_document_parser ) {
		$this->wp_filesystem       = $wp_filesystem;
		$this->dom_document_parser = $dom_document_parser;
	}

	/**
	 * Check for internal link.
	 *
	 * @param  array $parsed_link  array of parts of url.
	 *
	 * @return boolean  true if link is internal
	 */
	public function is_internal_link( $parsed_link ) {
		$parsed_home_url = \wp_parse_url( \home_url() );
		if ( isset( $parsed_link['host'] ) && ! empty( $parsed_link['host'] ) && $parsed_home_url['host'] === $parsed_link['host'] ) {
			return true;
		}
		if ( empty( $parsed_link['scheme'] ) && empty( $parsed_link['host'] ) ) {
			return true;
		}
		if ( isset( $url['path'] ) && \strpos( $url['path'], $home_url['path'] ) === 0 ) {
			return true;
		}
		return false;
	}

	/**
	 * Generates internal links from given page.
	 *
	 * @param  string $page_url       page url which needs to scan.
	 *
	 * @return array  $internal_links list of internal links
	 */
	public function create_internal_links( $page_url ) {
		$internal_links = [];
		$file_content   = $this->wp_filesystem->get_file_content( $page_url );

		if ( ! $file_content ) {
			return new \WP_Error( 'scan_page_error', esc_html__( 'An error occurred while scanning the page. Please try again later.', 'seo-links-crawler' ) );
		}

		$this->dom_document_parser->loadHTMLDocument( $file_content );

		$links = $this->dom_document_parser->gather_links();
		if ( empty( $links ) ) {
			return new \WP_Error( 'no_links_found', esc_html__( 'No links found in the page.', 'seo-links-crawler' ) );
		}

		$internal_links = \apply_filters(
			'slc_filter_internal_links',
			array_filter(
				$links,
				function( $link ) {
					$parsed_link = \wp_parse_url( $link );
					return $this->is_internal_link( $parsed_link );
				}
			)
		);
		return $internal_links;
	}
}
