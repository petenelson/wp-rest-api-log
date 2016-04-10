<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_API_Request_Response_Base' ) ) {

	/**
	 * Base class for a request or response with common fields
	 */
	class WP_REST_API_Log_API_Request_Response_Base {

		public $body;
		public $headers;

		protected $_type;

		private $_post;
		private $_meta;

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

		protected function set_type( $type ) {
			$this->_type = $type;
		}


		private function load() {

			$this->headers   = $this->get_post_meta_array( 'headers' );

			if ( 'request' === $this->_type ) {
				$this->body = get_post_meta( $this->_post->ID, '_request_body', true );
				if ( false === $this->body) {
					$this->body = '';
				}
			} else {
				$this->body = $this->_post->post_content;
			}

		}


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

			// loop through the post meta, find the keys for the array
			foreach ( $this->_meta as $key => $value ) {

				// ex: _request_headers|Expires
				// ex: _request_headers|Content-type
				$look_for = "{$this->_type}_{$type}|";
				$pos = stripos( $key, $look_for );

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


	}

}
