<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Admin' ) ) {

	class WP_REST_API_Log_Admin {


		public function plugins_loaded() {
			add_filter( 'post_type_link',     array( $this, 'entry_permalink' ), 10, 2 );
			add_filter( 'get_edit_post_link', array( $this, 'entry_permalink' ), 10, 2 );

			add_action( 'admin_init', array( $this, 'update_role_capabilities' ) );
			add_action( 'admin_init', array( $this, 'register_scripts' ) );

			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			add_action( 'wp_ajax_wp-rest-api-migrate-db', array( $this, 'migrate_db') );
			add_action( 'admin_footer',                   array( $this, 'enqueue_migrate_script') );

		}


		public function migrate_db() {
			$nonce = filter_input( INPUT_GET, 'nonce', FILTER_SANITIZE_STRING );
			if ( wp_verify_nonce( $nonce, 'wp-rest-api-log-migrate' ) ) {
				$db = new WP_REST_API_Log_DB();
				$post_ids = $db->migrate_db_records();
				wp_send_json( array( 'completed' => true, 'post_ids' => $post_ids ) );
			}
		}


		public function enqueue_migrate_script() {

			if ( '1' !== get_option( 'wp-rest-api-log-migrate-completed' ) ) {
				$data = array(
					'action' => 'wp-rest-api-migrate-db',
					'nonce'  => wp_create_nonce( 'wp-rest-api-log-migrate' )
					);

				wp_enqueue_script(  'wp-rest-api-log-admin' );
				wp_localize_script( 'wp-rest-api-log-admin', 'WP_REST_API_Log_Migrate_Data', $data );
			}

		}


		public function admin_menu() {

			add_submenu_page(
				null,
				__( 'REST API Log Entry', 'wp-rest-api-log' ),
				'',
				'read_' . WP_REST_API_Log_DB::POST_TYPE,
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
			$post = get_post( $post );
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
		 * Updates capabilities for roles to allow them access to the
		 * plugin's custom post types
		 *
		 * @return
		 */
		public function update_role_capabilities() {

			$post_type      = WP_REST_API_Log_DB::POST_TYPE;
			$plugin_name    = WP_REST_API_Log_Common::PLUGIN_NAME;

			$caps_version   = '2016-04-09-02';
			$roles          = apply_filters( "{$plugin_name}-default-roles", array( 'administrator') );

			$caps           = apply_filters( "{$plugin_name}-default-caps", array(
				"read_{$post_type}",
				"edit_{$post_type}",
				"edit_{$post_type}s",
				"delete_{$post_type}",
				)
			);

			$option_key     = "{$plugin_name}-caps-version";
			$version        = get_option( $option_key, false );

			// build a unique string for the version based on the roles
			foreach( $roles as $role ) {
				$caps_version .= '_' . $role;
			}

			$caps_version   = md5( $caps_version );

			if ( false === $version ) {
				add_option( $option_key, $caps_version, '', 'no' );
			}

			if ( $version !== $caps_version ) {

				foreach ( $roles as $role ) {
					$role = get_role( $role );

					if ( ! empty( $role ) ) {

						$role->remove_cap( 'edit_wp-rest-api-logs' );

						foreach( $caps as $cap ) {
							$role->add_cap( $cap );
						}

					}
				}

				// store the update version string so we only update the caps
				// when something has changed
				update_option( $option_key, $caps_version );

			}

		}


	}

}
