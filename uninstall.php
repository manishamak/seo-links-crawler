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
	$files = glob( $storage_directory . '/*' );
	if ( false !== $files ) {
		array_map( 'unlink', $files );
	}
	rmdir( $storage_directory );
}

wp_clear_scheduled_hook( 'slc_crawl_internal_links_scheduler' );
delete_option( 'slc_last_crawl' );
delete_transient( 'slc_crawl_lock' );
