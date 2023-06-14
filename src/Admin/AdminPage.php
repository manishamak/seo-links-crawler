<?php
namespace Slc\SeoLinksCrawler\Admin;

/**
 *  Main class
 **/
class AdminPage {

	/**
	 * Initiate class.
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'slc_register_page' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'slc_admin_assets' ] );
		add_action( 'wp_ajax_slc_admin_display_links', [ __CLASS__, 'slc_admin_display_links' ] );
	}

	public function slc_register_page() {
		add_menu_page(
			__( 'SEO Links Crawler', 'seo-links-crawler' ),
			__( 'SEO Links Crawler', 'seo-links-crawler' ),
			'manage_options',
			'seo-links-crawler',
			[ __CLASS__, 'slc_page_handler' ],
			'dashicons-tagcloud',
			''
		);
	}

	public function slc_page_handler(){ ?>
		<div class="slc-wrap">
			<button class="slc-button"><?php echo esc_html__( 'Start Crawler', 'seo-links-crawler' ); ?></button>
			<div class="slc-links-wrap"></div>
		</div> 
		<?php
		\Slc\SeoLinksCrawler\Admin\adminpage::init();
		$t = new \Slc\SeoLinksCrawler\FilesystemReader();
		$r = $t->get_file_content( 'http://localhost/wp-demo' );
		var_dump( $r );

	}

	public function slc_admin_assets() {
		if ( isset( $_GET['page'] ) && ! empty( $_GET['page'] ) && 'seo-links-crawler' === $_GET['page'] ) {
			wp_enqueue_script(
				'slc-admin-script',
				SLC_PLUGIN_URL . '/assets/js/admin.js',
				[ 'jquery' ],
				SLC_VERSION,
				false
			);

			$settings = [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'slc-admin' ),
				'loading' => esc_html__( 'Loading', 'seo-links-crawler' ),
			];

			wp_localize_script(
				'slc-admin-script',
				'slcAdminObj',
				$settings
			);
		}
	}

	public function slc_admin_display_links() {
		check_ajax_referer( 'slc-admin', 'nonce' );
		var_dump( $_POST );
	}
}
