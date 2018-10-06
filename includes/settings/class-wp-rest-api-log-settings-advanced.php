<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Settings_Advanced' ) ) {

	class WP_REST_API_Log_Settings_Advanced extends WP_REST_API_Log_Settings_Base {

		static $settings_key  = 'wp-rest-api-log-settings-advanced';

		static public function plugins_loaded() {
			add_action( 'admin_init', array( __CLASS__, 'register_advanced_settings' ) );
			add_filter( 'wp-rest-api-log-settings-tabs', array( __CLASS__, 'add_tab') );
		}


		static public function add_tab( $tabs ) {
			$tabs[ self::$settings_key ] = __( 'Advanced', 'wp-rest-api-log' );
			return $tabs;
		}


		static public function get_default_settings() {
			return array(
				'use-custom-tables'   => '0',
			);
		}


		static public function register_advanced_settings() {
			global $wpdb;

			$key = self::$settings_key;

			register_setting( $key, $key, array( __CLASS__, 'sanitize_settings') );

			$section = 'advanced';

			add_settings_section( $section, '', null, $key );

			$prefix = $wpdb->prefix . WP_REST_API_Log_DB::get_custom_table_prefix();

			add_settings_field( 'use-custom-tables', __( 'Use Custom Tables', 'wp-rest-api-log' ), array( __CLASS__, 'settings_yes_no' ), $key, $section,
				array(
					'key' => $key,
					'name' => 'use-custom-tables',
					'after' => '<p class="description">' . wp_kses_post( sprintf( __( 'Create and use custom tables for posts, terms, and meta. Tables will be prefixed with "%s"', 'wp-rest-api-log' ), $prefix ) ) . '</p>',
				)
			);
		}


		static public function sanitize_settings( $settings ) {
			return $settings;
		}
	}
}
