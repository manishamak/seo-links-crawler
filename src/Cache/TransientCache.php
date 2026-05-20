<?php

namespace Slc\SeoLinksCrawler\Cache;

use Slc\SeoLinksCrawler\Contracts\CacheInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Transient-based cache for crawl results.
 *
 * VIP Go environments typically provide shared object cache, making
 * transients a better fit than filesystem JSON writes.
 */
class TransientCache implements CacheInterface {

	/**
	 * Cache key for internal-link list.
	 *
	 * @var string
	 */
	const TRANSIENT_KEY = 'slc_cached_home_connected_links';

	/**
	 * Cache TTL (seconds). Matches "hourly" crawl cadence.
	 *
	 * @var int
	 */
	const TTL = 3600;

	/**
	 * {@inheritdoc}
	 */
	public function initiate_cache() {
		// No-op: transients are lazily created on first write.
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $data Crawl results data to cache.
	 *
	 * @return bool
	 */
	public function cache_data( $data ) {
		return (bool) set_transient( self::TRANSIENT_KEY, $data, self::TTL );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @return array|false
	 */
	public function get_cache_data() {
		$data = get_transient( self::TRANSIENT_KEY );

		return is_array( $data ) ? $data : false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function clean_up_cache() {
		delete_transient( self::TRANSIENT_KEY );
	}
}
