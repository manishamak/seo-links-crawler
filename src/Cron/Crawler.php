<?php

namespace Slc\SeoLinksCrawler\Cron;

use Slc\SeoLinksCrawler\Cache\FilesystemCache;
use Slc\SeoLinksCrawler\LinksFinder;
use Slc\SeoLinksCrawler\File_Operation\WPFilesystem;

defined( 'ABSPATH' ) || exit;

/**
 *  Class for Crawling using WP Cron.
 **/
class Crawler {

	/**
	 * Sitemap.html full path.
	 *
	 * @var string
	 */
	public const SITEMAP_HTML_PATH = SLC_PLUGIN_PATH . '/templates/sitemap.html';

	/**
	 * Instance of the WPFilesystem.
	 *
	 * @var WPFilesystem
	 */
	private $filesystem;

	/**
	 * Instance of the LinksFinder.
	 *
	 * @var LinksFinder
	 */
	private $links_finder;

	/**
	 * Instance of the FilesystemCache.
	 *
	 * @var FilesystemCache
	 */
	private $filesystem_cache;

	/**
	 * Constructor.
	 *
	 * @param WPFilesystem    $filesystem       Instance of WPFilesystem class.
	 * @param LinksFinder     $links_finder     Instance of LinksFinder class.
	 * @param FilesystemCache $filesystem_cache Instance of FilesystemCache class.
	 */
	public function __construct(
	WPFilesystem $filesystem,
	LinksFinder $links_finder,
	FilesystemCache $filesystem_cache
	) {
		$this->filesystem       = $filesystem;
		$this->links_finder     = $links_finder;
		$this->filesystem_cache = $filesystem_cache;
		add_action( 'wp_ajax_slc_admin_display_links', [ $this, 'slc_admin_display_links' ] );
		add_action( 'slc_crawl_internal_links_scheduler', [ $this, 'slc_execute_crawling' ] );
	}

	/**
	 * Get home page url.
	 *
	 * @return string home page url.
	 */
	public function get_home() {
		return \get_home_url();
	}

	/**
	 * Execute the crawling process.
	 *
	 * @throws \Exception If $links_result is returning WP_Error.
	 * @throws \Exception If there are no internal links found.
	 * @throws \Exception If $links_result is not stored in cache file.
	 * @throws \Exception If home page has not been created.
	 * @throws \Exception If sitemap.html file has not been created.
	 *
	 * @return array|WP_Error $links_result array of links on success, WP_Error on failure.
	 */
	public function slc_execute_crawling() {
		// start crawling scheduler.
		$this->schedule_cron();

		do_action( 'slc_before_links_crawling_action' );

		try {
			$this->filesystem_cache->initiate_cache();

			$this->filesystem_cache->clean_up_cache();

			$this->filesystem->delete_file( self::SITEMAP_HTML_PATH );

			$links_result = $this->links_finder->create_internal_links( $this->get_home() );
			if ( is_wp_error( $links_result ) ) {
				throw new \Exception( $links_result->get_error_message() );
			}
			if ( empty( $links_result ) ) {
				throw new \Exception( esc_html__( 'No internal links found.', 'seo-links-crawler' ) );
			}

			try {
				$data_cached = $this->filesystem_cache->cache_data( $links_result );
				if ( ! $data_cached ) {
					/* translators: 1: path of cache folder */
					throw new \Exception( sprintf( esc_html__( 'There is an error in storing the crawling results in cache. Please check the permission of %1$s.', 'seo-links-crawler' ), 'wp-content/slc-cache folder' ) );
				}

				$is_home_created = $this->save_home_page_as_html();
				if ( ! $is_home_created ) {
					throw new \Exception( esc_html__( 'There is some error in creating home.html. Please check active theme folder permission.', 'seo-links-crawler' ) );
				}

				$is_sitemap_created = $this->create_sitemap_html( $links_result );
				if ( ! $is_sitemap_created ) {
					throw new \Exception( esc_html__( 'There is some error in creating sitemap.html. Please try again later.', 'seo-links-crawler' ) );
				}
			} catch ( \Exception $e ) {
				error_log( 'Crawler file creation failed: ' . $e->getMessage() );
			}

			do_action( 'slc_after_links_crawling_action', $links_result );

			return $links_result;

		} catch ( \Exception $e ) {
			error_log( 'Cron task failed: ' . $e->getMessage() );
			return new \WP_Error( 'crawl_error', $e->getMessage() );
		}
	}

	/**
	 * Creation of sitemap.html.
	 *
	 * @param  array $slc_results     internal links array.
	 *
	 * @return boolean $sitemap_created True on success, False on failure.
	 */
	private function create_sitemap_html( $slc_results ) {
		require SLC_PLUGIN_PATH . '/templates/sitemap.php';
		$sitemap_created = $this->filesystem->put_file_content( self::SITEMAP_HTML_PATH, $slc_sitemap_structure );
		return $sitemap_created;
	}

	/**
	 * Create home.html from home page.
	 *
	 * @return boolean $file_created True on success, False on failure.
	 */
	private function save_home_page_as_html() {
		$new_home_file_name = $this->filesystem->file_exists( get_stylesheet_directory() . '/home.html' ) ? '/home-slc.html' : '/home.html';
		$new_home_file_path = get_stylesheet_directory() . $new_home_file_name;
		$home_contents      = $this->filesystem->get_file_content( $this->get_home() );
		$file_created       = $this->filesystem->put_file_content( $new_home_file_path, $home_contents );
		return $file_created;
	}

	/**
	 * Execute crawler and fetch results from cache(Ajax callback function).
	 */
	public function slc_admin_display_links() {
		check_ajax_referer( 'slc-admin', 'nonce' );
		$results      = $this->slc_execute_crawling();
		$cached_links = $this->filesystem_cache->get_cache_data();
		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results->get_error_message() );
		}
		$json_success = ! $cached_links ? $results : $cached_links;
		wp_send_json_success( $json_success );
	}

	/**
	 * Method to schedule the hourly cron event.
	 */
	private function schedule_cron() {
		if ( ! wp_next_scheduled( 'slc_crawl_internal_links_scheduler' ) ) {
			wp_schedule_event( time(), 'hourly', 'slc_crawl_internal_links_scheduler' );
		}
	}

	/**
	 * Method to unschedule the hourly cron event.
	 */
	public function unschedule_cron() {
		wp_clear_scheduled_hook( 'slc_crawl_internal_links_scheduler' );
	}
}

