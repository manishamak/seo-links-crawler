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
		$error_msg = esc_html__( 'Please install composer.', 'seo-links-crawler' );
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $error_msg );
		}

		add_action(
			'admin_notices',
			function() { ?>
			<div class="notice notice-error">
				<p><?php echo $error_msg; ?></p>
			</div>
				<?php
			}
		);
	}
}
