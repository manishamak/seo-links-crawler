<?php
/**
 * Plugin Name:       Seo Links Crawler
 * Description:       Crawl and display all internal links to admin and generates sitemap.html
 * Version:           1.0
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



// Check abspath exists or not.
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
	define( 'SLC_VERSION', 1.0 );
}

require __DIR__ . '/src/Autoloader.php';

if ( ! \Slc\SeoLinksCrawler\Autoloader::init() ) {
	return;
}

// $adminObj = new \Slc\SeoLinksCrawler\Admin\adminpage();

$container = new Slc\SeoLinksCrawler\Container\SeoLinksCrawlerContainer();
new \Slc\SeoLinksCrawler\Admin\adminpage($container);
