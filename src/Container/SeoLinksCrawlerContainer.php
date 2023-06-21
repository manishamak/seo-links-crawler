<?php

namespace Slc\SeoLinksCrawler\Container;

use Slc\SeoLinksCrawler\File_Operation\WPFilesystem;
use Slc\SeoLinksCrawler\Html_Parser\DomDocumentParser;
use Slc\SeoLinksCrawler\Cache\FilesystemCache;
use Slc\SeoLinksCrawler\LinksFinder;
use Slc\SeoLinksCrawler\Cron\Crawler;

/**
 * Container class for controlling various dependencies.
 */
class SeoLinksCrawlerContainer extends DependencyInjectionContainer {

	/**
	 * SeoLinksCrawlerContainer constructor.
	 */
	public function __construct() {
		$this->register_dependencies();
	}

	/**
	 * Register various class dependencies.
	 */
	protected function register_dependencies() {
		$this->register( 'WPFilesystem', WPFilesystem::class );
		$this->register( 'DomDocumentParser', DomDocumentParser::class );
		$this->register( 'FilesystemCache', FilesystemCache::class );
		$this->register( 'LinksFinder', LinksFinder::class );
		$this->register( 'Crawler', Crawler::class );
	}
}
