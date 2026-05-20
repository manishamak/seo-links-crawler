<?php

namespace Slc\SeoLinksCrawler\Cron;

use Slc\SeoLinksCrawler\Contracts\CacheInterface;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Contracts\StorageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * WP Cron lifecycle: scheduling, unscheduling, and executing the
 * hourly re-crawl with lock and metadata management.
 */
class CrawlScheduler {

	const CRON_HOOK = 'slc_crawl_internal_links_scheduler';

	/**
	 * Crawl orchestration service.
	 *
	 * @var CrawlOrchestrator
	 */
	private $orchestrator;

	/**
	 * Crawl lock manager.
	 *
	 * @var CrawlLock
	 */
	private $lock;

	/**
	 * Crawl metadata manager.
	 *
	 * @var CrawlMeta
	 */
	private $meta;

	/**
	 * Cache storage service.
	 *
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * File system abstraction.
	 *
	 * @var FileSystemInterface
	 */
	private $filesystem;

	/**
	 * Storage manager for generated files.
	 *
	 * @var StorageInterface
	 */
	private $storage;

	/**
	 * Constructor.
	 *
	 * @param CrawlOrchestrator   $orchestrator Crawl orchestrator.
	 * @param CrawlLock           $lock         Lock manager.
	 * @param CrawlMeta           $meta         Metadata tracker.
	 * @param CacheInterface      $cache        Cache instance.
	 * @param FileSystemInterface $filesystem  File system instance.
	 * @param StorageInterface    $storage      Storage manager.
	 */
	public function __construct(
		CrawlOrchestrator $orchestrator,
		CrawlLock $lock,
		CrawlMeta $meta,
		CacheInterface $cache,
		FileSystemInterface $filesystem,
		StorageInterface $storage
	) {
		$this->orchestrator = $orchestrator;
		$this->lock         = $lock;
		$this->meta         = $meta;
		$this->cache        = $cache;
		$this->filesystem   = $filesystem;
		$this->storage      = $storage;
	}

	/**
	 * Register the WP Cron hook.
	 */
	public function register_hooks() {
		add_action( self::CRON_HOOK, [ $this, 'execute' ] );
	}

	/**
	 * Cron callback: clear stale data, re-crawl, and record results.
	 *
	 * @return array|\WP_Error Crawl result on success, WP_Error on failure.
	 */
	public function execute() {
		if ( ! $this->lock->acquire() ) {
			return new \WP_Error(
				'crawl_locked',
				__( 'A crawl is already in progress.', 'seo-links-crawler' )
			);
		}

		$this->meta->update(
			[
				'started_at' => time(),
				'status'     => 'running',
				'source'     => 'cron',
			]
		);

		try {
			$previous_cache = $this->cache->get_cache_data();
			$this->cache->clean_up_cache();

			$this->storage->clear_artifacts();

			$result = $this->orchestrator->crawl();

			if ( is_wp_error( $result ) && ! empty( $previous_cache ) ) {
				$this->cache->cache_data( $previous_cache );
			}

			$this->meta->record_finished( $result );

			return $result;
		} catch ( \Exception $e ) {
			$this->meta->update(
				[
					'finished_at' => time(),
					'status'      => 'error',
					'error'       => $e->getMessage(),
				]
			);

			return new \WP_Error( 'crawl_error', $e->getMessage() );
		} finally {
			$this->lock->release();
		}
	}

	/**
	 * Schedule the hourly cron event if not already scheduled.
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Unschedule the hourly cron event.
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}
}
