<?php

namespace Slc\SeoLinksCrawler\Container;

use Slc\SeoLinksCrawler\Contracts\CacheInterface;
use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Contracts\HtmlParserInterface;
use Slc\SeoLinksCrawler\Contracts\LinksFinderInterface;
use Slc\SeoLinksCrawler\File_Operation\WPFilesystem;
use Slc\SeoLinksCrawler\Html_Parser\DomDocumentParser;
use Slc\SeoLinksCrawler\Cache\FilesystemCache;
use Slc\SeoLinksCrawler\Cache\TransientCache;
use Slc\SeoLinksCrawler\LinksFinder;
use Slc\SeoLinksCrawler\Vip\VipCompat;

/**
 * Plugin-specific container with interface → implementation bindings.
 *
 * Only interface bindings need to be registered. Concrete classes
 * (StorageManager, CrawlOrchestrator, etc.) are auto-resolved from
 * their constructor type-hints.
 */
class SeoLinksCrawlerContainer extends DependencyInjectionContainer {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_bindings();
	}

	/**
	 * Map each contract to its concrete implementation.
	 *
	 * WPFilesystem and DomDocumentParser are shared (singletons) because
	 * they hold stateful resources that should not be duplicated.
	 */
	protected function register_bindings() {
		$this->bind( FileSystemInterface::class, WPFilesystem::class, true );
		$this->bind( HtmlParserInterface::class, DomDocumentParser::class, true );
		$this->bind(
			CacheInterface::class,
			VipCompat::is_vip() ? TransientCache::class : FilesystemCache::class
		);
		$this->bind( LinksFinderInterface::class, LinksFinder::class );
	}
}
