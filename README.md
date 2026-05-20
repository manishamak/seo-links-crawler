# SEO Links Crawler

A WordPress plugin that crawls your home page, extracts all internal links, displays them in the admin dashboard, and generates static HTML artifacts using environment-appropriate storage (uploads on standard hosts, CPT + object cache on WordPress VIP).

## Features

- **Internal link discovery** — Parses the home page HTML and identifies all internal links (absolute and relative).
- **Admin dashboard** — One-click crawl with a clean admin UI showing all discovered links, crawl status, and last-run metadata.
- **Clear cache** — Admin button to clear cached link data and remove generated HTML artifacts.
- **Runtime-safe HTML output** — Auto-generates `sitemap.html` and `home.html` on every successful cron crawl.
- **Dual-environment storage** — Filesystem + JSON cache on standard WordPress; transients, CPT posts, and object cache on VIP Go.
- **Public artifact URLs (VIP)** — Serves `/home.html` and `/sitemap.html` via rewrite rules with cache-first reads and `noindex` headers.
- **WP Cron** — Hourly automatic re-crawl to keep results fresh (with cache restore on cron failure).
- **Concurrency control** — Transient-based locks on standard hosts; atomic object-cache locks on VIP.
- **Extensible** — Filter hooks (`slc_filter_all_links`, `slc_filter_internal_links`) and action hooks (`slc_before_links_crawling_action`, `slc_after_links_crawling_action`) for customisation.

## Requirements

- PHP 8.3+
- WordPress 5.0+
- Composer (for development and autoloading)

## Installation

1. Clone or download this repository into `wp-content/plugins/seo-links-crawler/`.
2. Run `composer install` in the plugin directory.
3. Activate the plugin from **Plugins > Installed Plugins** in the WordPress admin.

## Usage

1. Navigate to **SEO Links Crawler** in the WordPress admin sidebar.
2. Click **Start Crawler** to scan the home page.
3. Internal links are displayed in the admin panel and cached for subsequent visits.
4. Use **Clear Cache** to remove cached links and generated HTML artifacts and force a fresh crawl.
5. Generated artifacts are available at the paths below depending on your environment.

## Runtime Storage

### Standard WordPress

- **Storage path:** `wp-content/uploads/seo-links-crawler/`
- **Cache:** `cached-home-connected-links.json` (JSON-encoded link list)
- **Artifacts:** `home.html`, `sitemap.html` (writable files under uploads)
- **Lock:** Transient `slc_crawl_lock`

### WordPress VIP Go

Detected automatically when VIP helpers (e.g. `vip_safe_wp_remote_get`) are present. No runtime writes to the local filesystem.

- **Cache:** Transient `slc_cached_home_connected_links`
- **Artifacts:** Internal `slc_artifact` CPT posts (`slc-home`, `slc-sitemap`) plus object-cache HTML keys
- **Public URLs:** `/home.html` and `/sitemap.html` (rewrite rules; cache-first, CPT fallback)
- **Lock:** Object cache (`wp_cache_add` / `wp_cache_delete`) for atomic cross-node locking
- **HTTP fetch:** `vip_safe_wp_remote_get()` when available, with a short timeout on VIP

Standard and VIP code paths share the same orchestration layer; the DI container binds `StorageInterface` and `CacheInterface` to the correct implementation per environment.

## Architecture

```
src/
├── Admin/
│   ├── AdminPage.php              Menu, template, and asset enqueuing
│   └── AjaxHandler.php            AJAX: crawl, status, clear cache
├── Cache/
│   ├── FilesystemCache.php        JSON file cache (standard)
│   └── TransientCache.php         Transient cache (VIP)
├── Contracts/                     Interfaces (Cache, FileSystem, HtmlParser, LinksFinder, Storage)
├── Container/
│   ├── DependencyInjectionContainer.php  Auto-wiring DI container
│   └── SeoLinksCrawlerContainer.php      Environment-specific bindings
├── Cron/
│   ├── CrawlLock.php              Concurrency lock (transient or object cache)
│   ├── CrawlMeta.php              Last-run metadata tracking
│   ├── CrawlOrchestrator.php      Pure crawl logic (fetch → parse → cache → generate)
│   └── CrawlScheduler.php         WP Cron lifecycle (schedule, execute, unschedule)
├── Endpoint/
│   └── PublicArtifactsController.php  VIP rewrite rules + artifact serving
├── File_Operation/
│   └── WPFilesystem.php           WP_Filesystem wrapper + HTTP fetch
├── Html_Parser/
│   └── DomDocumentParser.php      DOMDocument-based link extractor
├── Storage/
│   ├── StorageManager.php         Uploads-backed artifacts (standard)
│   └── VipStorageManager.php      CPT + object-cache artifacts (VIP)
├── Vip/
│   └── VipCompat.php              VIP detection, safe HTTP, error logging
├── Autoloader.php                 Composer autoloader bootstrap
└── LinksFinder.php                Internal link detection and URL normalisation
```

### Design Decisions

- **Single Responsibility** — Each class has one clear job: locking, metadata, storage, orchestration, scheduling, endpoints, or AJAX handling.
- **Interface-driven DI** — Contracts for cache, storage, filesystem, and parsing; auto-wired container selects VIP or standard implementations at runtime.
- **No jQuery** — Admin JS uses vanilla JavaScript with the Fetch API and DOM-safe rendering.
- **JSON cache** — Avoids PHP object injection risks from `unserialize()` on standard hosts.
- **Artifacts gets refresh in background process** — Home snapshot and sitemap are regenerated during cron by default. When the cache got cleared then latest artifacts got created during the admin interaction.
- **VIP-safe I/O** — No local filesystem artifacts on VIP; public HTML served from object cache with CPT fallback.
- **Cron resilience** — Failed cron crawls restore the previous link cache so the admin UI stays usable.
- **`wp_remote_get()` / `vip_safe_wp_remote_get()`** — WordPress HTTP API (or VIP-safe variant) instead of `file_get_contents()` for fetching.

## Development

```bash
# Install dependencies
composer install

# Run coding standards check (WordPress + VIP Go via phpcs.xml.dist)
composer phpcs

# Auto-fix coding standards
composer phpcs:fix

# Run unit tests (Brain Monkey + PHPUnit)
composer test-unit
```

## Hooks Reference

| Hook | Type | Description |
|------|------|-------------|
| `slc_filter_all_links` | Filter | Modify the full list of links before internal filtering |
| `slc_filter_internal_links` | Filter | Modify the list of internal links |
| `slc_before_links_crawling_action` | Action | Fires before crawling begins |
| `slc_after_links_crawling_action` | Action | Fires after crawling completes (receives links array) |

## License

GPL v3 — see [LICENSE](LICENSE) for details.
