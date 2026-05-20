<?php

namespace Slc\SeoLinksCrawler\Contracts;

/**
 * Interface for cache operations.
 */
interface CacheInterface {

	/**
	 * Initialize the cache storage.
	 */
	public function initiate_cache();

	/**
	 * Store data in cache.
	 *
	 * @param array $data Data to cache.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function cache_data( $data );

	/**
	 * Retrieve cached data.
	 *
	 * @return array|false Cached data on success, false if not available.
	 */
	public function get_cache_data();

	/**
	 * Remove all cached data.
	 */
	public function clean_up_cache();
}
