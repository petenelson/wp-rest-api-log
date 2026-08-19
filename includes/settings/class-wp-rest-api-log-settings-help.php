<?php
/**
 * Help tab on the plugin's settings screen.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Settings_Help' ) ) {

	/**
	 * Registers the Help tab and renders its contents.
	 */
	class WP_REST_API_Log_Settings_Help extends WP_REST_API_Log_Settings_Base {

		/**
		 * Option name used to store this tab's settings.
		 *
		 * @var string
		 */
		public static $settings_key = 'wp-rest-api-log-settings-help';


		/**
		 * Hooks up WordPress actions and filters.
		 *
		 * @return void
		 */
		public static function plugins_loaded() {
			add_action( 'admin_init', array( __CLASS__, 'register_help_settings' ) );
			add_filter( 'wp-rest-api-log-settings-tabs', array( __CLASS__, 'add_tab' ) );
		}


		/**
		 * Adds a Help tab.
		 *
		 * @param array $tabs List of tabs.
		 * @return array
		 */
		public static function add_tab( $tabs ) {
			$tabs[ self::$settings_key ] = __( 'Help', 'wp-rest-api-log' );
			return $tabs;
		}


		/**
		 * Registers the Help settings section.
		 *
		 * @return void
		 */
		public static function register_help_settings() {

			add_settings_section( 'help', '', array( __CLASS__, 'section_header' ), self::$settings_key );
		}


		/**
		 * Renders the Help section contents.
		 *
		 * @return void
		 */
		public static function section_header() {
			include_once WP_REST_API_LOG_ROOT . 'admin/partials/admin-help.php';
		}
	}

}
