<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Admin' ) ) {

	class WP_REST_API_Log_Admin {


		public function plugins_loaded() {
			add_filter( 'post_type_link', array( $this, 'entry_permalink' ), 10, 2 );
			add_action( 'admin_init', array( $this, 'register_scripts' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			add_action( 'admin_init', array( $this, 'create_migrate_legacy_db_cron' ) );

		}


		public function admin_menu() {

			add_submenu_page(
				null,
				__( 'REST API Log Entry', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'',
				'manage_options',
				WP_REST_API_Log_Common::PLUGIN_NAME . '-view-entry',
				array( $this, 'display_log_entry')
			);

			global $submenu;
			if ( ! empty( $submenu['tools.php'] ) ) {
				foreach ( $submenu['tools.php'] as &$item ) {
					if ( 'edit.php?post_type=wp-rest-api-log' === $item[2] ) {
						$item[0] = __( 'REST API Log', 'wp-rest-api-log' );
						if ( ! empty( $item[3] ) ) {
							$item[3] = $item[0];
						}
					}
				}
			}

		}




		public function register_scripts() {

			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.min' : '';

			// https://highlightjs.org/
			$highlight_version = apply_filters( 'wp-rest-api-log-admin-highlist-js-version', '9.2.0' );
			$highlight_style   = apply_filters( 'wp-rest-api-log-admin-highlist-js-version', 'github' );

			wp_register_script( 'wp-rest-api-log-admin-highlight-js',   '//cdnjs.cloudflare.com/ajax/libs/highlight.js/' . $highlight_version . '/highlight.min.js' );
			wp_register_style(  'wp-rest-api-log-admin-highlight-js',  '//cdnjs.cloudflare.com/ajax/libs/highlight.js/' . $highlight_version . '/styles/' . $highlight_style . '.min.css' );

			wp_register_script( 'wp-rest-api-log-admin', plugin_dir_url( __FILE__ ) . 'js/wp-rest-api-log-admin' . $min . '.js', 'jquery', WP_REST_API_Log_Common::VERSION );
			wp_register_style(  'wp-rest-api-log-admin', plugin_dir_url( __FILE__ ) . 'css/wp-rest-api-log-admin' . $min . '.css', '', WP_REST_API_Log_Common::VERSION );

			// http://trentrichardson.com/examples/timepicker/
			//wp_enqueue_script( 'jquery-ui-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.4.5/jquery-ui-timepicker-addon.min.js' );
			//wp_enqueue_style( 'jquery-ui-timepicker', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-ui-timepicker-addon/1.4.5/jquery-ui-timepicker-addon.min.css' );

		}


		public function display_log_entry() {

			include_once apply_filters( 'wp-rest-api-log-admin-view-entry-template', plugin_dir_path( __FILE__ ) .'partials/wp-rest-api-log-view-entry.php' );

			wp_enqueue_script( 'wp-rest-api-log-admin-highlight-js' );
			wp_enqueue_style(  'wp-rest-api-log-admin-highlight-js' );
			wp_enqueue_script( 'wp-rest-api-log-admin' );

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

		/**
		 * Creates a one-time cron job to migrate the legacy tables to
		 * custom post type records
		 *
		 * @return void
		 */
		public function create_migrate_legacy_db_cron() {
			$migrate_completed = get_option( 'wp-rest-api-log-migrate-completed' );
			if ( false === $migrate_completed ) {
				wp_schedule_single_event( time(), 'wp-rest-api-log-migrate-legacy-db' ); 
			}
		}


	}

}
