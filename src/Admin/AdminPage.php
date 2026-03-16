<?php

namespace Slc\SeoLinksCrawler\Admin;

use Slc\SeoLinksCrawler\Cron\CrawlLock;
use Slc\SeoLinksCrawler\Cron\CrawlMeta;

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
		$is_locked = CrawlLock::is_locked();
		$meta      = CrawlMeta::get_last();
		?>
		<div class="slc-wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html__( 'SEO Links Crawler', 'seo-links-crawler' ); ?></h1>
			<a href="#" class="slc-button-action<?php echo $is_locked ? ' disabled' : ''; ?>"><?php echo esc_html__( 'Start Crawler', 'seo-links-crawler' ); ?></a>

			<div class="slc-status-bar" aria-live="polite">
				<?php $this->render_status( $meta, $is_locked ); ?>
			</div>

			<div class="slc-links-wrap"></div>
		</div>
		<?php
	}

	/**
	 * Render the crawl-status section shown above the results list.
	 *
	 * @param array $meta      Last-crawl metadata from Crawler::get_last_crawl_meta().
	 * @param bool  $is_locked Whether a crawl is currently running.
	 */
	private function render_status( $meta, $is_locked ) {

		$status = isset( $meta['status'] ) ? $meta['status'] : '';

		if ( empty( $meta ) || empty( $meta['status'] ) ) {
			printf(
				'<span class="slc-status slc-status--none">%s</span>',
				esc_html__( 'No crawl has been run yet.', 'seo-links-crawler' )
			);
			return;
		}
		
		if ( $is_locked || 'running' === $status ) {
			printf(
				'<span class="slc-status slc-status--running">%s</span>',
				esc_html__( 'Crawl in progress…', 'seo-links-crawler' )
			);
			return;
		}

		$finished_at = isset( $meta['finished_at'] ) ? (int) $meta['finished_at'] : 0;
		$time_label  = $finished_at
			? sprintf(
				/* translators: %s: human-readable time difference */
				esc_html__( '%s ago', 'seo-links-crawler' ),
				human_time_diff( $finished_at, time() )
			)
			: esc_html__( 'Unknown', 'seo-links-crawler' );

		if ( 'success' === $status ) {
			$link_count = isset( $meta['link_count'] ) ? (int) $meta['link_count'] : 0;
			printf(
				'<span class="slc-status slc-status--success">%s</span>',
				sprintf(
					/* translators: 1: relative time, 2: link count */
					esc_html__( 'Last crawl: %1$s — %2$d links found.', 'seo-links-crawler' ),
					$time_label,
					$link_count
				)
			);
		} else {
			$error = ! empty( $meta['error'] ) ? $meta['error'] : __( 'Unknown error.', 'seo-links-crawler' );
			printf(
				'<span class="slc-status slc-status--error">%s %s</span>',
				sprintf(
					/* translators: %s: relative time */
					esc_html__( 'Last crawl: %s — failed.', 'seo-links-crawler' ),
					$time_label
				),
				esc_html( $error )
			);
		}
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
					'isLocked'     => CrawlLock::is_locked(),
					'lockedMsg'    => esc_html__( 'A crawl is already in progress. Please wait and try again.', 'seo-links-crawler' ),
				]
			);
		}
	}
}
