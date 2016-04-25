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

			register_setting( $key, $key, array( __CLASS__, 'sanitize_settings') );

			$section = 'general';

			add_settings_section( $section, '', null, $key );

			add_settings_field( 'logging-enabled', __( 'Enabled', 'wp-rest-api-log' ), array( __CLASS__, 'settings_yes_no' ), $key, $section,
				array( 'key' => $key, 'name' => 'logging-enabled', 'after' => '' ) );

			add_settings_field( 'purge-days', __( 'Purge Old Entries', 'rest-api-toolbox' ), array( __CLASS__, 'settings_input' ), $key, $section,
				array(
					'key' => $key,
					'name' => 'purge-days',
					'after' => __( 'Delete entries older than this many days ', 'rest-api-toolbox' ),
					'size' => 3,
					'maxlength' => 3,
					)
				);

		}

		static public function sanitize_settings( $settings ) {

			$settings['purge-days'] = empty( $settings['purge-days'] ) ? '' : absint( $settings['purge-days'] );

			if ( 0 === $settings['purge-days'] ) {
				$settings['purge-days'] = '';
			}

			return $settings;
		}


	}

}

