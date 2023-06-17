<?php 

namespace Slc\SeoLinksCrawler\Cron;
use Slc\SeoLinksCrawler\Cache\TransientCache;
use Slc\SeoLinksCrawler\LinksFinder;
use Slc\SeoLinksCrawler\File_Reader\FilesystemReader;
// use Slc\SeoLinksCrawler\

// Define a class to handle WP Cron tasks
class Crawler {

    private $filesystem;

    private $links_finder;

    private $transient_cache;

    // private $container;
  
  // Constructor method
  public function __construct(FilesystemReader $filesystem, LinksFinder $links_finder, TransientCache $transient_cache) {
    $this->filesystem = $filesystem;
    $this->links_finder = $links_finder;
    $this->transient_cache = $transient_cache;
    add_action( 'wp_ajax_slc_admin_display_links', [ $this, 'slc_admin_display_links' ] );
    // Schedule the cron event
    add_action('slc_crawl_internal_links_scheduler', array($this, 'slc_execute_crawling'));
  }
  

  // public static function initiate_crawler(){
  //   $this->execute_crawling();
  //   // self
  //   // $this->container = $container;
  //   // add_action('slc_crawl_internal_links_scheduler', array($this, 'slc_crawl_links_callback'));
  // }

  // Cron callback function
  // public function slc_crawl_links_callback() {
  //   $this->execute_crawling();

  //   // Perform your cron task here
  //   // This function will be executed when the 'slc_crawl_internal_links_scheduler' event is triggered
  // }
  
  
  public function slc_execute_crawling(){
    $this->schedule_cron();
    $page_to_scan = \get_home_url();
    $links_result = $this->links_finder->create_internal_links($page_to_scan);
    if ( is_wp_error($links_result) || empty ( $links_result ) ){
      return new WP_Error( 'no_internal_links_found', esc_html__( 'No internal links found.', 'seo-links-crawler' ) );
    }
    $this->container->get('TransientCache');
    $this->container->get('FilesystemReader');
    $this->container->get('LinksFinder');
  }

  // on button click call
  public function slc_admin_display_links() {
		check_ajax_referer( 'slc-admin', 'nonce' );
		$this->slc_execute_crawling();
    //get_transient data
    // wp_send_json(transient_data)
		var_dump( $_POST );
	}

  // Method to schedule the cron event
  public function schedule_cron() {
    // Use WP Cron to schedule the event
    if (!wp_next_scheduled('slc_crawl_internal_links_scheduler')) {
      wp_schedule_event(time(), 'hourly', 'slc_crawl_internal_links_scheduler');
    }
  }
  
  // Method to unschedule the cron event
  public function unschedule_cron() {
    // Use WP Cron to unschedule the event
    wp_clear_scheduled_hook('slc_crawl_internal_links_scheduler');
  }
}

