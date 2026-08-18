<?php
/**
 * Loads the plugin's translations.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_i18n' ) ) {

	/**
	 * Handles internationalization for the plugin.
	 *
	 * The lowercase "i18n" in the class name is retained for backwards
	 * compatibility; renaming it would break code that references the class.
	 */
	class WP_REST_API_Log_i18n { // phpcs:ignore PEAR.NamingConventions.ValidClassName.Invalid -- Class name is part of the plugin's public API.

		/**
		 * Loads the plugin text domain.
		 *
		 * @return void
		 */
		public static function plugins_loaded() {

			load_plugin_textdomain(
				'wp-rest-api-log',
				false,
				dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
			);
		}
	}
}
