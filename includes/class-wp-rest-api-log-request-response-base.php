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

			$this->headers   = $this->get_post_meta_array( 'header' );

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

				// ex: _request_header_key_67b3dba8bc6778101892eb77249db32e
				$look_for = "_{$this->_type}_{$type}_key_";

				if ( 0 === stripos( $key, $look_for ) ) {

					$hash = substr( $key , strlen( $look_for ) );
					if ( is_array( $value ) && 1 === count( $value ) ) {
						$meta_name = $value[0];
					} else {
						$meta_name = $value;
					}

					// set the meta field we need to look for in the next loop
					$meta[ $meta_name ] = $look_for = "_{$this->_type}_{$type}_value_" . $hash;
				}

			}


			// loop through the look_fors that were set
			foreach ( $meta as $name => $look_for_key ) {
				$meta[ $name ] = get_post_meta( $this->_post->ID, $look_for_key, true );
			}

			return $meta;

		}


	}

}
