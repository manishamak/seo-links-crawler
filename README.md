# SEO Links Crawler

A WordPress plugin that crawls your home page, extracts all internal links, displays them in the admin dashboard, and generates static HTML artifacts in runtime-safe storage.

## Features

- **Internal link discovery** — Parses the home page HTML and identifies all internal links (absolute and relative).
- **Admin dashboard** — One-click crawl with a clean admin UI showing all discovered links.
- **Runtime-safe HTML output** — Auto-generates `sitemap.html` and `home.html` in uploads-backed storage instead of plugin or theme code directories.
- **Filesystem cache** — Results are cached as JSON to avoid repeated HTTP requests.
- **WP Cron** — Hourly automatic re-crawl to keep results fresh.
- **Extensible** — Filter hooks (`slc_filter_all_links`, `slc_filter_internal_links`) and action hooks (`slc_before_links_crawling_action`, `slc_after_links_crawling_action`) for customisation.

## Requirements

- PHP 7.4+
- WordPress 5.0+

## Installation

1. Clone or download this repository into `wp-content/plugins/seo-links-crawler/`.
2. Run `composer install` in the plugin directory.
3. Activate the plugin from **Plugins > Installed Plugins** in the WordPress admin.

## Usage

1. Navigate to **SEO Links Crawler** in the WordPress admin sidebar.
2. Click **Start Crawler** to scan the home page.
3. Internal links are displayed in the admin panel and cached for subsequent visits.
4. Generated files are written to `wp-content/uploads/seo-links-crawler/` by default.

## Runtime Storage

- Default storage path: `wp-content/uploads/seo-links-crawler/`
- Generated files now live in uploads-backed writable storage instead of plugin or theme code directories

## Architecture

```
src/
├── Admin/              Admin page registration and asset enqueuing
├── Cache/              Filesystem-based JSON cache
├── Contracts/          Interfaces (FileSystem, Cache, HtmlParser, LinksFinder)
├── Container/          Simple DI container with singleton support
├── Cron/               WP Cron crawler and AJAX handler
├── File_Operation/     WP_Filesystem wrapper + wp_remote_get
├── Html_Parser/        DOMDocument-based link extractor
├── Autoloader.php      Composer autoloader bootstrap
└── LinksFinder.php     Internal link detection and normalisation
```

### Design Decisions

- **Interface-driven DI** — All major classes depend on contracts, making them testable and swappable.
- **No jQuery** — Admin JS uses vanilla JavaScript with the Fetch API.
- **JSON cache** — Avoids PHP object injection risks from `unserialize()`.
- **`wp_remote_get()`** — Uses the WordPress HTTP API instead of `file_get_contents()` for reliable URL fetching.

## Development

```bash
# Install dependencies
composer install

# Run coding standards check
composer phpcs

# Auto-fix coding standards
composer phpcs:fix

# Run unit tests
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
