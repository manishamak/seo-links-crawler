<?php

namespace Slc\SeoLinksCrawler\Contracts;

/**
 * Interface for file system operations.
 */
interface FileSystemInterface {

	/**
	 * Read entire file contents into a string.
	 *
	 * @param string $file_path Path to the file.
	 *
	 * @return string|false File contents on success, false on failure.
	 */
	public function get_file_content( $file_path );

	/**
	 * Write a string to a file.
	 *
	 * @param string    $file_path    Path to the file.
	 * @param string    $file_content Content to write.
	 * @param int|false $mode         File permissions as octal number.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function put_file_content( $file_path, $file_content, $mode = false );

	/**
	 * Delete a file.
	 *
	 * @param string $file_path Path to the file.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete_file( $file_path );

	/**
	 * Check if a file or directory exists.
	 *
	 * @param string $file_path Path to the file or directory.
	 *
	 * @return bool True if exists, false otherwise.
	 */
	public function file_exists( $file_path );

	/**
	 * Fetch content from a remote URL via HTTP GET.
	 *
	 * @param string $url URL to fetch.
	 *
	 * @return string|false Response body on success, false on failure.
	 */
	public function fetch_url( $url );
}
