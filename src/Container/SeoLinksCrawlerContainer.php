<?php

namespace Slc\SeoLinksCrawler\Container;

use Slc\SeoLinksCrawler\File_Reader\FilesystemReader;
use Slc\SeoLinksCrawler\Html_Parser\DomDocumentParser;
use Slc\SeoLinksCrawler\Cache\FilesystemCache;
use Slc\SeoLinksCrawler\LinksFinder;
use Slc\SeoLinksCrawler\Cron\Crawler;

class SeoLinksCrawlerContainer extends DependencyInjectionContainer {

	public function __construct() {
		$this->register_dependencies();
	}

	protected function register_dependencies() {
		$this->register( 'FilesystemReader', FilesystemReader::class );
		$this->register( 'DomDocumentParser', DomDocumentParser::class );
		$this->register( 'FilesystemCache', FilesystemCache::class );
		$this->register( 'LinksFinder', LinksFinder::class );
		$this->register( 'Crawler', Crawler::class );
	}
}
