<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Admin' ) ) {

	class WP_REST_API_Log_Admin {


		public function plugins_loaded() {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}


		public function admin_init() {

		}


		public function admin_menu() {
			add_submenu_page( 'tools.php', 'WP REST API Log', 'WP REST API Log', 'manage_options', WP_REST_API_Log_Common::$plugin_name, array( $this, 'display_entries' ) );
		}


		public function display_entries() {

			wp_enqueue_script( $this->plugin_name(), plugin_dir_url( __FILE__ ) . 'js/wp-rest-api-log-admin.js', 'jquery', WP_REST_API_Log_Common::$version );

			// TODO query entries
			// TODO store in global

			$data = array(
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'route' => site_url( rest_get_url_prefix() . '/wp-rest-api-log/entries' ),
				);

			wp_localize_script( $this->plugin_name(), 'wp_rest_api_log_admin', $data );

			require_once dirname( __FILE__ ) . '/partials/wp-rest-api-log-display-entries.php';


		}


		private function plugin_name() {
			return WP_REST_API_Log_Common::$plugin_name . '-admin';
		}



	}

}
