<?php
/**
 * General tab on the plugin's settings screen.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Settings_General' ) ) {

	/**
	 * Registers the General tab and its logging and retention settings.
	 */
	class WP_REST_API_Log_Settings_General extends WP_REST_API_Log_Settings_Base {

		/**
		 * Option name used to store this tab's settings.
		 *
		 * @var string
		 */
		public static $settings_key = 'wp-rest-api-log-settings-general';


		/**
		 * Hooks up WordPress actions and filters.
		 *
		 * @return void
		 */
		public static function plugins_loaded() {
			add_action( 'admin_init', array( __CLASS__, 'register_general_settings' ) );
			add_filter( 'wp-rest-api-log-settings-tabs', array( __CLASS__, 'add_tab' ) );
			add_action( 'admin_notices', array( __CLASS__, 'display_db_notice' ) );
			add_action( 'wp_ajax_wp-rest-api-log-db-notice-dismiss', array( __CLASS__, 'dismiss_db_notice' ) );
		}


		/**
		 * Adds a General tab.
		 *
		 * @param  array $tabs List of tabs.
		 * @return array
		 */
		public static function add_tab( $tabs ) {
			$tabs[ self::$settings_key ] = __( 'General', 'wp-rest-api-log' );
			return $tabs;
		}


		/**
		 * Returns the default values for this settings group.
		 *
		 * @return array
		 */
		public static function get_default_settings() {
			return array(
				'logging-enabled' => '1',
				'purge-days'      => '7',
			);
		}


		/**
		 * Registers the General settings sections and fields.
		 *
		 * @return void
		 */
		public static function register_general_settings() {
			$key = self::$settings_key;

			register_setting( $key, $key, array( __CLASS__, 'sanitize_settings' ) );

			$section = 'general';

			add_settings_section( $section, '', null, $key );

			add_settings_field(
				'logging-enabled',
				__( 'Enabled', 'wp-rest-api-log' ),
				array( __CLASS__, 'settings_yes_no' ),
				$key,
				$section,
				array(
					'key'   => $key,
					'name'  => 'logging-enabled',
					'after' => '',
				)
			);

			$purge_button_html = self::get_purge_button_html();

			add_settings_field(
				'purge-days',
				__( 'Days to Retain Old Entries', 'wp-rest-api-log' ),
				array( __CLASS__, 'settings_input' ),
				$key,
				$section,
				array(
					'key'       => $key,
					'name'      => 'purge-days',
					'after'     => '<p class="description">' . wp_kses_post(
						sprintf(
							/* translators: %1$s: HTML markup for the "purge entries" button. */
							__( 'Entries older than this will be automatically cleaned up, leave blank to keep all entries. %1$s', 'wp-rest-api-log' ),
							$purge_button_html
						)
					) . '</p>',
					'size'      => 3,
					'maxlength' => 3,
				)
			);

			add_settings_field(
				'ip-address-display',
				__( 'IP Address Display', 'wp-rest-api-log' ),
				array( __CLASS__, 'settings_check_radio_list' ),
				$key,
				$section,
				array(
					'key'     => $key,
					'name'    => 'ip-address-display',
					'type'    => 'radio',
					'after'   => '<p class="description">' . __( 'Sets the IP address displayed in the list of log entries.', 'wp-rest-api-log' ) . '</p>',
					'items'   => array(
						'ip_address'           => __( 'IP Address', 'wp-rest-api-log' ),
						'http_x_forwarded_for' => __( 'HTTP X Forwarded For', 'wp-rest-api-log' ),
					),
					'default' => array( 'ip_address' ),
				)
			);
		}


		/**
		 * Sanitizes the General settings before they are saved.
		 *
		 * @param  array $settings Raw submitted settings.
		 * @return array
		 */
		public static function sanitize_settings( $settings ) {

			$settings['purge-days'] = empty( $settings['purge-days'] ) ? '' : absint( $settings['purge-days'] );

			if ( 0 === $settings['purge-days'] ) {
				$settings['purge-days'] = '';
			}

			return $settings;
		}

		/**
		 * Displays an admin notice about running the plugin in production.
		 *
		 * @return void
		 */
		public static function display_db_notice() {

			$screen = get_current_screen();

			if ( 'settings_page_wp-rest-api-log-settings' === $screen->id && false === get_option( 'wp-rest-api-log-db-notice-dismissed' ) ) {
				?>
					<div class="notice notice-warning is-dismissible" id="wp-rest-api-log-admin-db-notice">
						<p><?php esc_html_e( 'Use caution when using this plugin on a production site with a large amount of REST API traffic. It logs a large amount of data and can greatly increase the size of your database.', 'wp-rest-api-log' ); ?></p>
					</div>

					<script type="text/javascript">
						jQuery( '#wp-rest-api-log-admin-db-notice' ).on( 'click', '.notice-dismiss', function() {
							jQuery.post( window.ajaxurl, {
								action: 'wp-rest-api-log-db-notice-dismiss',
							} );
						} );
					</script>
				<?php
			}
		}

		/**
		 * Gets the HTML markup for the purge button.
		 *
		 * @return string
		 */
		public static function get_purge_button_html() {

			$total_count = 0;
			$html        = '';

			$data = filter_var_array(
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check of the current admin page; no state is changed.
				$_GET,
				array(
					'page' => WP_REST_API_Log_Common::filter_strip_all_tags(),
				)
			);

			if ( WP_REST_API_Log_Settings_Base::$settings_page === $data['page'] ) {
				$total_count = absint( count( WP_REST_API_Log_DB::get_all_log_ids() ) );
			}

			if ( ! empty( $total_count ) ) {
				ob_start();
				?>

					<p>
						<a class="button wp-rest-api-log-purge-all" href="#purge-log" class="button">
							<?php // translators: Prompt for purging a number of log entries. ?>
							<?php echo esc_html( sprintf( __( 'Purge All %1$s Entries Now', 'wp-rest-api-log' ), number_format( $total_count ) ) ); ?>
						</a>
					</p>
					<span class="spinner hidden wp-rest-api-log-purge-all-spinner"></span>
					<span class="hidden wp-rest-api-log-purge-all-status"><?php esc_html_e( 'Purging entries...', 'wp-rest-api-log' ); ?></span>

				<?php

				$html = ob_get_clean();
			}

			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Hook name is the function name and is part of the public API.
			return apply_filters( __FUNCTION__, $html );
		}

		/**
		 * Sets an option to dismiss the admin production database notice.
		 *
		 * @return void
		 */
		public static function dismiss_db_notice() {
			$key = 'wp-rest-api-log-db-notice-dismissed';
			delete_option( $key );
			add_option( $key, '1', '', 'no' );
		}
	}

}

