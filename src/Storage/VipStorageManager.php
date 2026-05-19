<?php

namespace Slc\SeoLinksCrawler\Storage;

use Slc\SeoLinksCrawler\Contracts\FileSystemInterface;
use Slc\SeoLinksCrawler\Contracts\StorageInterface;
use Slc\SeoLinksCrawler\Public\PublicArtifactsController;
use Slc\SeoLinksCrawler\Vip\VipCompat;

defined( 'ABSPATH' ) || exit;

/**
 * VIP-safe storage manager.
 *
 * WordPress VIP Go environments should not write to local disk. This storage
 * implementation persists generated artifacts (home snapshot + sitemap HTML)
 * in the database/object cache via options/transients.
 */
class VipStorageManager implements StorageInterface {

	/**
	 * Post slug for the sitemap artifact.
	 *
	 * @var string
	 */
	const SITEMAP_SLUG = 'slc-sitemap';

	/**
	 * Post slug for the home snapshot artifact.
	 *
	 * @var string
	 */
	const HOME_SLUG = 'slc-home';

	/**
	 * Cache key for home snapshot HTML.
	 *
	 * @var string
	 */
	const HOME_CACHE_KEY = 'slc_artifact_home_html';

	/**
	 * Cache key for sitemap HTML.
	 *
	 * @var string
	 */
	const SITEMAP_CACHE_KEY = 'slc_artifact_sitemap_html';

	/**
	 * File system abstraction (used only for fetching).
	 *
	 * @var FileSystemInterface
	 */
	private $filesystem;

	/**
	 * Constructor.
	 *
	 * @param FileSystemInterface $filesystem File system instance.
	 */
	public function __construct( FileSystemInterface $filesystem ) {
		$this->filesystem = $filesystem;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_location_label() {
		return 'database/object cache';
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepare() {
		// No directory creation on VIP.
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function home_snapshot_exists() {
		$post = get_page_by_path( self::HOME_SLUG, \OBJECT, PublicArtifactsController::POST_TYPE );
		return (bool) ( $post && isset( $post->ID ) );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param string|null $html Pre-fetched HTML, or null to fetch internally.
	 */
	public function save_home_snapshot( $html = null ) {
		if ( null === $html ) {
			$html = $this->filesystem->fetch_url( \get_home_url() );
		}

		if ( ! $html ) {
			return false;
		}

		// $html = $this->sanitize_snapshot_html( $html );

		$ok = $this->upsert_artifact_post(
			self::HOME_SLUG,
			'SEO Links Crawler: home snapshot',
			$html
		);

		if ( $ok ) {
			wp_cache_set( self::HOME_CACHE_KEY, $html, PublicArtifactsController::CACHE_GROUP, 300 );
		}

		return $ok;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $links Internal links list.
	 */
	public function save_sitemap( array $links ) {
		// Normalize array keys before rendering the template.
		$slc_results = array_values( $links );

		$sitemap_css = $this->filesystem->get_file_content( SLC_PLUGIN_PATH . '/assets/css/sitemap.css' );
		if ( ! is_string( $sitemap_css ) ) {
			$sitemap_css = '';
		}

		ob_start();
		include SLC_PLUGIN_PATH . '/templates/sitemap.php';
		$sitemap_html = ob_get_clean();

		if ( ! is_string( $sitemap_html ) || '' === $sitemap_html ) {
			return false;
		}

		$ok = $this->upsert_artifact_post(
			self::SITEMAP_SLUG,
			'SEO Links Crawler: sitemap',
			(string) $sitemap_html
		);

		if ( $ok ) {
			wp_cache_set( self::SITEMAP_CACHE_KEY, (string) $sitemap_html, PublicArtifactsController::CACHE_GROUP, 300 );
		}

		return $ok;
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear_artifacts() {
		$this->delete_artifact_post( self::HOME_SLUG );
		$this->delete_artifact_post( self::SITEMAP_SLUG );

		wp_cache_delete( self::HOME_CACHE_KEY, PublicArtifactsController::CACHE_GROUP );
		wp_cache_delete( self::SITEMAP_CACHE_KEY, PublicArtifactsController::CACHE_GROUP );
	}

	/**
	 * Upsert an artifact post by slug (single-record, no-history).
	 *
	 * @param string $slug  Post slug.
	 * @param string $title Post title.
	 * @param string $html  HTML content to store.
	 *
	 * @return bool True on success.
	 */
	private function upsert_artifact_post( string $slug, string $title, string $html ): bool {
		$post_type = PublicArtifactsController::POST_TYPE;

		$existing = get_page_by_path( $slug, \OBJECT, $post_type );
		$postarr  = [
			'post_type'    => $post_type,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $html,
		];

		if ( $existing && isset( $existing->ID ) ) {
			$postarr['ID'] = (int) $existing->ID;
			$result        = wp_update_post( $postarr, true );
		} else {
			$result = wp_insert_post( $postarr, true );
		}

		return ! is_wp_error( $result ) && ! empty( $result );
	}

	/**
	 * Delete an artifact post by slug.
	 *
	 * @param string $slug Post slug.
	 *
	 * @return void
	 */
	private function delete_artifact_post( string $slug ): void {
		$post = get_page_by_path( $slug, \OBJECT, PublicArtifactsController::POST_TYPE );
		if ( $post && isset( $post->ID ) ) {
			wp_delete_post( (int) $post->ID, true );
		}
	}

	/**
	 * Build a home snapshot without requiring loopback HTTP when possible.
	 *
	 * If the front page is a static page, we render its content in a minimal HTML
	 * shell. Otherwise we fall back to a cookie-less HTTP fetch.
	 *
	 * @return string|false
	 */
	// private function build_home_snapshot_html() {
	// 	$show_on_front = get_option( 'show_on_front' );
	// 	if ( 'page' === $show_on_front ) {
	// 		$page_id = (int) get_option( 'page_on_front' );
	// 		if ( $page_id ) {
	// 			$post = get_post( $page_id );
	// 			if ( $post && isset( $post->post_content ) ) {
	// 				$content = apply_filters( 'the_content', $post->post_content );
	// 				$title   = get_bloginfo( 'name' );
	// 				return '<!doctype html><html><head><meta charset=\"utf-8\"><meta name=\"robots\" content=\"noindex,nofollow\"><title>' .
	// 					esc_html( $title ) .
	// 					'</title></head><body>' . $content . '</body></html>';
	// 			}
	// 		}
	// 	}

	// 	// Fallback: VIP-safe HTTP fetch with no cookies, short timeout already handled by WPFilesystem.
	// 	return $this->filesystem->fetch_url( \get_home_url() );
	// }

	 /**
	 * Sanitize snapshot HTML to reduce risk of serving executable scripts.
	 *
	 * @param string $html Raw HTML.
	 *
	 * @return string
	 */
	// private function sanitize_snapshot_html( string $html ): string {
	// 	// Strip script tags as a defense-in-depth measure.
	// 	$html = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $html );
	// 	return is_string( $html ) ? $html : '';
	// }
}
