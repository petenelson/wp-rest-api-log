<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Settings_General' ) ) {

	class WP_REST_API_Log_Settings_General extends WP_REST_API_Log_Settings_Base {

		static $settings_key  = 'wp-rest-api-log-settings-general';

		static public function plugins_loaded() {
			add_action( 'admin_init', array( __CLASS__, 'register_general_settings' ) );
			add_filter( 'wp-rest-api-log-settings-tabs', array( __CLASS__, 'add_tab') );
		}

		static public function add_tab( $tabs ) {
			$tabs[ self::$settings_key ] = __( 'General', 'wp-rest-api-log' );
			return $tabs;
		}

		static public function register_general_settings() {
			$key = self::$settings_key;

			register_setting( $key, $key, array( __CLASS__, 'sanitize_general_settings') );

			$section = 'general';

			add_settings_section( $section, '', null, $key );

			add_settings_field( 'logging-enabled', __( 'Enabled', 'wp-rest-api-log' ), array( __CLASS__, 'settings_yes_no' ), $key, $section,
				array( 'key' => $key, 'name' => 'logging-enabled', 'after' => '' ) );

		}

		static public function sanitize_settings( $settings ) {

			return $settings;
		}


	}

}

