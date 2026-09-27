<?php
/**
 * Advanced tab on the plugin's settings screen.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Settings_Advanced' ) ) {

	/**
	 * Registers the Advanced tab and its custom table settings.
	 */
	class WP_REST_API_Log_Settings_Advanced extends WP_REST_API_Log_Settings_Base {

		/**
		 * Option name used to store this tab's settings.
		 *
		 * @var string
		 */
		public static $settings_key = 'wp-rest-api-log-settings-advanced';

		/**
		 * Hooks up WordPress actions and filters.
		 *
		 * @return void
		 */
		public static function plugins_loaded() {
			add_action( 'admin_init', array( __CLASS__, 'register_advanced_settings' ) );
			add_filter( 'wp-rest-api-log-settings-tabs', array( __CLASS__, 'add_tab' ) );
		}

		/**
		 * Adds an Advanced tab.
		 *
		 * @param array $tabs List of tabs.
		 * @return array
		 */
		public static function add_tab( $tabs ) {
			$tabs[ self::$settings_key ] = __( 'Advanced', 'wp-rest-api-log' );
			return $tabs;
		}

		/**
		 * Returns the default Advanced settings.
		 *
		 * @return array
		 */
		public static function get_default_settings() {
			return array(
				'use-custom-tables' => '0',
			);
		}

		/**
		 * Registers the advanced settings.
		 *
		 * @return void
		 */
		public static function register_advanced_settings() {
			global $wpdb;

			$key = self::$settings_key;

			register_setting( $key, $key, array( __CLASS__, 'sanitize_settings' ) );

			$section = 'advanced';

			add_settings_section( $section, '', '__return_null', $key );

			$prefix = $wpdb->prefix . WP_REST_API_Log_DB::get_custom_table_prefix();

			add_settings_field(
				'use-custom-tables',
				__( 'Use Custom Tables', 'wp-rest-api-log' ),
				array( __CLASS__, 'settings_yes_no' ),
				$key,
				$section,
				array(
					'key'   => $key,
					'name'  => 'use-custom-tables',
					/* translators: %s: the database table prefix used for the custom log tables. */
					'after' => '<p class="description">' . wp_kses_post( sprintf( __( 'Create and use custom tables for posts, terms, and meta. Tables will be prefixed with "%s"', 'wp-rest-api-log' ), $prefix ) ) . '</p>',
				)
			);
		}

		/**
		 * Sanitizes the Advanced settings before they are saved.
		 *
		 * @param  array $settings Submitted settings.
		 * @return array
		 */
		public static function sanitize_settings( $settings ) {
			return $settings;
		}
	}
}
