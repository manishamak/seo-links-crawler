<?php

namespace Slc\SeoLinksCrawler\Public;

use Slc\SeoLinksCrawler\Vip\VipCompat;

defined( 'ABSPATH' ) || exit;

/**
 * Public endpoints for VIP-safe artifacts.
 *
 * Registers rewrite rules for `/home.html` and `/sitemap.html` and serves the
 * stored HTML from the artifact CPT with cache-first reads.
 */
class PublicArtifactsController {

	/**
	 * Query var used by rewrite rules.
	 *
	 * @var string
	 */
	const QUERY_VAR = 'slc_artifact';

	/**
	 * CPT used to store artifacts on VIP.
	 *
	 * @var string
	 */
	const POST_TYPE = 'slc_artifact';

	/**
	 * Cache group for artifact HTML.
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'seo-links-crawler';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		if ( ! VipCompat::is_vip() ) {
			return;
		}

		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'init', [ $this, 'register_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'register_query_var' ] );
		add_action( 'template_redirect', [ $this, 'maybe_serve_artifact' ], 0 );
	}

	/**
	 * Register the internal artifact CPT.
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			[
				'labels'              => [
					'name' => __( 'SLC Artifacts', 'seo-links-crawler' ),
				],
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'exclude_from_search' => true,
				'supports'            => [ 'title', 'editor' ],
			]
		);
	}

	/**
	 * Register rewrite rules for artifact endpoints.
	 *
	 * @return void
	 */
	public function register_rewrite_rules() {
		self::add_rewrite_rules();
	}

	/**
	 * Add rewrite rules for artifact endpoints.
	 *
	 * Safe to call during activation before hooks are registered.
	 *
	 * @return void
	 */
	public static function add_rewrite_rules() {
		add_rewrite_rule( '^home\.html$', 'index.php?' . self::QUERY_VAR . '=home', 'top' );
		add_rewrite_rule( '^sitemap\.html$', 'index.php?' . self::QUERY_VAR . '=sitemap', 'top' );
	}

	/**
	 * Add query var used by our rewrite rules.
	 *
	 * @param array $vars Query vars.
	 *
	 * @return array
	 */
	public function register_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Serve the artifact endpoint if matched.
	 *
	 * @return void
	 */
	public function maybe_serve_artifact() {
		$key = get_query_var( self::QUERY_VAR );
		if ( empty( $key ) ) {
			return;
		}

		if ( 'home' !== $key && 'sitemap' !== $key ) {
			status_header( 404 );
			exit;
		}

		$cache_key = 'slc_artifact_' . $key . '_html';
		$html      = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( ! is_string( $html ) || '' === $html ) {
			$post = get_page_by_path( 'slc-' . $key, \OBJECT, self::POST_TYPE );
			$html = ( $post && isset( $post->post_content ) ) ? (string) $post->post_content : '';

			if ( '' !== $html ) {
				wp_cache_set( $cache_key, $html, self::CACHE_GROUP, 300 );
			}
		}

		if ( '' === $html ) {
			status_header( 404 );
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo esc_html__( 'Artifact not available yet. Please run the crawl first.', 'seo-links-crawler' );
			exit;
		}

		// Security + SEO: this is a generated artifact and should not be indexed.
		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex, nofollow', true );
		header( 'X-Content-Type-Options: nosniff', true );

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}

