<?php
namespace Slc\SeoLinksCrawler\Admin;

use Slc\SeoLinksCrawler\Container\SeoLinksCrawlerContainer;


/**
 *  Main class
 **/
class AdminPage {

	private $crawler;
    private $transient_cache;
	private $filesystem;
	// private $dom_document_parser;
	private $links_finder;
	// private $container;

	/**
	 * Initiate class.
	 */
	public function __construct(SeoLinksCrawlerContainer $container) {
		// $this->register_dependencies();
		// $container->register_dependencies();
		
		// $this->container = $container;
        $this->transient_cache = $container->get('TransientCache');
		$this->filesystem = $container->get('FilesystemReader');
		// $this->dom_document_parser = $container->get('DomDocumentParser');
		$this->links_finder = $container->get('LinksFinder', $this->filesystem, $container->get('DomDocumentParser'));
		$this->crawler = $container->get('Crawler',$this->filesystem,$this->links_finder,$this->transient_cache);
		add_action( 'admin_menu', [ $this, 'slc_register_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'slc_admin_assets' ] );
		
	}


	public function slc_register_page() {
		add_menu_page(
			__( 'SEO Links Crawler', 'seo-links-crawler' ),
			__( 'SEO Links Crawler', 'seo-links-crawler' ),
			'manage_options',
			'seo-links-crawler',
			[ $this, 'slc_page_handler' ],
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
		// $t = new \Slc\SeoLinksCrawler\File_Reader\FilesystemReader();
		// $t = $this->get('FilesystemReader');
		// var_dump($this->filesystem);
		// $r = $this->filesystem->get_file_content( \get_home_url() );
		// $parser = new \Slc\SeoLinksCrawler\Html_Parser\DomDocumentParser();
		// $this->dom_document_parser->loadHTMLDocument($r);

		// var_dump($this->dom_document_parser->gather_links()) ;

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
	
}
