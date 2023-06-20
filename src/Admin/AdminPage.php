<?php
namespace Slc\SeoLinksCrawler\Admin;

use Slc\SeoLinksCrawler\Container\SeoLinksCrawlerContainer;

defined( 'ABSPATH' ) || exit;

/**
 *  Admin page settings class.
 **/
class AdminPage {

	/**
	 * Create instances of the classes.
	 *
	 * @param SeoLinksCrawlerContainer $container Instance of the container.
	 */
	public function __construct( SeoLinksCrawlerContainer $container ) {

		$filesystem_obj   = $container->get( 'WPFilesystem' );
		$links_finder_obj = $container->get(
			'LinksFinder',
			$filesystem_obj,
			$container->get( 'DomDocumentParser' )
		);
		$container->get(
			'Crawler',
			$filesystem_obj,
			$links_finder_obj,
			$container->get(
				'FilesystemCache',
				$filesystem_obj
			)
		);
		add_action( 'admin_menu', [ $this, 'slc_register_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'slc_admin_assets' ] );
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
	}

	/**
	 *  Loads a plugin’s translated strings.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'seo-links-crawler', false, SLC_PLUGIN_PATH . '/languages' );
	}

	/**
	 *  Adds settings page in admin dashboard.
	 */
	public function slc_register_page() {
		add_menu_page(
			__( 'SEO Links Crawler', 'seo-links-crawler' ),
			__( 'SEO Links Crawler', 'seo-links-crawler' ),
			'manage_options',
			'seo-links-crawler',
			[ $this, 'slc_page_handler' ],
			'dashicons-tagcloud'
		);
	}

	/**
	 *  Admin settings page callback function.
	 */
	public function slc_page_handler(){ ?>
		<div class="slc-wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html__( 'SEO Links Crawler', 'seo-links-crawler' ); ?></h1>
			<a href="#" class="slc-button-action"><?php echo esc_html__( 'Start Crawler', 'seo-links-crawler' ); ?></a>
			<div class="slc-links-wrap"></div>
		</div> 
		<?php
	}

	/**
	 *  Includes assets in Admin settings page.
	 */
	public function slc_admin_assets() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && ! empty( $_GET['page'] ) && 'seo-links-crawler' === $_GET['page'] ) {
			wp_enqueue_script(
				'slc-admin-script',
				SLC_PLUGIN_URL . '/assets/js/admin.js',
				[ 'jquery' ],
				SLC_VERSION,
				false
			);

			wp_enqueue_style(
				'slc-admin-style',
				SLC_PLUGIN_URL . '/assets/css/admin-settings.css',
				[],
				SLC_VERSION
			);

			$settings = [
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'slc-admin' ),
				'loading'      => esc_html__( 'Loading', 'seo-links-crawler' ),
				'resetBtnText' => esc_html__( 'Start Crawler', 'seo-links-crawler' ),
			];

			wp_localize_script(
				'slc-admin-script',
				'slcAdminObj',
				$settings
			);
		}
	}

}
