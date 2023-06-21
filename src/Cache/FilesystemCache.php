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
	private $cache_directory = WP_CONTENT_DIR . '/slc-cache/';

	/**
	 * Full Path of cache file.
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
		$this->cache_file_path = $this->cache_directory . 'cached-home-connected-links.txt';
	}

	/**
	 * Create cache directory if not exists.
	 */
	public function initiate_cache() {
		if ( ! is_dir( $this->cache_directory ) ) {
			wp_mkdir_p( $this->cache_directory );
		}
	}

	/**
	 * Store data in the cache file.
	 *
	 * @param  array $data   data to be stored in cache.
	 *
	 * @return boolean $status True on success, false on failure.
	 */
	public function cache_data( $data ) {
		$serialized_data = maybe_serialize( $data );
		$status          = $this->filesystem->put_file_content( $this->cache_file_path, $serialized_data, FS_CHMOD_FILE );
		return $status;
	}

	/**
	 * Reads data from cache file.
	 *
	 * @return array|false $cachedData cache data on success, false on failure.
	 */
	public function get_cache_data() {
		$cached_data = $this->filesystem->get_file_content( $this->cache_file_path );
		return $cached_data ? maybe_unserialize( $cached_data ) : $cached_data;
	}

	/**
	 * Delete cache file.
	 */
	public function clean_up_cache() {
		$this->filesystem->delete_file( $this->cache_file_path );
	}
}
