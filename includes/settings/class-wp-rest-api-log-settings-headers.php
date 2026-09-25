<?php
/**
 * Headers tab on the plugin's settings screen.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Settings_Headers' ) ) {

	/**
	 * Registers the Headers tab and the lists of headers whose values are
	 * redacted before a log entry is stored.
	 */
	class WP_REST_API_Log_Settings_Headers extends WP_REST_API_Log_Settings_Base {

		/**
		 * Option name used to store this tab's settings.
		 *
		 * @var string
		 */
		public static $settings_key = 'wp-rest-api-log-settings-headers';

		/**
		 * Hooks up WordPress actions and filters.
		 *
		 * @return void
		 */
		public static function plugins_loaded() {
			add_action( 'admin_init', array( __CLASS__, 'register_headers_settings' ) );
			add_filter( 'wp-rest-api-log-settings-tabs', array( __CLASS__, 'add_tab' ) );
		}

		/**
		 * Adds a Headers tab.
		 *
		 * @param array $tabs List of tabs.
		 * @return array
		 */
		public static function add_tab( $tabs ) {
			$tabs[ self::$settings_key ] = __( 'Headers', 'wp-rest-api-log' );
			return $tabs;
		}

		/**
		 * Gets the request headers whose values are redacted by default.
		 *
		 * @return array
		 */
		public static function default_request_headers() {
			return array(
				'Authorization',
				'Proxy-Authorization',
				'Cookie',
				'X-Api-Key',
				'Api-Key',
				'X-Auth-Token',
				'X-Csrf-Token',
				'X-WP-Nonce',
			);
		}

		/**
		 * Gets the response headers whose values are redacted by default.
		 *
		 * @return array
		 */
		public static function default_response_headers() {
			return array(
				'Set-Cookie',
				'Authorization',
				'WWW-Authenticate',
				'Proxy-Authenticate',
				'X-WP-Nonce',
			);
		}

		/**
		 * Gets the default Headers settings.
		 *
		 * @return array
		 */
		public static function get_default_settings() {
			return array(
				'redacted-request-headers'  => implode( "\n", self::default_request_headers() ),
				'redacted-response-headers' => implode( "\n", self::default_response_headers() ),
			);
		}

		/**
		 * Registers settings sections and fields for the Headers tab.
		 *
		 * @return void
		 */
		public static function register_headers_settings() {
			$key = self::$settings_key;

			register_setting( $key, $key, array( __CLASS__, 'sanitize_settings' ) );

			$section  = 'headers';
			$defaults = self::get_default_settings();

			add_settings_section( $section, '', array( __CLASS__, 'section_header' ), $key );

			add_settings_field(
				'redacted-request-headers',
				__( 'Redacted Request Headers', 'wp-rest-api-log' ),
				array( __CLASS__, 'settings_textarea' ),
				$key,
				$section,
				array(
					'key'     => $key,
					'name'    => 'redacted-request-headers',
					'default' => $defaults['redacted-request-headers'],
				)
			);

			add_settings_field(
				'redacted-response-headers',
				__( 'Redacted Response Headers', 'wp-rest-api-log' ),
				array( __CLASS__, 'settings_textarea' ),
				$key,
				$section,
				array(
					'key'     => $key,
					'name'    => 'redacted-response-headers',
					'default' => $defaults['redacted-response-headers'],
				)
			);
		}

		/**
		 * Outputs the introduction shown above the Headers fields.
		 *
		 * @return void
		 */
		public static function section_header() {
			?>
			<p>
				<?php esc_html_e( 'Request and response headers can contain sensitive information such as cookies, API keys and bearer tokens. The values of the headers listed below are redacted before a log entry is saved.', 'wp-rest-api-log' ); ?>
			</p>
			<p>
				<?php
				esc_html_e( 'One header name per line. Matching is case-insensitive and treats dashes and underscores as the same character.', 'wp-rest-api-log' );
				echo ' ';
				printf(
					/* translators: %s: the text stored in place of the header value. */
					esc_html__( 'The header name is still logged, but its value is replaced with %s.', 'wp-rest-api-log' ),
					'<code>' . esc_html( WP_REST_API_Log_Headers::REDACTED ) . '</code>'
				);
				?>
			</p>
			<?php
		}

		/**
		 * Sanitizes the header settings.
		 *
		 * @param  array $settings List of settings.
		 * @return array
		 */
		public static function sanitize_settings( $settings ) {

			$header_list_fields = array(
				'redacted-request-headers',
				'redacted-response-headers',
			);

			// Sanitize one header per line, since sanitize_text_field() would
			// collapse the newlines if it were run against the whole value.
			foreach ( $header_list_fields as $field ) {
				if ( ! isset( $settings[ $field ] ) ) {
					continue;
				}

				$headers = explode( "\n", $settings[ $field ] );
				$headers = array_map( 'sanitize_text_field', $headers );
				$headers = array_filter( array_map( 'trim', $headers ) );

				$settings[ $field ] = implode( "\n", $headers );
			}

			return $settings;
		}
	}

}
