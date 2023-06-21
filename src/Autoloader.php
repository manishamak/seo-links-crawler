<?php
/**
 * Includes composer autoloader file
 */

namespace Slc\SeoLinksCrawler;

defined( 'ABSPATH' ) || exit;

/**
 * Autoloader class
 */
class Autoloader {

	/**
	 * Require the autoloader and return the result.
	 *
	 * If autoloader is not present then log the error and display admin notice.
	 *
	 * @return boolean
	 */
	public static function init() {
		$autoloader = SLC_PLUGIN_PATH . '/vendor/autoload.php';

		if ( ! is_readable( $autoloader ) ) {
			self::missing_autoloader();
			return false;
		}

		$autoloader_result = require $autoloader;
		if ( ! $autoloader_result ) {
			return false;
		}
		return $autoloader_result;
	}

	/**
	 * If the autoloader is missing, display admin notice
	 */
	protected static function missing_autoloader() {
		$error_msg = __( 'Please install composer in order to make <strong>SEO links Crawler</strong> working.', 'seo-links-crawler' );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( wp_kses_post( $error_msg ) );
		}

		add_action(
			'admin_notices',
			function() use ( $error_msg ) { ?>
			<div class="notice notice-error">
				<p><?php echo wp_kses_post( $error_msg ); ?></p>
			</div>
				<?php
			}
		);
	}
}
