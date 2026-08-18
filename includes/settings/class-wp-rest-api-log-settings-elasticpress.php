<?php
/**
 * ElasticPress tab on the plugin's settings screen.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Settings_ElasticPress' ) ) {

	/**
	 * Registers the ElasticPress tab and its logging settings.
	 */
	class WP_REST_API_Log_Settings_ElasticPress extends WP_REST_API_Log_Settings_Base {

		/**
		 * Option name used to store this tab's settings.
		 *
		 * @var string
		 */
		public static $settings_key = 'wp-rest-api-log-settings-elasticpress';


		/**
		 * Hooks up WordPress actions and filters.
		 *
		 * @return void
		 */
		public static function plugins_loaded() {
			add_action( 'admin_init', array( __CLASS__, 'register_elasticpress_settings' ) );
			add_filter( 'wp-rest-api-log-settings-tabs', array( __CLASS__, 'add_tab' ) );
		}


		/**
		 * Adds an ElasticPress tab.
		 *
		 * @param  array $tabs List of tabs.
		 * @return array
		 */
		public static function add_tab( $tabs ) {
			$tabs[ self::$settings_key ] = __( 'ElasticPress', 'wp-rest-api-log' );
			return $tabs;
		}


		/**
		 * Returns the default values for this settings group.
		 *
		 * @return array
		 */
		public static function get_default_settings() {
			return array(
				'logging-enabled' => '1',
			);
		}


		/**
		 * Registers the ElasticPress settings sections and fields.
		 *
		 * @return void
		 */
		public static function register_elasticpress_settings() {
			$key = self::$settings_key;

			register_setting( $key, $key, array( __CLASS__, 'sanitize_settings' ) );

			$section = 'elasticpress';

			add_settings_section( $section, '', null, $key );

			add_settings_field(
				'logging-enabled',
				__( 'Log ElasticPress API Calls', 'wp-rest-api-log' ),
				array( __CLASS__, 'settings_yes_no' ),
				$key,
				$section,
				array(
					'key'   => $key,
					'name'  => 'logging-enabled',
					'after' => '',
				)
			);
		}


		/**
		 * Sanitizes the ElasticPress settings before they are saved.
		 *
		 * @param  array $settings Raw submitted settings.
		 * @return array
		 */
		public static function sanitize_settings( $settings ) {

			return $settings;
		}
	}

}
