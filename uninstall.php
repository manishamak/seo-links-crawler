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

$is_vip = function_exists( 'vip_safe_wp_remote_get' ) || function_exists( 'vip_error_log' );

$uploads           = wp_upload_dir();
$storage_directory = trailingslashit( $uploads['basedir'] ) . 'seo-links-crawler';

if ( ! $is_vip && is_dir( $storage_directory ) ) {
	$entries = scandir( $storage_directory );

	if ( is_array( $entries ) ) {
		foreach ( $entries as $entry ) {
			if ( '.' === $entry || '..' === $entry ) {
				continue;
			}

			$file_path = $storage_directory . DIRECTORY_SEPARATOR . $entry;

			// No subfolders expected, so we only remove files.
			if ( is_file( $file_path ) ) {
				wp_delete_file( $file_path );
			}
		}
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	if ( false !== WP_Filesystem() ) {
		global $wp_filesystem;
		if ( $wp_filesystem && $wp_filesystem->is_dir( $storage_directory ) ) {
			$wp_filesystem->rmdir( $storage_directory );
		}
	}
}

wp_clear_scheduled_hook( 'slc_crawl_internal_links_scheduler' );
delete_option( 'slc_last_crawl' );
delete_transient( 'slc_crawl_lock' );
delete_transient( 'slc_cached_home_connected_links' );

// VIP artifacts are stored as CPT posts (slc_artifact).
$artifact_post = get_page_by_path( 'slc-home', \OBJECT, 'slc_artifact' );
if ( $artifact_post && isset( $artifact_post->ID ) ) {
	wp_delete_post( (int) $artifact_post->ID, true );
}
$artifact_post = get_page_by_path( 'slc-sitemap', \OBJECT, 'slc_artifact' );
if ( $artifact_post && isset( $artifact_post->ID ) ) {
	wp_delete_post( (int) $artifact_post->ID, true );
}
wp_cache_delete( 'slc_artifact_home_html', 'seo-links-crawler' );
wp_cache_delete( 'slc_artifact_sitemap_html', 'seo-links-crawler' );
