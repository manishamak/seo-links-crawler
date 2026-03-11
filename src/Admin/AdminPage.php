<?php

namespace Slc\SeoLinksCrawler\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin page UI: menu registration, template rendering, and asset enqueuing.
 */
class AdminPage {

	/**
	 * Register WordPress hooks for the admin UI.
	 */
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'slc_register_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'slc_admin_assets' ] );
	}

	/**
	 * Add settings page in admin dashboard.
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
	 * Admin settings page callback function.
	 */
	public function slc_page_handler() {
		?>
		<div class="slc-wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html__( 'SEO Links Crawler', 'seo-links-crawler' ); ?></h1>
			<a href="#" class="slc-button-action"><?php echo esc_html__( 'Start Crawler', 'seo-links-crawler' ); ?></a>
			<div class="slc-links-wrap"></div>
		</div>
		<?php
	}

	/**
	 * Enqueue assets on the plugin's admin page only.
	 */
	public function slc_admin_assets() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && ! empty( $_GET['page'] ) && 'seo-links-crawler' === $_GET['page'] ) {
			wp_enqueue_script(
				'slc-admin-script',
				SLC_PLUGIN_URL . '/assets/js/admin.js',
				[],
				SLC_VERSION,
				true
			);

			wp_enqueue_style(
				'slc-admin-style',
				SLC_PLUGIN_URL . '/assets/css/admin-settings.css',
				[],
				SLC_VERSION
			);

			wp_localize_script(
				'slc-admin-script',
				'slcAdminObj',
				[
					'ajaxurl'      => admin_url( 'admin-ajax.php' ),
					'nonce'        => wp_create_nonce( 'slc-admin' ),
					'loading'      => esc_html__( 'Loading', 'seo-links-crawler' ),
					'resetBtnText' => esc_html__( 'Start Crawler', 'seo-links-crawler' ),
				]
			);
		}
	}
}
