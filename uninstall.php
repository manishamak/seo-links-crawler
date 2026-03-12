<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up all data created by the plugin:
 * - Cache directory and files
 * - Generated runtime HTML files
 * - Scheduled cron events
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$slc_cache_dir = WP_CONTENT_DIR . '/slc-cache/';
if ( is_dir( $slc_cache_dir ) ) {
	$files = glob( $slc_cache_dir . '*' );
	if ( $files ) {
		array_map( 'unlink', $files );
	}
	rmdir( $slc_cache_dir );
}

$uploads           = wp_upload_dir();
$storage_directory = trailingslashit( $uploads['basedir'] ) . 'seo-links-crawler';
$generated_files   = [
	$storage_directory . '/home.html',
	$storage_directory . '/sitemap.html'
];

foreach ( $generated_files as $generated_file ) {
	if ( file_exists( $generated_file ) ) {
		unlink( $generated_file );
	}
}

if ( is_dir( $storage_directory ) ) {
	$storage_files = glob( $storage_directory . '/*' );
	if ( false !== $storage_files && count( $storage_files ) === 0 ) {
		rmdir( $storage_directory );
	}
}

wp_clear_scheduled_hook( 'slc_crawl_internal_links_scheduler' );
