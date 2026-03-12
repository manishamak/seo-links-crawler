<?php

namespace Slc\SeoLinksCrawler\Cron;

use Slc\SeoLinksCrawler\Contracts\CacheInterface;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Contracts\LinksFinderInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Handles crawling of internal links using WP Cron.
 */
class Crawler {

	/**
	 * Directory name used under uploads for generated files.
	 *
	 * @var string
	 */
	const STORAGE_DIRECTORY_NAME = 'seo-links-crawler';

	/**
	 * Generated sitemap filename.
	 *
	 * @var string
	 */
	const SITEMAP_HTML_FILENAME = 'sitemap.html';

	/**
	 * Generated home snapshot filename.
	 *
	 * @var string
	 */
	const HOME_HTML_FILENAME = 'home.html';

	/**
	 * @var FileSystemInterface
	 */
	private $filesystem;

	/**
	 * @var LinksFinderInterface
	 */
	private $links_finder;

	/**
	 * @var CacheInterface
	 */
	private $filesystem_cache;

	/**
	 * Constructor.
	 *
	 * @param FileSystemInterface  $filesystem       File system instance.
	 * @param LinksFinderInterface $links_finder     Links finder instance.
	 * @param CacheInterface       $filesystem_cache Cache instance.
	 */
	public function __construct(
		FileSystemInterface $filesystem,
		LinksFinderInterface $links_finder,
		CacheInterface $filesystem_cache
	) {
		$this->filesystem       = $filesystem;
		$this->links_finder     = $links_finder;
		$this->filesystem_cache = $filesystem_cache;
	}

	/**
	 * Register WordPress hooks for the crawler.
	 */
	public function register_hooks() {
		add_action( 'wp_ajax_slc_admin_display_links', [ $this, 'slc_admin_display_links' ] );
		add_action( 'slc_crawl_internal_links_scheduler', [ $this, 'slc_execute_cron_crawling' ] );
	}

	/**
	 * Get home page URL.
	 *
	 * @return string Home page URL.
	 */
	public function get_home() {
		return \get_home_url();
	}

	/**
	 * Execute the cron crawling process.
	 *
	 * Clears stale cache and generated files before re-crawling.
	 *
	 * @return array|\WP_Error Result array on success, WP_Error on failure.
	 */
	public function slc_execute_cron_crawling() {
		$this->filesystem_cache->clean_up_cache();
		$generated_file_paths = array_merge(
			[
				$this->get_home_html_path(),
				$this->get_sitemap_html_path(),
			],
		);

		foreach ( $generated_file_paths as $generated_file_path ) {
			$this->filesystem->delete_file( $generated_file_path );
		}

		return $this->slc_execute_crawling();
	}

	/**
	 * Execute the crawling process.
	 *
	 * @return array|\WP_Error Result array with 'links' and 'file_error' keys on success, WP_Error on failure.
	 */
	public function slc_execute_crawling() {
		$file_error = '';

		$this->schedule_cron();

		do_action( 'slc_before_links_crawling_action' );

		try {
			$this->filesystem_cache->initiate_cache();
			$this->ensure_storage_directory();

			$home_url          = $this->get_home();
			$home_content      = null;
			$file_errors       = [];
			$storage_directory = $this->get_storage_directory();

			$links_result = $this->filesystem_cache->get_cache_data();

			if ( ! $links_result ) {
				$home_content = $this->filesystem->fetch_url( $home_url );
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

			$home_html_path = $this->get_home_html_path();

			if ( ! $this->filesystem->file_exists( $home_html_path ) ) {
				$is_home_created = $this->save_home_page_as_html( $home_content );
				if ( ! $is_home_created ) {
					/* translators: 1: runtime storage directory */
					$file_errors[] = sprintf( esc_html__( 'There is some error in creating home.html. Please check whether %1$s exists and is writable.', 'seo-links-crawler' ), $storage_directory );
				}
			}

			$is_sitemap_created = $this->create_sitemap_html( $links_result );
			if ( ! $is_sitemap_created ) {
				/* translators: 1: runtime storage directory */
				$file_errors[] = sprintf( esc_html__( 'There is some error in creating sitemap.html. Please check whether %1$s exists and is writable.', 'seo-links-crawler' ), $storage_directory );
			}

			if ( ! empty( $file_errors ) ) {
				$file_error = implode( ' ', $file_errors );
				error_log( 'SEO Links Crawler: file creation failed - ' . $file_error );
			}

			do_action( 'slc_after_links_crawling_action', $links_result );

			return [
				'links'      => $links_result,
				'file_error' => $file_error,
			];

		} catch ( \Exception $e ) {
			error_log( 'SEO Links Crawler: crawl failed - ' . $e->getMessage() );
			return new \WP_Error( 'crawl_error', $e->getMessage() );
		}
	}

	/**
	 * Build sitemap.html from the crawl results.
	 *
	 * @param array $slc_results Internal links array.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function create_sitemap_html( $slc_results ) {
		$sitemap_html_path = $this->get_sitemap_html_path();

		if ( $this->filesystem->file_exists( $sitemap_html_path ) ) {
			return true;
		}

		$this->ensure_storage_directory();

		ob_start();
		include SLC_PLUGIN_PATH . '/templates/sitemap.php';
		$sitemap_html = ob_get_clean();

		return $this->filesystem->put_file_content( $sitemap_html_path, $sitemap_html );
	}

	/**
	 * Get the uploads-backed storage directory for generated files.
	 *
	 * @return string
	 */
	private function get_storage_directory() {
		$uploads = wp_upload_dir();

		return trailingslashit( $uploads['basedir'] ) . self::STORAGE_DIRECTORY_NAME;
	}

	/**
	 * Ensure the uploads-backed storage directory exists.
	 *
	 * @return bool
	 */
	private function ensure_storage_directory() {
		$storage_directory = $this->get_storage_directory();

		if ( is_dir( $storage_directory ) ) {
			return true;
		}

		return wp_mkdir_p( $storage_directory );
	}

	/**
	 * Get the generated sitemap path.
	 *
	 * @return string
	 */
	private function get_sitemap_html_path() {
		return trailingslashit( $this->get_storage_directory() ) . self::SITEMAP_HTML_FILENAME;
	}

	/**
	 * Get the generated home snapshot path.
	 *
	 * @return string
	 */
	private function get_home_html_path() {
		return trailingslashit( $this->get_storage_directory() ) . self::HOME_HTML_FILENAME;
	}

	/**
	 * Create home.html from pre-fetched home page content.
	 *
	 * @param string|null $home_contents  Pre-fetched home page HTML content.
	 * @param string      $home_html_path Full path for the home.html file.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function save_home_page_as_html( $home_contents ) {
		// $home_html_file_directory = dirname( $this->get_home_html_path() );

		// if ( ! is_dir( $home_html_file_directory ) ) {
		// 	wp_mkdir_p( $home_html_file_directory );
		// }
		$this->ensure_storage_directory();

		if ( null === $home_contents ) {
			$home_contents = $this->filesystem->fetch_url( $this->get_home() );
		}

		if ( ! $home_contents ) {
			return false;
		}

		return $this->filesystem->put_file_content( $this->get_home_html_path(), $home_contents );
	}

	/**
	 * AJAX callback to execute crawling and return results.
	 */
	public function slc_admin_display_links() {
		check_ajax_referer( 'slc-admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( esc_html__( 'You do not have permission to perform this action.', 'seo-links-crawler' ) );
		}

		$results      = $this->slc_execute_crawling();
		$cached_links = $this->filesystem_cache->get_cache_data();

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results->get_error_message() );
		}

		$links_list = ! $cached_links ? $results['links'] : $cached_links;

		wp_send_json_success( [
			'result'     => $links_list,
			'file_error' => $results['file_error'],
		] );
	}

	/**
	 * Schedule the hourly cron event if not already scheduled.
	 */
	private function schedule_cron() {
		if ( ! wp_next_scheduled( 'slc_crawl_internal_links_scheduler' ) ) {
			wp_schedule_event( time(), 'hourly', 'slc_crawl_internal_links_scheduler' );
		}
	}

	/**
	 * Unschedule the hourly cron event.
	 */
	public static function unschedule_cron() {
		wp_clear_scheduled_hook( 'slc_crawl_internal_links_scheduler' );
	}
}
