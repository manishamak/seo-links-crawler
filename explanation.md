# Seo Links Crawler

## Summary

This is a WordPress plugin which extracts internal links from the home page and displays them on an admin page so the administrator can review SEO-related links connected to the home page. The plugin re-crawls every hour, keeps results up to date, and generates two public HTML files: `home.html` (a snapshot of the current home page) and `sitemap.html` (a list of discovered internal links). On a normal WordPress site these files live under uploads. On WordPress VIP Go the same content is stored in the database and object cache and served at `/home.html` and `/sitemap.html`.

## What plugin is doing?

* When the plugin is activated, the **SEO Links Crawler** admin menu becomes visible and the hourly cron job is scheduled.
* On the admin page there are two buttons: **Start Crawler** and **Clear Cache**.
* **Start Crawler** begins a crawl (if another crawl is not already running). It fetches the home page when needed, finds internal links, saves them to cache, and regenerates `home.html` and `sitemap.html`.
* **Clear Cache** removes cached link data and deletes generated HTML artifacts so the next crawl starts fresh.
* On a **standard WordPress** site:
  * Link results are cached in `wp-content/uploads/seo-links-crawler/cached-home-connected-links.json`.
  * `home.html` and `sitemap.html` are saved in `wp-content/uploads/seo-links-crawler/` and are reachable via the uploads URL.
* On **WordPress VIP Go** (detected automatically):
  * Link results are stored in a transient.
  * HTML is stored in internal CPT posts and object cache.
  * Visitors can open `/home.html` and `/sitemap.html` on the site (rewrite rules + cache-first serving).
* **Hourly cron** clears old cache and artifacts, runs a full crawl, and saves new data. If the crawl fails, the previous link cache is restored so the admin page can still show the last good results.
* After a crawl finishes, links are read from cache and shown below the buttons. File-related warnings appear as admin notices; cron errors are logged when `WP_DEBUG` is on (or via `vip_error_log` on VIP).
* If the home page cannot be scanned, an error message is shown below the crawler button.
* On plugin deactivation, the hourly cron event is removed. On uninstall, plugin data (files, options, transients, CPT artifacts, and cache keys) is cleaned up.

## Technical Decisions

* Used Composer autoloader (PSR-4) for class loading.
* Used AJAX for crawl, status, and clear-cache actions for a smoother admin UI (vanilla JavaScript + Fetch API, no jQuery).
* Used an auto-wiring DI container with interfaces so standard and VIP implementations can be swapped without changing crawl logic.
* Split responsibilities into focused classes (orchestrator, scheduler, lock, meta, storage, AJAX, endpoints) instead of one large crawler class.
* Used `WP_Filesystem` and `wp_remote_get()` (or `vip_safe_wp_remote_get()` on VIP) for file and HTTP operations in a WordPress-friendly way.
* Used `DOMDocument` for HTML parsing because it handles large pages reliably and is available in PHP by default.
* Prevent concurrent crawl(via cron and via admin click) by implementing lock mechanism.
* **Standard hosts:** JSON file cache and uploads-backed HTML files under `wp-content/uploads/seo-links-crawler/` (one folder for cache + artifacts).
* **VIP Go:** transients for link cache, CPT posts for HTML artifacts, object cache for fast public serving, and atomic object-cache locks instead of transients for concurrent crawls.
* Regenerates `home.html` and `sitemap.html` on every successful crawl.
* Shared `WPFilesystem` and `DomDocumentParser` instances where a single shared instance is enough (via container “shared” bindings).
* Coding standards: WordPress Coding Standards + VIP Go rules (`phpcs.xml.dist`), with PHPUnit unit tests (Brain Monkey).
