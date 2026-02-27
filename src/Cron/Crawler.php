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
	const SITEMAP_HTML_PATH = SLC_PLUGIN_PATH . '/templates/sitemap.html';

	/**
	 * Home.html full path.
	 *
	 * @var string
	 */
	const HOME_HTML_RELATIVE_PATH = '/slc-templates/home.html';

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
		add_action( 'slc_crawl_internal_links_scheduler', [ $this, 'slc_execute_cron_crawling' ] );
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
	 * Execute the cron crawling process.
	 *
	 * @return array|WP_Error $send_results array of links and file error on success, WP_Error on failure.
	 */
	public function slc_execute_cron_crawling() {

		$this->filesystem_cache->clean_up_cache();
		$this->filesystem->delete_file( self::SITEMAP_HTML_PATH );
		$home_html_path = \get_stylesheet_directory() . self::HOME_HTML_RELATIVE_PATH;
		$this->filesystem->delete_file( $home_html_path );
		$this->slc_execute_crawling();
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
	 * @return array|WP_Error $send_results array of links and file error on success, WP_Error on failure.
	 */
	public function slc_execute_crawling() {
		$file_error = '';
		// start crawling scheduler.
		$this->schedule_cron();

		do_action( 'slc_before_links_crawling_action' );

		try {
			$this->filesystem_cache->initiate_cache();

			$home_url     = $this->get_home();
			$home_content = $this->filesystem->get_file_content( $home_url );
			$file_errors  = [];

			$links_result = $this->filesystem_cache->get_cache_data();
			if ( ! $links_result ) {
				$links_result = $this->links_finder->create_internal_links( $home_url, $home_content );
				if ( is_wp_error( $links_result ) ) {
					throw new \Exception( $links_result->get_error_message() );
				}
				if ( empty( $links_result ) ) {
					throw new \Exception( esc_html__( 'No internal links found.', 'seo-links-crawler' ) );
				}

				$data_cached = $this->filesystem_cache->cache_data( $links_result );
				if ( ! $data_cached ) {
					/* translators: 1: path of cache folder */
					$file_errors[] = sprintf( esc_html__( 'There is an error in storing the crawling results in cache. Please check the permission of %1$s.', 'seo-links-crawler' ), 'wp-content/slc-cache folder' );
				}

			}

				$is_home_created = $this->save_home_page_as_html( $home_content );
				if ( ! $is_home_created ) {
					/* translators: 1: path of home.html folder */
					$file_errors[] = sprintf( esc_html__( 'There is some error in creating home.html. Please check %1$s in active theme folder. Either its not exists or is not writable. You can create one and change its permission manually.', 'seo-links-crawler' ), 'slc-templates' );
				}

				$is_sitemap_created = $this->create_sitemap_html( $links_result );
				if ( ! $is_sitemap_created ) {
					$file_errors[] = esc_html__( 'There is some error in creating sitemap.html. Please try again later.', 'seo-links-crawler' );
				}

				if ( ! empty( $file_errors ) ) {
					$file_error = implode( ' ', $file_errors );
					error_log( 'Crawler file creation failed: ' . $file_error );
				}
			// } catch ( \Exception $e ) {
			// 	error_log( 'Crawler file creation failed: ' . $e->getMessage() );
			// 	$file_error = $e->getMessage();
			// }

			do_action( 'slc_after_links_crawling_action', $links_result );

			$send_results = [
				'links'      => $links_result,
				'file_error' => $file_error,
			];
			return $send_results;

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
		if ( $this->filesystem->file_exists( self::SITEMAP_HTML_PATH ) ) {
			return true;
		}
		$sitemap_created = $this->filesystem->put_file_content( self::SITEMAP_HTML_PATH, $slc_sitemap_structure );
		return $sitemap_created;
	}

	/**
	 * Create home.html from home page.
	 *
	 * @param string $home_contents Pre-fetched home page HTML content.
	 *
	 * @return boolean $file_created True on success, False on failure.
	 */
	private function save_home_page_as_html( $home_contents ) {
		$home_html_path = \get_stylesheet_directory() . self::HOME_HTML_RELATIVE_PATH;
		$home_html_file_directory = dirname( $home_html_path );
		if ( ! is_dir( $home_html_file_directory ) ) {
			wp_mkdir_p( $home_html_file_directory );
		}
		if ( $this->filesystem->file_exists( $home_html_path ) ) {
			return true;
		}
		if ( ! $home_contents ) {
			return false;
		}
		$file_created = $this->filesystem->put_file_content( $home_html_path, $home_contents );
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
		$links_list   = ! $cached_links ? $results['links'] : $cached_links;
		$json_success = [
			'result'     => $links_list,
			'file_error' => $results['file_error'],
		];
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
	public static function unschedule_cron() {
		wp_clear_scheduled_hook( 'slc_crawl_internal_links_scheduler' );
	}
}

