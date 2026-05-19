<?php

namespace Slc\SeoLinksCrawler\Storage;

use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Contracts\StorageInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the uploads-backed storage directory and generated HTML files.
 */
class StorageManager implements StorageInterface {

	const DIRECTORY_NAME     = 'seo-links-crawler';
	const SITEMAP_FILENAME   = 'sitemap.html';
	const HOME_HTML_FILENAME = 'home.html';

	/**
	 * File system abstraction used for storage operations.
	 *
	 * @var FileSystemInterface
	 */
	private $filesystem;

	/**
	 * Constructor.
	 *
	 * @param FileSystemInterface $filesystem File system instance.
	 */
	public function __construct( FileSystemInterface $filesystem ) {
		$this->filesystem = $filesystem;
	}

	/**
	 * Get the uploads-backed storage directory path.
	 *
	 * @return string Absolute path to the storage directory.
	 */
	public function get_directory() {
		$uploads = wp_upload_dir();

		return trailingslashit( $uploads['basedir'] ) . self::DIRECTORY_NAME;
	}

	/**
	 * Ensure the storage directory exists, creating it if necessary.
	 *
	 * @return bool True if the directory exists or was created.
	 */
	public function ensure_directory() {
		$dir = $this->get_directory();

		if ( is_dir( $dir ) ) {
			return true;
		}

		return wp_mkdir_p( $dir );
	}

	/**
	 * Get the full path for the generated sitemap file.
	 *
	 * @return string
	 */
	public function get_sitemap_path() {
		return trailingslashit( $this->get_directory() ) . self::SITEMAP_FILENAME;
	}

	/**
	 * Get the full path for the generated home snapshot file.
	 *
	 * @return string
	 */
	public function get_home_html_path() {
		return trailingslashit( $this->get_directory() ) . self::HOME_HTML_FILENAME;
	}

	/**
	 * Create home.html from pre-fetched or freshly-fetched home page content.
	 *
	 * @param string|null $html Pre-fetched HTML, or null to fetch via the filesystem.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save_home_html( $html = null ) {
		$this->ensure_directory();

		if ( null === $html ) {
			$html = $this->filesystem->fetch_url( \get_home_url() );
		}

		if ( ! $html ) {
			return false;
		}

		return $this->filesystem->put_file_content( $this->get_home_html_path(), $html );
	}

	/**
	 * Build sitemap.html from crawl results using the template.
	 *
	 * @param array $slc_results Internal links array.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save_sitemap_html( array $slc_results ) {
		$path = $this->get_sitemap_path();

		if ( $this->filesystem->file_exists( $path ) ) {
			return true;
		}

		$this->ensure_directory();

		$sitemap_css = $this->filesystem->get_file_content( SLC_PLUGIN_PATH . '/assets/css/sitemap.css' );
		if ( ! is_string( $sitemap_css ) ) {
			$sitemap_css = '';
		}

		// Normalize array keys before rendering the template.
		$slc_results = array_values( $slc_results );

		ob_start();
		include SLC_PLUGIN_PATH . '/templates/sitemap.php';
		$sitemap_html = ob_get_clean();

		return $this->filesystem->put_file_content( $path, $sitemap_html );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_location_label() {
		return $this->get_directory();
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepare() {
		return $this->ensure_directory();
	}

	/**
	 * {@inheritdoc}
	 */
	public function home_snapshot_exists() {
		return (bool) $this->filesystem->file_exists( $this->get_home_html_path() );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string|null $html Pre-fetched HTML, or null to fetch via the filesystem.
	 */
	public function save_home_snapshot( $html = null ) {
		return $this->save_home_html( $html );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $links Internal links list.
	 */
	public function save_sitemap( array $links ) {
		return $this->save_sitemap_html( $links );
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear_artifacts() {
		$this->filesystem->delete_file( $this->get_home_html_path() );
		$this->filesystem->delete_file( $this->get_sitemap_path() );
	}
}
