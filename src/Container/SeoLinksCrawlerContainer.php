<?php

namespace Slc\SeoLinksCrawler\Container;

use Slc\SeoLinksCrawler\File_Operation\WPFilesystem;
use Slc\SeoLinksCrawler\Html_Parser\DomDocumentParser;
use Slc\SeoLinksCrawler\Cache\FilesystemCache;
use Slc\SeoLinksCrawler\LinksFinder;
use Slc\SeoLinksCrawler\Cron\Crawler;

/**
 * Plugin-specific container with pre-registered dependencies.
 */
class SeoLinksCrawlerContainer extends DependencyInjectionContainer {

	/**
	 * Constructor: register all plugin dependencies.
	 */
	public function __construct() {
		$this->register_dependencies();
	}

	/**
	 * Register plugin class dependencies.
	 *
	 * WPFilesystem and DomDocumentParser are shared (singletons) because
	 * they hold stateful resources that should not be duplicated.
	 */
	protected function register_dependencies() {
		$this->register( 'WPFilesystem', WPFilesystem::class, true );
		$this->register( 'DomDocumentParser', DomDocumentParser::class, true );
		$this->register( 'FilesystemCache', FilesystemCache::class );
		$this->register( 'LinksFinder', LinksFinder::class );
		$this->register( 'Crawler', Crawler::class );
	}
}
