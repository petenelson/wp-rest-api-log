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

			global $wp_rest_api_log_display_entries;

			$db = new WP_REST_API_Log_DB();

			$this->enqueue_scripts();

			$data = array(
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'route'   => site_url( rest_get_url_prefix() . '/wp-rest-api-log/entries' ),
				'id'      => absint( filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT ) ),
				);

			$entries = $db->search( array( 'id' => $data['id'] ) );

			wp_localize_script( $this->plugin_name(), 'wp_rest_api_log_admin', $data );

			if ( ! empty( $entries ) && ! empty( $entries->paged_records) ) {
				$wp_rest_api_log_display_entries = $entries->paged_records;
			}

			require_once dirname( __FILE__ ) . '/partials/wp-rest-api-log-display-entries.php';

		}


		public function entries_to_html( $entries ) {

			global $wp_rest_api_log_display_entries;
			$wp_rest_api_log_display_entries = $entries;

			ob_start();
			require_once plugin_dir_path( __FILE__ ) . 'partials/wp-rest-api-log-display-entries-table.php';
			$html = ob_get_contents();
			ob_end_clean();

			return $html;
		}


		private function enqueue_scripts() {

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-datepicker' );

			// https://highlightjs.org/
			wp_enqueue_script( 'highlight-js', '//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/highlight.min.js' );
			wp_enqueue_style( 'highlight-js', '//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/styles/default.min.css' );

			// http://trentrichardson.com/examples/timepicker/
			wp_enqueue_script( 'jquery-ui-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.4.5/jquery-ui-timepicker-addon.min.js' );
			wp_enqueue_style( 'jquery-ui-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.4.5/jquery-ui-timepicker-addon.min.css' );

			wp_enqueue_script( $this->plugin_name(), plugin_dir_url( __FILE__ ) . 'js/wp-rest-api-log-admin.js', 'jquery', WP_REST_API_Log_Common::$version );

			wp_enqueue_style( 'jquery-ui-datepicker', 'https://code.jquery.com/ui/1.11.3/themes/smoothness/jquery-ui.css' );
			wp_enqueue_style( $this->plugin_name(), plugin_dir_url( __FILE__ ) . 'css/wp-rest-api-log-admin.css', '', WP_REST_API_Log_Common::$version );


		}


		private function plugin_name() {
			return WP_REST_API_Log_Common::$plugin_name . '-admin';
		}



	}

}
