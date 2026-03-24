<?php

namespace Slc\SeoLinksCrawler\Cron;

use Slc\SeoLinksCrawler\Contracts\CacheInterface;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Contracts\LinksFinderInterface;
use Slc\SeoLinksCrawler\Storage\StorageManager;

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrates the crawl: fetch, parse, cache, and generate files.
 *
 * This class contains only the pure crawl logic. Lock management,
 * metadata tracking, and response formatting belong to the callers
 * (AjaxHandler, CrawlScheduler).
 */
class CrawlOrchestrator {

	/**
	 * File system abstraction for read/write and HTTP fetch operations.
	 *
	 * @var FileSystemInterface
	 */
	private $filesystem;

	/**
	 * Internal-links extraction service.
	 *
	 * @var LinksFinderInterface
	 */
	private $links_finder;

	/**
	 * Persistent crawl cache storage.
	 *
	 * @var CacheInterface
	 */
	private $cache;

	/**
	 * Manager for generated storage artifacts.
	 *
	 * @var StorageManager
	 */
	private $storage;

	/**
	 * Constructor.
	 *
	 * @param FileSystemInterface  $filesystem   File system instance.
	 * @param LinksFinderInterface $links_finder  Links finder instance.
	 * @param CacheInterface       $cache         Cache instance.
	 * @param StorageManager       $storage       Storage manager instance.
	 */
	public function __construct(
		FileSystemInterface $filesystem,
		LinksFinderInterface $links_finder,
		CacheInterface $cache,
		StorageManager $storage
	) {
		$this->filesystem   = $filesystem;
		$this->links_finder = $links_finder;
		$this->cache        = $cache;
		$this->storage      = $storage;
	}

	/**
	 * Execute the crawl process.
	 *
	 * Checks cache first, fetches the home page if needed, discovers
	 * internal links, caches results, and generates HTML artifacts.
	 *
	 * @return array|\WP_Error {
	 *     @type array  $links      List of internal link URLs.
	 *     @type string $file_error Concatenated file-creation warnings (may be empty).
	 * }
	 *
	 * @throws \Exception When crawl prerequisites or parsing fail.
	 */
	public function crawl() {
		$file_error = '';

		\do_action( 'slc_before_links_crawling_action' );

		try {
			$this->cache->initiate_cache();
			$this->storage->ensure_directory();

			$home_url          = \get_home_url();
			$home_content      = null;
			$file_errors       = [];
			$storage_directory = $this->storage->get_directory();

			$links_result = $this->cache->get_cache_data();

			if ( ! $links_result ) {
				$home_content = $this->filesystem->fetch_url( $home_url );
				$links_result = $this->links_finder->create_internal_links( $home_url, $home_content );

				if ( is_wp_error( $links_result ) ) {
					throw new \Exception( $links_result->get_error_message() );
				}

				if ( empty( $links_result ) ) {
					throw new \Exception( \esc_html__( 'No internal links found.', 'seo-links-crawler' ) );
				}

				$data_cached = $this->cache->cache_data( $links_result );
				if ( ! $data_cached ) {
					/* translators: 1: storage directory path */
					$file_errors[] = sprintf( \esc_html__( 'There is an error in storing the crawling results in cache. Please check the permission of %1$s.', 'seo-links-crawler' ), $storage_directory );
				}
			}

			if ( ! $this->filesystem->file_exists( $this->storage->get_home_html_path() ) ) {
				$is_home_created = $this->storage->save_home_html( $home_content );
				if ( ! $is_home_created ) {
					/* translators: 1: storage directory path */
					$file_errors[] = sprintf( \esc_html__( 'There is some error in creating home.html. Please check whether %1$s exists and is writable.', 'seo-links-crawler' ), $storage_directory );
				}
			}

			$is_sitemap_created = $this->storage->save_sitemap_html( $links_result );
			if ( ! $is_sitemap_created ) {
				/* translators: 1: storage directory path */
				$file_errors[] = sprintf( \esc_html__( 'There is some error in creating sitemap.html. Please check whether %1$s exists and is writable.', 'seo-links-crawler' ), $storage_directory );
			}

			if ( ! empty( $file_errors ) ) {
				$file_error = implode( ' ', $file_errors );
				\error_log( 'SEO Links Crawler: file creation failed - ' . $file_error );
			}

			\do_action( 'slc_after_links_crawling_action', $links_result );

			return [
				'links'      => $links_result,
				'file_error' => $file_error,
			];

		} catch ( \Exception $e ) {
			\error_log( 'SEO Links Crawler: crawl failed - ' . $e->getMessage() );
			return new \WP_Error( 'crawl_error', $e->getMessage() );
		}
	}
}
