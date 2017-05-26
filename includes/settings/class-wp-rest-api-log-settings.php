<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Settings' ) ) {

	class WP_REST_API_Log_Settings extends WP_REST_API_Log_Settings_Base {

		/**
		 * Wire up WordPress hooks and filters.
		 *
		 * @return void
		 */
		static public function plugins_loaded() {
			// admin menus
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
			add_action( 'admin_notices', array( __CLASS__, 'activation_admin_notice' ) );

			// filters to get plugin settings
			add_filter( 'wp-rest-api-log-setting-is-enabled', array( __CLASS__, 'filter_setting_is_enabled' ), 10, 3 );
			add_filter( 'wp-rest-api-log-setting-get', array( __CLASS__, 'setting_get' ), 10, 3 );
		}


		/**
		 * Displays an admin notice if the plugin was just activated.
		 *
		 * @return void
		 */
		static public function activation_admin_notice() {
			if ( '1' === get_option( 'wp-rest-api-log-plugin-activated' ) ) {
				?>
					<div class="updated">
						<p>
							<?php echo wp_kses_post( sprintf( __( '<strong>REST API Log activated!</strong> Please <a href="%s">visit the Settings page</a> to customize the settings.', 'wp-rest-api-log' ), esc_url( admin_url( 'options-general.php?page=wp-rest-api-log-settings' ) ) ) ); ?>
						</p>
					</div>
				<?php
				delete_option( 'wp-rest-api-log-plugin-activated' );
			}
		}


		static public function deactivation_hook() {
			// placeholder in case we need deactivation code
		}

		/**
		 * Creates default settings for the plugin.
		 *
		 * @return void
		 */
		static public function create_default_settings() {
			// create default settings
			add_option( WP_REST_API_Log_Settings_General::$settings_key, WP_REST_API_Log_Settings_General::get_default_settings(), '', $autoload = 'no' );
			add_option( WP_REST_API_Log_Settings_Routes::$settings_key,  WP_REST_API_Log_Settings_Routes::get_default_settings(),  '', $autoload = 'no' );
		}

		/**
		 * Registers the REST API Log admin menu,
		 *
		 * @return void
		 */
		static public function admin_menu() {
			add_options_page(
				__( 'REST API Log Settings', 'wp-rest-api-log' ),
				__( 'REST API Log', 'wp-rest-api-log' ),
				'manage_options',
				self::$settings_page,
				array( __CLASS__, 'options_page' ),
				30
				);
		}

		/**
		 * Displays the settings page for the plugin.
		 *
		 * @return void
		 */
		static public function options_page() {

			$tab = self::current_tab(); ?>
			<div class="wrap">
				<?php self::plugin_options_tabs(); ?>
				<form method="post" action="options.php" class="options-form">
					<?php settings_fields( $tab ); ?>
					<?php do_settings_sections( $tab ); ?>
					<?php
						if ( WP_REST_API_Log_Settings_Help::$settings_key !== $tab ) {
							submit_button( __( 'Save Changes' ), 'primary', 'submit', true );
						}
					?>
				</form>
			</div>
			<?php

			$settings_updated = filter_input( INPUT_GET, 'settings-updated', FILTER_SANITIZE_STRING );
			if ( ! empty( $settings_updated ) ) {
				do_action( 'wp-rest-api-log-settings-updated' );
				flush_rewrite_rules();
			}

		}


		static private function current_tab() {
			$current_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
			return empty( $current_tab ) ? 'wp-rest-api-log-settings-general' : $current_tab;
		}


		/**
		 * Outputs the HTML for the settings tabs.
		 *
		 * @return void
		 */
		static private function plugin_options_tabs() {
			$current_tab = self::current_tab();

			echo '<h2>' . esc_html__( 'Settings' ) . ' &rsaquo; REST API Log</h2><h2 class="nav-tab-wrapper">';

			$tabs = apply_filters( 'wp-rest-api-log-settings-tabs', array() );

			foreach ( $tabs as $tab_key => $tab_caption ) {
				$active = $current_tab === $tab_key ? 'nav-tab-active' : '';

				// Build URL for tab.
				$url = add_query_arg(
					array(
						'page' => urlencode( self::$settings_page ),
						'tab' => urlencode( $tab_key ),
					),
					admin_url( 'options-general.php' )
				);

				if ( is_ssl() ) {
					$url = set_url_scheme( $url, 'https' );
				}

				// Output the tab anchor tag.
				printf( '<a class="nav-tab %1$s" href="%2$s">%3$s</a>',
					sanitize_html_class( $active ),
					esc_url( $url ),
					esc_html( $tab_caption )
				);

			}
			echo '</h2>';
		}


	}

}