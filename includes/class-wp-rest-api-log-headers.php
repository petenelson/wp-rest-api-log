<?php
/**
 * Redacts sensitive header values before they are logged.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Headers' ) ) {

	/**
	 * Replaces the values of sensitive request and response headers, such as
	 * cookies, API keys and bearer tokens, with a placeholder.
	 */
	class WP_REST_API_Log_Headers {

		/**
		 * Text stored in place of a sensitive header value.
		 *
		 * Square brackets are used instead of angle brackets because the admin
		 * entry viewer escapes header values more than once, which would leave
		 * angle brackets displayed as HTML entities.
		 *
		 * @var string
		 */
		const REDACTED = '[REDACTED]';

		/**
		 * Single instance of this class.
		 *
		 * @var WP_REST_API_Log_Headers
		 */
		private static $instance;

		/**
		 * Gets the single instance of this class.
		 *
		 * @return WP_REST_API_Log_Headers
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Normalizes a header name so names can be compared regardless of the
		 * casing and separator used.
		 *
		 * The REST API canonicalizes request header names to lowercase with
		 * underscores (authorization, x_wp_nonce), while response header names
		 * come from headers_list() and keep their original form (Set-Cookie).
		 *
		 * @param  string $name Header name.
		 * @return string
		 */
		public function normalize_header_name( $name ) {
			return strtolower( str_replace( '-', '_', trim( $name ) ) );
		}

		/**
		 * Gets the normalized names of the headers whose values are redacted.
		 *
		 * @param  string $type                Either "request" or "response".
		 * @param  mixed  $request_or_response Optional. The request or response
		 *                                     being logged, for context.
		 * @return array List of normalized header names.
		 */
		public function get_redacted_header_names( $type, $request_or_response = null ) {

			$setting  = 'response' === $type ? 'redacted-response-headers' : 'redacted-request-headers';
			$defaults = WP_REST_API_Log_Settings_Headers::get_default_settings();

			// The settings are only created when the plugin is activated, so
			// fall back to the defaults until this tab has been saved.
			$headers = apply_filters( 'wp-rest-api-log-setting-get', 'headers', $setting, $defaults[ $setting ] );

			$headers = array_map( array( $this, 'normalize_header_name' ), explode( "\n", $headers ) );
			$headers = array_values( array_unique( array_filter( $headers ) ) );

			/**
			 * Filters the names of the headers whose values are redacted.
			 *
			 * Names are normalized to lowercase with underscores.
			 *
			 * @param array  $headers             List of normalized header names.
			 * @param string $type                Either "request" or "response".
			 * @param mixed  $request_or_response The request or response being logged.
			 */
			return apply_filters( 'wp-rest-api-log-redacted-header-names', $headers, $type, $request_or_response );
		}

		/**
		 * Replaces the values of sensitive headers with a placeholder.
		 *
		 * @param  array  $headers             Header values keyed by header name.
		 * @param  string $type                Either "request" or "response".
		 * @param  mixed  $request_or_response Optional. The request or response
		 *                                     being logged, for context.
		 * @return array Headers with sensitive values replaced.
		 */
		public function redact_headers( $headers, $type, $request_or_response = null ) {

			if ( ! is_array( $headers ) || empty( $headers ) ) {
				return $headers;
			}

			$redacted_names = $this->get_redacted_header_names( $type, $request_or_response );

			if ( empty( $redacted_names ) ) {
				return $headers;
			}

			foreach ( $headers as $name => $value ) {

				if ( ! in_array( $this->normalize_header_name( $name ), $redacted_names, true ) ) {
					continue;
				}

				/**
				 * Filters the text stored in place of a sensitive header value.
				 *
				 * @param string $redacted            Replacement text.
				 * @param string $name                Header name.
				 * @param string $type                Either "request" or "response".
				 * @param mixed  $request_or_response The request or response being logged.
				 */
				$headers[ $name ] = apply_filters( 'wp-rest-api-log-redacted-header-value', self::REDACTED, $name, $type, $request_or_response );
			}

			return $headers;
		}
	}

}
