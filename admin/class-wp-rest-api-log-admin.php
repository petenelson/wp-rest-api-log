<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Admin' ) ) {

	class WP_REST_API_Log_Admin {


		public function plugins_loaded() {
			add_filter( 'post_type_link', array( $this, 'entry_permalink' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'register_scripts' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		}


		public function admin_menu() {
			add_submenu_page(
				NULL,
				__( 'REST API Log Entry', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'', // menu title
				'manage_options',
				WP_REST_API_Log_Common::PLUGIN_NAME . '-view-entry',
				array( $this, 'display_log_entry')
			);
		}




		public function register_scripts() {

			// https://highlightjs.org/
			wp_register_script( $this->plugin_name() .'-highlight-js',  '//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/highlight.min.js' );
			wp_register_style( $this->plugin_name() . '-highlight-css', '//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.6/styles/default.min.css' );

			wp_register_script( $this->plugin_name(), plugin_dir_url( __FILE__ ) . 'js/wp-rest-api-log-admin.min.js', 'jquery', WP_REST_API_Log_Common::VERSION );
			wp_register_style( $this->plugin_name(), plugin_dir_url( __FILE__ ) . 'css/wp-rest-api-log-admin.min.css', '', WP_REST_API_Log_Common::VERSION );

			// http://trentrichardson.com/examples/timepicker/
			//wp_enqueue_script( 'jquery-ui-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.4.5/jquery-ui-timepicker-addon.min.js' );
			//wp_enqueue_style( 'jquery-ui-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.4.5/jquery-ui-timepicker-addon.min.css' );

		}


		public function display_log_entry() {

			$template = plugin_dir_path( __FILE__ ) .'partials/wp-rest-api-log-view-entry.php';

			include_once $template;

		}


		public function entry_permalink( $permalink, $post ) {

			if ( WP_REST_API_Log_DB::POST_TYPE === $post->post_type ) {
				$permalink = add_query_arg( array(
					'page'  => WP_REST_API_Log_Common::PLUGIN_NAME . '-view-entry',
					'id'    => urlencode( $post->ID ),
					), admin_url( 'tools.php' ) );
			}
			return $permalink;
		}


		private function plugin_name() {
			return WP_REST_API_Log_Common::PLUGIN_NAME . '-admin';
		}



	}

}
