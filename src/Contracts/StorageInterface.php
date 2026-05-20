<?php

namespace Slc\SeoLinksCrawler\Contracts;

defined( 'ABSPATH' ) || exit;

/**
 * Interface for storage of generated crawl artifacts.
 *
 * Implementations may persist artifacts on disk (non-VIP) or in the database /
 * object cache (VIP) depending on environment constraints.
 */
interface StorageInterface {

	/**
	 * Get a human-readable storage location identifier for error messages.
	 *
	 * For filesystem implementations this should be an absolute directory path.
	 * For VIP-safe implementations this may be a label such as "object cache".
	 *
	 * @return string
	 */
	public function get_location_label();

	/**
	 * Prepare storage for writes, if applicable.
	 *
	 * @return bool True if storage is ready.
	 */
	public function prepare();

	/**
	 * Whether the stored home snapshot artifact exists.
	 *
	 * @return bool
	 */
	public function home_snapshot_exists();

	/**
	 * Persist the home snapshot artifact.
	 *
	 * @param string|null $html Pre-fetched HTML, or null to fetch internally.
	 *
	 * @return bool
	 */
	public function save_home_snapshot( $html = null );

	/**
	 * Persist the sitemap artifact built from crawl results.
	 *
	 * @param array $links Internal links list.
	 *
	 * @return bool
	 */
	public function save_sitemap( array $links );

	/**
	 * Remove any previously generated artifacts.
	 *
	 * @return void
	 */
	public function clear_artifacts();
}
