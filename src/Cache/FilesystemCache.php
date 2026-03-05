<?php

namespace Slc\SeoLinksCrawler\Cache;

use Slc\SeoLinksCrawler\File_Operation\WPFilesystem;

defined( 'ABSPATH' ) || exit;

/**
 *  Class for temporary storage(cache).
 **/
class FilesystemCache {

	/**
	 * Instance of the WPFilesystem.
	 *
	 * @var WPFilesystem
	 */
	private $filesystem;

	/**
	 * Path of cache directory.
	 *
	 * @var string
	 */
	private $cache_directory;

	/**
	 * Full path of cache file.
	 *
	 * @var string
	 */
	private $cache_file_path;

	/**
	 * Constructor.
	 *
	 * @param WPFilesystem $filesystem Instance of WPFilesystem class.
	 */
	public function __construct( WPFilesystem $filesystem ) {
		$this->filesystem      = $filesystem;
		$this->cache_directory = WP_CONTENT_DIR . '/slc-cache/';
		$this->cache_file_path = $this->cache_directory . 'cached-home-connected-links.json';
	}

	/**
	 * Create cache directory if it does not exist.
	 */
	public function initiate_cache() {
		if ( ! is_dir( $this->cache_directory ) ) {
			wp_mkdir_p( $this->cache_directory );
		}
	}

	/**
	 * Store data in the cache file as JSON.
	 *
	 * @param array $data Data to be stored in cache.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function cache_data( $data ) {
		$json = wp_json_encode( $data );

		if ( false === $json ) {
			return false;
		}

		return $this->filesystem->put_file_content( $this->cache_file_path, $json, FS_CHMOD_FILE );
	}

	/**
	 * Read data from cache file.
	 *
	 * @return array|false Cached data array on success, false on failure.
	 */
	public function get_cache_data() {
		$raw = $this->filesystem->get_file_content( $this->cache_file_path );

		if ( ! $raw ) {
			return false;
		}

		$data = json_decode( $raw, true );

		return is_array( $data ) ? $data : false;
	}

	/**
	 * Delete cache file.
	 */
	public function clean_up_cache() {
		$this->filesystem->delete_file( $this->cache_file_path );
	}
}
