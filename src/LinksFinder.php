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
	 * File system abstraction for read/write and HTTP fetch operations.
	 *
	 * @var FileSystemInterface
	 */
	private $wp_filesystem;

	/**
	 * HTML parser for DOM document manipulation.
	 *
	 * @var HtmlParserInterface
	 */
	private $dom_document_parser;

	/**
	 * URI schemes that are non-navigational and should be ignored.
	 *
	 * @var string[]
	 */
	private static $skip_schemes = [
		'mailto',
		'tel',
		'javascript',
		'data',
		'ftp',
		'blob',
	];

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
	 * Determine whether an href value should be skipped entirely.
	 *
	 * Rejects empty strings, fragment-only anchors, and non-navigational
	 * schemes such as mailto:, tel:, javascript:, etc.
	 *
	 * @param string $href Raw href attribute value.
	 *
	 * @return bool
	 */
	public function should_skip_href( $href ) {
		$href = \trim( $href );

		if ( '' === $href || '#' === $href[0] ) {
			return true;
		}

		$colon_pos = \strpos( $href, ':' );
		if ( false !== $colon_pos ) {
			$scheme = \strtolower( \substr( $href, 0, $colon_pos ) );
			if ( \in_array( $scheme, self::$skip_schemes, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a link belongs to this WordPress site.
	 *
	 * Handles absolute URLs, protocol-relative URLs, root-relative paths,
	 * plain relative paths, and subdirectory installs. Host comparison is
	 * case-insensitive.
	 *
	 * @param string $href Raw href attribute value.
	 *
	 * @return bool True if the link is internal.
	 */
	public function is_internal_link( $href ) {
		$href = \trim( $href );

		$parsed_home = \wp_parse_url( \home_url() );
		$home_host   = \strtolower( $parsed_home['host'] );
		$home_path   = isset( $parsed_home['path'] ) ? \trailingslashit( $parsed_home['path'] ) : '/';

		$parsed = \wp_parse_url( $href );
		if ( false === $parsed ) {
			return false;
		}

		// Protocol-relative URL (//example.com/...).
		if ( ! isset( $parsed['scheme'] ) && 0 === \strpos( $href, '//' ) ) {
			return isset( $parsed['host'] )
				&& \strtolower( $parsed['host'] ) === $home_host
				&& $this->path_belongs_to_home( $parsed, $home_path );
		}

		// Absolute URL with scheme.
		if ( isset( $parsed['scheme'] ) ) {
			if ( ! \in_array( \strtolower( $parsed['scheme'] ), [ 'http', 'https' ], true ) ) {
				return false;
			}
			if ( ! isset( $parsed['host'] ) || \strtolower( $parsed['host'] ) !== $home_host ) {
				return false;
			}
			return $this->path_belongs_to_home( $parsed, $home_path );
		}

		// No scheme, no host → root-relative (/page) or plain relative (page).
		if ( ! isset( $parsed['host'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check whether a parsed URL's path starts with the home path prefix.
	 *
	 * Accounts for subdirectory installs where home_url() may contain a
	 * non-root path such as /blog/.
	 *
	 * @param array  $parsed    Parsed URL components.
	 * @param string $home_path Home path with trailing slash (e.g. '/' or '/blog/').
	 *
	 * @return bool
	 */
	private function path_belongs_to_home( $parsed, $home_path ) {
		if ( '/' === $home_path ) {
			return true;
		}

		$link_path = isset( $parsed['path'] ) ? $parsed['path'] : '/';

		return 0 === \strpos( $link_path, $home_path )
			|| \rtrim( $link_path, '/' ) === \rtrim( $home_path, '/' );
	}

	/**
	 * Normalize a URL for consistent storage and deduplication.
	 *
	 * Strips fragments, lowercases scheme and host, and applies a consistent
	 * trailing-slash policy via trailingslashit().
	 *
	 * @param string $url Absolute URL.
	 *
	 * @return string Normalized URL.
	 */
	public function normalize_url( $url ) {
		$frag_pos = \strpos( $url, '#' );
		if ( false !== $frag_pos ) {
			$url = \substr( $url, 0, $frag_pos );
		}

		$parsed = \wp_parse_url( $url );
		if ( false === $parsed || ! isset( $parsed['host'] ) ) {
			return $url;
		}

		$scheme = isset( $parsed['scheme'] ) ? \strtolower( $parsed['scheme'] ) : 'http';
		$host   = \strtolower( $parsed['host'] );
		$path   = isset( $parsed['path'] ) ? $parsed['path'] : '/';
		$query  = isset( $parsed['query'] ) ? '?' . $parsed['query'] : '';

		$path = \trailingslashit( $path );

		return $scheme . '://' . $host . $path . $query;
	}

	/**
	 * Check whether a URL is relative (no scheme and no protocol-relative prefix).
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
	 * Generate a list of unique internal links from the given page.
	 *
	 * Filters out non-navigational hrefs, identifies internal links,
	 * converts relative URLs to absolute, normalizes, and deduplicates.
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
			return new \WP_Error( 'scan_page_error', \esc_html__( 'An error occurred while scanning the page. Please check home page template.', 'seo-links-crawler' ) );
		}

		$this->dom_document_parser->load_html_document( $file_content );

		$links = $this->dom_document_parser->gather_links();
		if ( empty( $links ) ) {
			return new \WP_Error( 'no_links_found', \esc_html__( 'No links found in the page.', 'seo-links-crawler' ) );
		}

		$internal_links = \apply_filters(
			'slc_filter_internal_links',
			\array_filter(
				$links,
				function ( $link ) {
					if ( $this->should_skip_href( $link ) ) {
						return false;
					}
					return $this->is_internal_link( $link );
				}
			)
		);

		$absolute_internal_links = \array_map(
			function ( $element ) {
				if ( $this->is_relative_url( $element ) ) {
					return $this->create_absolute_url( $element );
				}
				return $element;
			},
			$internal_links
		);

		$normalized = \array_map( [ $this, 'normalize_url' ], $absolute_internal_links );

		return \array_values( \array_unique( $normalized ) );
	}
}
