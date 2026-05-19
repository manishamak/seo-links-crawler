<?php
/**
 * Plugin Name:       SEO Links Crawler
 * Description:       Crawl and display all internal links to admin and generates sitemap.html
 * Version:           1.1.0
 * Author:            Manisha Makhija
 * Author URI:        https://profiles.wordpress.org/manishamakhija/
 * License:           GPL v3
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       seo-links-crawler
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'SLC_PLUGIN_FILE' ) ) {
	define( 'SLC_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'SLC_PLUGIN_PATH' ) ) {
	define( 'SLC_PLUGIN_PATH', plugin_dir_path( SLC_PLUGIN_FILE ) );
}

if ( ! defined( 'SLC_PLUGIN_URL' ) ) {
	define( 'SLC_PLUGIN_URL', plugin_dir_url( SLC_PLUGIN_FILE ) );
}

if ( ! defined( 'SLC_VERSION' ) ) {
	define( 'SLC_VERSION', '1.1.0' );
}

require SLC_PLUGIN_PATH . '/src/Autoloader.php';

if ( ! \Slc\SeoLinksCrawler\Autoloader::init() ) {
	return;
}

/**
 * Bootstrap the plugin after all plugins are loaded.
 *
 * Wires dependencies, registers cron and AJAX hooks on every request,
 * and registers admin UI hooks only inside the admin context.
 */
function slc_init_plugin() {
	$container = new \Slc\SeoLinksCrawler\Container\SeoLinksCrawlerContainer();

	$container->get( \Slc\SeoLinksCrawler\Cron\CrawlScheduler::class )->register_hooks();
	$container->get( \Slc\SeoLinksCrawler\Admin\AjaxHandler::class )->register_hooks();
	if ( \Slc\SeoLinksCrawler\Vip\VipCompat::is_vip() ) {
		$container->get( \Slc\SeoLinksCrawler\Endpoint\PublicArtifactsController::class )->register_hooks();
	}

	if ( is_admin() ) {
		$container->get( \Slc\SeoLinksCrawler\Admin\AdminPage::class )->register_hooks();
	}

	load_plugin_textdomain( 'seo-links-crawler', false, dirname( plugin_basename( SLC_PLUGIN_FILE ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'slc_init_plugin' );

/**
 * Plugin activation: ensure runtime storage directory exists and schedule cron.
 */
function slc_activate_plugin() {
	// On VIP Go the local filesystem is not reliable for runtime artifacts.
	// Storage is handled via TransientCache and VipStorageManager.
	if ( \Slc\SeoLinksCrawler\Vip\VipCompat::is_vip() ) {
		\Slc\SeoLinksCrawler\Endpoint\PublicArtifactsController::add_rewrite_rules();
		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules -- One-time flush on plugin activation to register VIP artifact rewrite rules.
		flush_rewrite_rules();
		\Slc\SeoLinksCrawler\Cron\CrawlScheduler::schedule();
		return;
	}

	$uploads           = wp_upload_dir();
	$storage_directory = trailingslashit( $uploads['basedir'] ) . 'seo-links-crawler';
	if ( ! is_dir( $storage_directory ) ) {
		wp_mkdir_p( $storage_directory );
	}

	\Slc\SeoLinksCrawler\Cron\CrawlScheduler::schedule();
}
register_activation_hook( SLC_PLUGIN_FILE, 'slc_activate_plugin' );

/**
 * Plugin deactivation: unschedule cron events.
 */
function slc_deactivate_plugin() {
	\Slc\SeoLinksCrawler\Cron\CrawlScheduler::unschedule();
}
register_deactivation_hook( SLC_PLUGIN_FILE, 'slc_deactivate_plugin' );
