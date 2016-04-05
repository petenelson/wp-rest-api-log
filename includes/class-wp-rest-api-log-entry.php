<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log' ) ) {

	class WP_REST_API_Log_Entry {

		static public function from_posts( array $posts ) {
			$entries = array();
			foreach ( $posts as $post ) {
				$entries[] = new WP_REST_API_Log_Entry( $post );
			}
			return $entries;
		}


		/**
		 * ID of the log entry (post ID)
		 * @var int
		 */
		public $ID;

		/**
		 * Time of the request
		 * @var string
		 */
		public $time;

		/**
		 * Time of the request
		 * @var string
		 */
		public $time_gmt;

		/**
		 * IP address of the request (from postmeta)
		 * @var string
		 */
		public $ip_address;

		/**
		 * HTTP method of the request (from wp-rest-api-log-method taxonomy)
		 * @var string
		 */
		public $method;

		/**
		 * HTTP status of the request (from wp-rest-api-log-status taxonomy)
		 * @var int
		 */
		public $status;

		/**
		 * Route (post_title)
		 * @var string
		 */
		public $route;

		/**
		 * Request data
		 * @var object
		 */
		public $request;

		/**
		 * Response data
		 * @var object
		 */
		public $response;

		/**
		 * How long the request took
		 * @var int
		 */
		public $milliseconds;

		public $_links = array( 'self' => array( 'href' => '' ) );

		private $_post;


		public function __construct( $post = null ) {

			if ( is_int( $post ) ) {
				$post = get_post( $post );
			}

			if ( is_object( $post ) ) {
				$this->_post = $post;
				$this->load();
			}
		}


		private function load() {

			$this->request   = new WP_REST_API_Log_API_Request( $this->_post );
			$this->response  = new WP_REST_API_Log_API_Response( $this->_post );

			$this->load_post_data();
			$this->load_post_meta();
			$this->load_taxonomies();

		}

		private function load_post_data() {
			$this->ID        = $this->_post->ID;
			$this->route     = $this->_post->post_title;
			$this->time      = $this->_post->post_date;
			$this->time_gmt  = $this->_post->post_date_gmt;

			if ( function_exists( 'rest_url' ) ) {
				$this->_links['self']['href'] = rest_url( WP_REST_API_Log_Common::PLUGIN_NAME . '/entry/' . $this->ID );
			}
		}

		private function load_post_meta() {

			$post_id = $this->_post->ID;

			$this->ip_address      = get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_IP_ADDRESS, true );
			$this->milliseconds    = absint( get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_MILLISECONDS, true ) );

		}

		private function load_taxonomies() {
			$post_id = $this->_post->ID;

			$this->method  = $this->get_first_term_name( $post_id, WP_REST_API_Log_DB::TAXONOMY_METHOD );
			$this->status  = $this->get_first_term_name( $post_id, WP_REST_API_Log_DB::TAXONOMY_STATUS );
			$this->source  = $this->get_first_term_name( $post_id, WP_REST_API_Log_DB::TAXONOMY_SOURCE );

		}

		public function get_first_term_name( $post_id, $taxonomy ) {
			$terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
			return ! empty( $terms ) ? $terms[0] : '';
		}


	}

}

