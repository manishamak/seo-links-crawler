<?php

namespace Slc\SeoLinksCrawler\Vip;

defined( 'ABSPATH' ) || exit;

/**
 * VIP-compatible fallbacks for functions that may exist only in VIP
 * environments (VIP Go / VIP Classic).
 */
final class VipCompat {

	/**
	 * Whether we appear to be running inside a WordPress VIP environment.
	 *
	 * We use feature detection (VIP functions exist) rather than hosting
	 * string checks.
	 *
	 * @return bool
	 */
	public static function is_vip(): bool {
		return function_exists( 'vip_safe_wp_remote_get' ) || function_exists( 'vip_error_log' );
	}

	/**
	 * Fetch a URL safely on VIP, falling back to WP HTTP API elsewhere.
	 *
	 * @param string $url  URL to fetch.
	 * @param array  $args wp_remote_get-like arguments.
	 *
	 * @return array|\WP_Error
	 */
	public static function safe_wp_remote_get( string $url, array $args ) {
		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			return vip_safe_wp_remote_get( $url, $args );
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.wp_remote_get_wp_remote_get
		return wp_remote_get( $url, $args );
	}

	/**
	 * Log errors in VIP-compatible way.
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 */
	public static function log_error( string $message ): void {
		if ( function_exists( 'vip_error_log' ) ) {
			vip_error_log( $message );
			return;
		}

		// On non-VIP environments, only log when WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log
		}
	}
}
