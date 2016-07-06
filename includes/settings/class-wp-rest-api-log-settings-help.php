<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Settings_Help' ) ) {

	class WP_REST_API_Log_Settings_Help extends WP_REST_API_Log_Settings_Base {

		static $settings_key  = 'wp-rest-api-log-settings-help';


		static public function plugins_loaded() {
			add_action( 'admin_init', array( __CLASS__, 'register_help_settings' ) );
			add_filter( 'wp-rest-api-log-settings-tabs', array( __CLASS__, 'add_tab') );
		}


		static public function add_tab( $tabs ) {
			$tabs[ self::$settings_key ] = __( 'Help', 'wp-rest-api-log' );
			return $tabs;
		}


		static public function register_help_settings( $title ) {

			add_settings_section( 'help', '', array( __CLASS__, 'section_header' ), self::$settings_key );
		}


		static public function section_header( $args ) {
			include_once WP_REST_API_LOG_ROOT . 'admin/partials/admin-help.php';
		}

	}

}
