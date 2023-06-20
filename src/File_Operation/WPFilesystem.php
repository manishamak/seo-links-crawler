<?php

namespace Slc\SeoLinksCrawler\File_Operation;

/**
 *  Class for file related operations.
 **/
class WPFilesystem {

	/**
	 * Global variable of WP Filesystem.
	 *
	 * @var WP_Filesystem
	 */
	private $filesystem;

	/**
	 * Constructor for including and initiating WP_Filesystem class.
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
	 * Reads entire file into a string.
	 *
	 * @param  string $file_path    Name of the file to read.
	 *
	 * @return string|false $file_content Read data on success, false on failure.
	 */
	public function get_file_content( $file_path ) {
		if ( ! $this->filesystem ) {
			return false;
		}
		$file_content = $this->filesystem->get_contents( $file_path );

		return $file_content;
	}

	/**
	 * Writes a string to a file.
	 *
	 * @param string    $file_path    Remote path to the file where to write the data.
	 * @param string    $file_content The data to write.
	 * @param int|false $mode         The file permissions as octal number, usually 0644.
	 *
	 * @return boolean  $file_status   True on success, false on failure.
	 */
	public function put_file_content( $file_path, $file_content, $mode = false ) {
		if ( ! $this->filesystem ) {
			return false;
		}
		$file_status = $this->filesystem->put_contents( $file_path, $file_content, $mode );

		return $file_status;
	}

	/**
	 * Deletes a file.
	 *
	 * @param  string $file_path    Path to the file.
	 *
	 * @return boolean $file_status  True on success, false on failure.
	 */
	public function delete_file( $file_path ) {
		if ( ! $this->filesystem ) {
			return false;
		}
		$file_status = $this->filesystem->delete( $file_path );

		return $file_status;
	}

	/**
	 * Checks if a file or directory exists.
	 *
	 * @param  string $file_path    Path to the file or directory.
	 *
	 * @return boolean $file_status  Whether $path exists or not.
	 */
	public function file_exists( $file_path ) {
		if ( ! $this->filesystem ) {
			return false;
		}
		$file_status = $this->filesystem->exists( $file_path );

		return $file_status;
	}
}
