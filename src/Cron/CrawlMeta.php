<?php

namespace Slc\SeoLinksCrawler\Cron;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks metadata for the most recent crawl run.
 *
 * Stores started/finished timestamps, status, link count, and
 * any error messages in a single wp_options row.
 */
class CrawlMeta {

	const OPTION_KEY = 'slc_last_crawl';

	/**
	 * Persist metadata key-value pairs.
	 *
	 * @param array $data Metadata to store.
	 */
	public function update( array $data ) {
		update_option( self::OPTION_KEY, $data, false );
	}

	/**
	 * Record finished-crawl metadata from a crawl result.
	 *
	 * @param array|\WP_Error $result Return value of CrawlOrchestrator::crawl().
	 */
	public function record_finished( $result ) {
		if ( is_wp_error( $result ) ) {
			$this->update(
				[
					'finished_at' => time(),
					'status'      => 'error',
					'error'       => $result->get_error_message(),
				]
			);
			return;
		}

		$this->update(
			[
				'finished_at' => time(),
				'status'      => 'success',
				'link_count'  => is_array( $result['links'] ) ? count( $result['links'] ) : 0,
				'error'       => $result['file_error'] ?: '',
			]
		);
	}

	/**
	 * Return stored metadata from the most recent crawl run.
	 *
	 * @return array {
	 *     @type int    $started_at  Unix timestamp when the crawl started.
	 *     @type int    $finished_at Unix timestamp when the crawl finished.
	 *     @type string $status      One of 'running', 'success', 'error'.
	 *     @type int    $link_count  Number of internal links found (success only).
	 *     @type string $error       Error/warning message, if any.
	 *     @type string $source      Source of the crawl: 'admin' or 'cron'.
	 * }
	 */
	public static function get_last() {
		return get_option( self::OPTION_KEY, [] );
	}
}
