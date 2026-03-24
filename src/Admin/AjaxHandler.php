<?php

namespace Slc\SeoLinksCrawler\Admin;

use Slc\SeoLinksCrawler\Contracts\CacheInterface;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Cron\CrawlLock;
use Slc\SeoLinksCrawler\Cron\CrawlMeta;
use Slc\SeoLinksCrawler\Cron\CrawlOrchestrator;
use Slc\SeoLinksCrawler\Storage\StorageManager;

defined( 'ABSPATH' ) || exit;

/**
 * Handles AJAX endpoints for the admin crawl UI.
 */
class AjaxHandler {

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
	 * Storage manager for generated artifacts.
	 *
	 * @var StorageManager
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
	 * @param StorageManager      $storage      Storage manager.
	 */
	public function __construct(
		CrawlOrchestrator $orchestrator,
		CrawlLock $lock,
		CrawlMeta $meta,
		CacheInterface $cache,
		FileSystemInterface $filesystem,
		StorageManager $storage
	) {
		$this->orchestrator = $orchestrator;
		$this->lock         = $lock;
		$this->meta         = $meta;
		$this->cache        = $cache;
		$this->filesystem   = $filesystem;
		$this->storage      = $storage;
	}

	/**
	 * Register AJAX action hooks.
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_slc_admin_display_links', [ $this, 'handle_crawl' ] );
		add_action( 'wp_ajax_slc_crawl_status', [ $this, 'handle_status' ] );
		add_action( 'wp_ajax_slc_clear_cache', [ $this, 'handle_clear_cache' ] );
	}

	/**
	 * AJAX: execute a crawl and return the results.
	 *
	 * Acquires a lock, records metadata, runs the orchestrator,
	 * then releases the lock before sending the JSON response.
	 */
	public function handle_crawl() {
		check_ajax_referer( 'slc-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'You do not have permission to perform this action.', 'seo-links-crawler' ) );
		}

		if ( ! $this->lock->acquire() ) {
			wp_send_json_error( esc_html__( 'A crawl is already in progress. Please wait and try again.', 'seo-links-crawler' ) );
		}

		$this->meta->update(
			[
				'started_at' => time(),
				'status'     => 'running',
				'source'     => 'admin',
			]
		);

		$results = $this->orchestrator->crawl();

		if ( is_wp_error( $results ) ) {
			$this->meta->update(
				[
					'finished_at' => time(),
					'status'      => 'error',
					'error'       => $results->get_error_message(),
				]
			);
			$this->lock->release();
			wp_send_json_error( $results->get_error_message() );
		}

		$meta_data = [
			'finished_at' => time(),
			'status'      => 'success',
			'link_count'  => count( $results['links'] ),
			'error'       => $results['file_error'] ?: '',
			'source'      => 'admin',
		];
		$this->meta->update( $meta_data );

		$this->lock->release();
		wp_send_json_success(
			[
				'result'     => $results['links'],
				'file_error' => $results['file_error'],
				'crawl_meta' => $meta_data,
			]
		);
	}

	/**
	 * AJAX: return the current crawl lock state and last-run metadata.
	 */
	public function handle_status() {
		check_ajax_referer( 'slc-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'You do not have permission to perform this action.', 'seo-links-crawler' ) );
		}

		wp_send_json_success(
			[
				'is_locked' => CrawlLock::is_locked(),
				'meta'      => CrawlMeta::get_last(),
			]
		);
	}

	/**
	 * AJAX: clear the cached crawl data and generated HTML files.
	 */
	public function handle_clear_cache() {
		check_ajax_referer( 'slc-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'You do not have permission to perform this action.', 'seo-links-crawler' ) );
		}

		$this->cache->clean_up_cache();
		$this->filesystem->delete_file( $this->storage->get_home_html_path() );
		$this->filesystem->delete_file( $this->storage->get_sitemap_path() );

		wp_send_json_success(
			[
				'message' => esc_html__( 'Cache cleared successfully.', 'seo-links-crawler' ),
			]
		);
	}
}
