<?php

namespace Slc\SeoLinksCrawler\Cron;

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

	/**
	 * Attempt to acquire the crawl lock.
	 *
	 * @return bool True if the lock was acquired, false if already held.
	 */
	public function acquire() {
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
		delete_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Whether a crawl is currently running.
	 *
	 * @return bool
	 */
	public static function is_locked() {
		return (bool) get_transient( self::TRANSIENT_KEY );
	}
}
