<?php

namespace Slc\SeoLinksCrawler\Cron;

use Slc\SeoLinksCrawler\Vip\VipCompat;

defined( 'ABSPATH' ) || exit;

/**
 * Transient-based lock to prevent concurrent crawl runs.
 *
 * A TTL ensures that a crashed crawl cannot block future runs
 * indefinitely — the lock self-expires after LOCK_TTL seconds.
 */
class CrawlLock {

	const TRANSIENT_KEY = 'slc_crawl_lock';
	const TTL           = 300;
	const CACHE_GROUP   = 'seo-links-crawler';

	/**
	 * Attempt to acquire the crawl lock.
	 *
	 * @return bool True if the lock was acquired, false if already held.
	 */
	public function acquire() {
		// On VIP, use an atomic object-cache add to avoid race conditions across nodes.
		if ( VipCompat::is_vip() && function_exists( 'wp_cache_add' ) ) {
			// Use a literal TTL to satisfy VIPWPCS cache-expiry sniffs.
			return (bool) wp_cache_add( self::TRANSIENT_KEY, time(), self::CACHE_GROUP, 300 );
		}

		if ( get_transient( self::TRANSIENT_KEY ) ) {
			return false;
		}

		set_transient( self::TRANSIENT_KEY, time(), self::TTL );

		return true;
	}

	/**
	 * Release the crawl lock.
	 */
	public function release() {
		if ( VipCompat::is_vip() && function_exists( 'wp_cache_delete' ) ) {
			wp_cache_delete( self::TRANSIENT_KEY, self::CACHE_GROUP );
			return;
		}

		delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Whether a crawl is currently running.
	 *
	 * @return bool
	 */
	public static function is_locked() {
		if ( VipCompat::is_vip() && function_exists( 'wp_cache_get' ) ) {
			return (bool) wp_cache_get( self::TRANSIENT_KEY, self::CACHE_GROUP );
		}

		return (bool) get_transient( self::TRANSIENT_KEY );
	}
}
