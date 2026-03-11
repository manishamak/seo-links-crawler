<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Cleans up all data created by the plugin:
 * - Cache directory and files
 * - Generated home.html in the active theme
 * - Generated sitemap.html in the plugin templates
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

$home_html = get_stylesheet_directory() . '/slc-templates/home.html';
if ( file_exists( $home_html ) ) {
	unlink( $home_html );
	$slc_dir = dirname( $home_html );
	if ( is_dir( $slc_dir ) && count( glob( $slc_dir . '/*' ) ) === 0 ) {
		rmdir( $slc_dir );
	}
}

$sitemap_html = plugin_dir_path( __FILE__ ) . 'templates/sitemap.html';
if ( file_exists( $sitemap_html ) ) {
	unlink( $sitemap_html );
}

wp_clear_scheduled_hook( 'slc_crawl_internal_links_scheduler' );
