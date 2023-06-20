<?php

namespace Slc\SeoLinksCrawler\Cache;

use Slc\SeoLinksCrawler\File_Reader\FilesystemReader;

class FilesystemCache {
	private $filesystem;
	private $cache_directory = WP_CONTENT_DIR . '/slc-cache/';
	private $cache_file_path;

	public function __construct( FilesystemReader $filesystem ) {
		$this->filesystem      = $filesystem;
		$this->cache_file_path = $this->cache_directory . 'cached-home-connected-links.txt';
	}

	public function initiate_cache() {
		if ( ! is_dir( $this->cache_directory ) ) {
			wp_mkdir_p( $this->cache_directory );
		}
	}

	public function cache_data( $data ) {
		$serialized_data = serialize( $data );
		$status          = $this->filesystem->put_file_content( $this->cache_file_path, $serialized_data, FS_CHMOD_FILE );
		return $status;
	}

	public function get_cache_data() {
		$cachedData = $this->filesystem->get_file_content( $this->cache_file_path );

		return $cachedData ? unserialize( $cachedData ) : $cachedData;
	}

	public function clean_up_cache() {
		// $cache_file = $this->get_cache_file_path();
		// $expiration_time = 3600; // 1 hour
		// global $wp_filesystem;
		// WP_Filesystem();
		// && $wp_filesystem->mtime($cache_file) < (time() - $expiration_time)
		// if ( $wp_filesystem->exists($this->cache_file_path) ) {
		$this->filesystem->delete_file( $this->cache_file_path );
		// }
	}
}
