<?php

namespace Slc\SeoLinksCrawler\File_Reader;

class FilesystemReader {

	private $filesystem;

	public function __construct() {
		// add_action( 'init', array( $this, 'init_filesystem' ) );
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		global $wp_filesystem;
		$this->filesystem = $wp_filesystem;
	}

	// public function init_filesystem() {
	// if ( ! function_exists( 'WP_Filesystem' ) ) {
	// require_once( ABSPATH . 'wp-admin/includes/file.php' );
	// }

	// WP_Filesystem();

	// global $wp_filesystem;
	// $this->filesystem = $wp_filesystem;
	// }

	public function get_file_content( $file_path ) {
		if ( ! $this->filesystem ) {
			return false;
		}

		$file_content = $this->filesystem->get_contents( $file_path );
		// $file_content = \file_get_contents( $file_path );

		return $file_content;
	}


	public function put_file_content( $file_path, $file_content ) {
		if ( ! $this->filesystem ) {
			return false;
		}

		$file_status = $this->filesystem->put_contents( $file_path, $file_content );

		return $file_status;
	}

	public function delete_file( $file_path ) {
		if ( ! $this->filesystem ) {
			return false;
		}

		$file_status = $this->filesystem->delete( $file_path );

		return $file_status;
	}
}
