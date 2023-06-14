<?php

namespace Slc\SeoLinksCrawler;

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
}
