<?php
namespace Slc\SeoLinksCrawler;

/**
 *  Class for Scanning links from web pages.
 **/
class LinksFinder {

	private $filesystem;
	private $dom_document_parser;

	public function __construct(FilesystemReader $filesystem, DomDocumentParser $dom_document_parser) {
		$this->filesystem = $filesystem;
		$this->dom_document_parser = $dom_document_parser;
	}

	public function is_internal_link($parsed_link){
		$parsed_home_url    = \wp_parse_url( \home_url() );
		if (isset($parsed_link['host']) && !empty($parsed_link['host']) && $parsed_home_url['host'] == $parsed_link['host']){
			return true;
		}
		if (empty( $parsed_link['scheme'] ) && empty( $parsed_link['host'] )){
			return true;
		}
		if (isset( $url['path'] ) && \strpos( $url['path'], $home_url['path'] ) === 0){
			return true;
		}
		return false;
	}

	// public function get_home_page(){
	// 	return get_home_url();
	// }

	public function create_internal_links( $page_url ){
		$internal_links = [];
		$file_content = $this->filesystem->get_file_content( $page_url );

		if ( ! $file_content ){
			return new WP_Error( 'scan_page_error', esc_html__( 'An error occurred while scanning the page. Please try again later.', 'seo-links-crawler' ) );
		}

		$this->dom_document_parser->loadHTMLDocument( $file_content );

		$links = $this->dom_document_parser->gather_links();
		if ( empty( $links ) ){
			return new WP_Error( 'no_links_found', esc_html__( 'No links found in the page.', 'seo-links-crawler' ) );
		}
		$internal_links = array_filter($links, function($link){
			$parsed_link = \wp_parse_url($link);
			return $this->is_internal_link($parsed_link);
		});
		return $internal_links;
	}
}
