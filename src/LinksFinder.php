<?php

namespace Slc\SeoLinksCrawler;

use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Contracts\HtmlParserInterface;
use Slc\SeoLinksCrawler\Contracts\LinksFinderInterface;

/**
 * Finds internal links from a given page.
 */
class LinksFinder implements LinksFinderInterface {

	/**
	 * Instance of the FileSystemInterface.
	 *
	 * @var FileSystemInterface
	 */
	private $wp_filesystem;

	/**
	 * Instance of the HtmlParserInterface.
	 *
	 * @var HtmlParserInterface
	 */
	private $dom_document_parser;

	/**
	 * Constructor.
	 *
	 * @param FileSystemInterface $wp_filesystem       File system instance.
	 * @param HtmlParserInterface $dom_document_parser HTML parser instance.
	 */
	public function __construct( FileSystemInterface $wp_filesystem, HtmlParserInterface $dom_document_parser ) {
		$this->wp_filesystem       = $wp_filesystem;
		$this->dom_document_parser = $dom_document_parser;
	}

	/**
	 * Check if a parsed link is internal.
	 *
	 * @param array $parsed_link Parsed URL components.
	 *
	 * @return bool True if the link is internal.
	 */
	public function is_internal_link( $parsed_link ) {
		$parsed_home_url = \wp_parse_url( \home_url() );

		if ( isset( $parsed_link['host'] ) && ! empty( $parsed_link['host'] ) && $parsed_home_url['host'] === $parsed_link['host'] ) {
			return true;
		}

		if ( empty( $parsed_link['scheme'] ) && empty( $parsed_link['host'] ) && ! empty( $parsed_link['path'] ) ) {
			return true;
		}

		if ( isset( $parsed_link['path'] ) && \strpos( $parsed_link['path'], $parsed_home_url['path'] ) === 0 ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether a URL is relative.
	 *
	 * @param string $url URL string to check.
	 *
	 * @return bool True when the URL is relative.
	 */
	public function is_relative_url( $url ) {
		return ( \strpos( $url, 'http' ) !== 0 && \strpos( $url, '//' ) !== 0 );
	}

	/**
	 * Convert a relative path to an absolute URL based on the home URL.
	 *
	 * @param string|null $path Optional path string.
	 *
	 * @return string Absolute URL.
	 */
	public function create_absolute_url( $path = null ) {
		$path      = \wp_parse_url( $path, \PHP_URL_PATH );
		$url_parts = \wp_parse_url( \home_url() );

		$base_url = \trailingslashit( $url_parts['scheme'] . '://' . $url_parts['host'] );

		if ( ! \is_null( $path ) ) {
			$base_url .= \ltrim( $path, '/' );
		}

		return $base_url;
	}

	/**
	 * Generate a list of internal links from the given page.
	 *
	 * @param string      $page_url     URL of the page to scan.
	 * @param string|null $file_content Pre-fetched HTML content, or null to fetch automatically.
	 *
	 * @return array|\WP_Error List of internal link URLs, or WP_Error on failure.
	 */
	public function create_internal_links( $page_url, $file_content = null ) {
		if ( null === $file_content ) {
			$file_content = $this->wp_filesystem->fetch_url( $page_url );
		}

		if ( ! $file_content ) {
			return new \WP_Error( 'scan_page_error', esc_html__( 'An error occurred while scanning the page. Please check home page template.', 'seo-links-crawler' ) );
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
				function ( $link ) {
					$parsed_link = \wp_parse_url( $link );
					return $this->is_internal_link( $parsed_link );
				}
			)
		);

		$absolute_internal_links = array_map(
			function ( $element ) {
				if ( $this->is_relative_url( $element ) ) {
					return $this->create_absolute_url( $element );
				}
				return $element;
			},
			$internal_links
		);

		return $absolute_internal_links;
	}
}
