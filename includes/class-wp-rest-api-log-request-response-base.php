<?php
/**
 * Shared base class for the request and response halves of a log entry.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_API_Request_Response_Base' ) ) {

	/**
	 * Base class for a request or response with common fields
	 */
	class WP_REST_API_Log_API_Request_Response_Base {

		/**
		 * Raw body content.
		 *
		 * @var string
		 */
		public $body;

		/**
		 * HTTP headers, keyed by header name.
		 *
		 * @var array
		 */
		public $headers;

		/**
		 * Which half of the entry this object represents: "request" or "response".
		 *
		 * @var string
		 */
		protected $_type; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Retained for backwards compatibility.

		/**
		 * The underlying log entry post.
		 *
		 * @var WP_Post
		 */
		private $_post; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Retained for backwards compatibility.

		/**
		 * Cached post meta for the log entry post.
		 *
		 * @var array
		 */
		private $_meta; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Retained for backwards compatibility.

		/**
		 * Loads one half of a log entry.
		 *
		 * @param string           $type Either "request" or "response".
		 * @param WP_Post|int|null $post Log entry post object or ID.
		 */
		public function __construct( $type, $post = null ) {

			$this->_type = $type;

			if ( is_int( $post ) ) {
				$post = get_post( $post );
			}

			if ( is_object( $post ) ) {
				$this->_post = $post;
				$this->load();
			}
		}

		/**
		 * Sets which half of the entry this object represents.
		 *
		 * @param  string $type Either "request" or "response".
		 * @return void
		 */
		protected function set_type( $type ) {
			$this->_type = $type;
		}


		/**
		 * Loads the headers and body for this half of the entry.
		 *
		 * @return void
		 */
		private function load() {

			$this->headers = $this->get_post_meta_array( 'headers' );

			if ( 'request' === $this->_type ) {
				$this->body = get_post_meta( $this->_post->ID, '_request_body', true );
				if ( false === $this->body ) {
					$this->body = '';
				}
			} else {
				$this->body = $this->_post->post_content;
			}
		}


		/**
		 * Collects a group of pipe-delimited post meta values into an array.
		 *
		 * @param  string $type Meta group name, for example "headers".
		 * @return array Meta values keyed by their name.
		 */
		protected function get_post_meta_array( $type ) {

			$meta = array();

			if ( ! is_object( $this->_post ) ) {
				return $meta;
			}

			if ( empty( $this->_meta ) ) {
				$this->_meta = get_post_meta( $this->_post->ID );
			}

			if ( empty( $this->_meta ) ) {
				return $meta;
			}

			// Loop through the post meta, find the keys for the array.
			foreach ( $this->_meta as $key => $value ) {

				// Example: _request_headers|Expires.
				// Example: _request_headers|Content-type.
				$look_for = "{$this->_type}_{$type}|";
				$pos      = stripos( $key, $look_for );

				if ( 0 === $pos ) {

					$meta_name = substr( $key, strlen( $look_for ) );

					if ( is_array( $value ) && 1 === count( $value ) ) {
						$meta[ $meta_name ] = maybe_unserialize( $value[0] );
					} else {
						$meta[ $meta_name ] = maybe_unserialize( $value );
					}
				}
			}

			return $meta;
		}

		/**
		 * Runs esc_html() on various fields for display in the admin.
		 *
		 * @param  object $entry REST API Log Entry.
		 * @return object
		 */
		public static function esc_html_fields( $entry ) {

			// Get the list of request fiels.
			$request_fields = array(
				'query_params',
				'headers',
			);

			$request_fields = apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-esc-html-request-fields', $request_fields );

			// Get the list of response fiels.
			$response_fields = array(
				'headers',
			);

			$response_fields = apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-esc-html-response-fields', $response_fields );

			// Run esc_html on the request fields.
			foreach ( $request_fields as $field ) {
				if ( is_array( $entry->request->$field ) ) {
					array_walk_recursive(
						$entry->request->$field,
						function ( &$v ) {
							$v = esc_html( $v );
						}
					);
				} else {
					$entry->request->$field = esc_html( $entry->request->$field );
				}
			}

			// Run esc_html on the response fields.
			foreach ( $response_fields  as $field ) {
				if ( is_array( $entry->response->$field ) ) {
					array_walk_recursive(
						$entry->response->$field,
						function ( &$v ) {
							$v = esc_html( $v );
						}
					);
				} else {
					$entry->response->$field = esc_html( $entry->response->$field );
				}
			}

			return $entry;
		}
	}

}
