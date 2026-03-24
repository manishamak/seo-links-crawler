<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up all data created by the plugin:
 * - Generated files and storage directory
 * - Scheduled cron events
 * - Options and transients
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$uploads           = wp_upload_dir();
$storage_directory = trailingslashit( $uploads['basedir'] ) . 'seo-links-crawler';

if ( is_dir( $storage_directory ) ) {
	$entries = scandir( $storage_directory );

	if ( is_array( $entries ) ) {
		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$path = $storage_directory . DIRECTORY_SEPARATOR . $entry;

			// No subfolders expected, so we only remove files.
			if ( is_file( $path ) ) {
				@unlink( $path );
			}
		}
	}

	@rmdir( $storage_directory );
}

wp_clear_scheduled_hook( 'slc_crawl_internal_links_scheduler' );
delete_option( 'slc_last_crawl' );
delete_transient( 'slc_crawl_lock' );
