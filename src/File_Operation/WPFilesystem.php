<?php

namespace Slc\SeoLinksCrawler\File_Operation;

use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;

/**
 * WordPress filesystem wrapper for local file and remote URL operations.
 */
class WPFilesystem implements FileSystemInterface {

	/**
	 * WP_Filesystem instance.
	 *
	 * @var \WP_Filesystem_Base|null
	 */
	private $filesystem;

	/**
	 * Constructor: initialise the WP_Filesystem global.
	 */
	public function __construct() {
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		global $wp_filesystem;
		$this->filesystem = $wp_filesystem;
	}

	/**
	 * Read entire file contents into a string.
	 *
	 * @param string $file_path Path to the file.
	 *
	 * @return string|false File contents on success, false on failure.
	 */
	public function get_file_content( $file_path ) {
		if ( ! $this->filesystem ) {
			return false;
		}

		return $this->filesystem->get_contents( $file_path );
	}

	/**
	 * Write a string to a file.
	 *
	 * @param string    $file_path    Path to the file.
	 * @param string    $file_content Content to write.
	 * @param int|false $mode         File permissions as octal number, usually 0644.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function put_file_content( $file_path, $file_content, $mode = false ) {
		if ( ! $this->filesystem ) {
			return false;
		}

		return $this->filesystem->put_contents( $file_path, $file_content, $mode );
	}

	/**
	 * Delete a file.
	 *
	 * @param string $file_path Path to the file.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_file( $file_path ) {
		if ( ! $this->filesystem ) {
			return false;
		}

		return $this->filesystem->delete( $file_path );
	}

	/**
	 * Check if a file or directory exists.
	 *
	 * @param string $file_path Path to the file or directory.
	 *
	 * @return bool True if exists, false otherwise.
	 */
	public function file_exists( $file_path ) {
		if ( ! $this->filesystem ) {
			return false;
		}

		return $this->filesystem->exists( $file_path );
	}

	/**
	 * Fetch content from a remote URL using the WP HTTP API.
	 *
	 * @param string $url URL to fetch.
	 *
	 * @return string|false Response body on success, false on failure.
	 */
	public function fetch_url( $url ) {
		$response = wp_remote_get( $url, [ 'timeout' => 30 ] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );

		return ! empty( $body ) ? $body : false;
	}
}
