<?php
/**
 * Models a single REST API log entry.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log' ) ) {

	/**
	 * A single log entry, hydrated from its underlying post, post meta and
	 * taxonomy terms.
	 */
	class WP_REST_API_Log_Entry {

		/**
		 * Builds a list of log entries from a list of posts.
		 *
		 * @param  array $posts Log entry post objects.
		 * @return array List of WP_REST_API_Log_Entry objects.
		 */
		public static function from_posts( array $posts ) {
			$entries = array();
			foreach ( $posts as $post ) {
				$entries[] = new WP_REST_API_Log_Entry( $post );
			}
			return $entries;
		}


		/**
		 * ID of the log entry (post ID)
		 *
		 * @var int
		 */
		public $ID;

		/**
		 * Time of the request
		 *
		 * @var string
		 */
		public $time;

		/**
		 * Time of the request
		 *
		 * @var string
		 */
		public $time_gmt;

		/**
		 * IP address of the request (from postmeta)
		 *
		 * @var string
		 */
		public $ip_address;

		/**
		 * User of the request (from postmeta)
		 *
		 * @var string
		 */
		public $user;

		/**
		 * HTTP_X_FORWARDED_FOR address of the request (from postmeta)
		 *
		 * @var string
		 */
		public $http_x_forwarded_for;

		/**
		 * HTTP method of the request (from wp-rest-api-log-method taxonomy)
		 *
		 * @var string
		 */
		public $method;

		/**
		 * HTTP status of the request (from wp-rest-api-log-status taxonomy)
		 *
		 * @var string
		 */
		public $status;

		/**
		 * Source of the request (from wp-rest-api-log-source taxonomy)
		 *
		 * @var string
		 */
		public $source;

		/**
		 * Route (post_title)
		 *
		 * @var string
		 */
		public $route;

		/**
		 * Request data
		 *
		 * @var object
		 */
		public $request;

		/**
		 * Response data
		 *
		 * @var object
		 */
		public $response;

		/**
		 * How long the request took
		 *
		 * @var int
		 */
		public $milliseconds;

		/**
		 * REST API links for this entry.
		 *
		 * The leading underscore matches the REST API's "_links" convention and
		 * is part of the response shape, so the name is kept as-is.
		 *
		 * @var array
		 */
		public $_links = array( 'self' => array( 'href' => '' ) ); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Name matches the REST API "_links" response key.

		/**
		 * The underlying log entry post.
		 *
		 * @var WP_Post
		 */
		private $_post; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- Retained for backwards compatibility.


		/**
		 * Loads a log entry from a post.
		 *
		 * @param WP_Post|int|null $post Log entry post object or ID.
		 */
		public function __construct( $post = null ) {

			if ( is_int( $post ) ) {
				$post = get_post( $post );
			}

			if ( is_object( $post ) ) {
				$this->_post = $post;
				$this->load();
			}
		}


		/**
		 * Hydrates the entry from its post, post meta and taxonomy terms.
		 *
		 * @return void
		 */
		private function load() {

			$this->request  = new WP_REST_API_Log_API_Request( $this->_post );
			$this->response = new WP_REST_API_Log_API_Response( $this->_post );

			$this->load_post_data();
			$this->load_post_meta();
			$this->load_taxonomies();
		}

		/**
		 * Populates the fields stored on the post itself.
		 *
		 * @return void
		 */
		private function load_post_data() {
			$this->ID       = $this->_post->ID;
			$this->route    = $this->_post->post_title;
			$this->time     = $this->_post->post_date;
			$this->time_gmt = $this->_post->post_date_gmt;

			if ( function_exists( 'rest_url' ) ) {
				$this->_links['self']['href'] = rest_url( WP_REST_API_Log_Common::PLUGIN_NAME . '/entry/' . $this->ID );
			}
		}

		/**
		 * Populates the fields stored in post meta.
		 *
		 * @return void
		 */
		private function load_post_meta() {

			$post_id = $this->_post->ID;

			$this->ip_address           = get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_IP_ADDRESS, true );
			$this->user                 = get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_REQUEST_USER, true );
			$this->http_x_forwarded_for = get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_HTTP_X_FORWARDED_FOR, true );
			$this->milliseconds         = absint( get_post_meta( $post_id, WP_REST_API_Log_DB::POST_META_MILLISECONDS, true ) );
		}

		/**
		 * Populates the method, status and source taxonomy fields.
		 *
		 * @return void
		 */
		private function load_taxonomies() {
			$post_id = $this->_post->ID;

			$this->method = $this->get_first_term_name( $post_id, WP_REST_API_Log_DB::TAXONOMY_METHOD );
			$this->status = $this->get_first_term_name( $post_id, WP_REST_API_Log_DB::TAXONOMY_STATUS );
			$this->source = $this->get_first_term_name( $post_id, WP_REST_API_Log_DB::TAXONOMY_SOURCE );
		}

		/**
		 * Gets the first term name for the supplied post and taxonomy.
		 *
		 * @param  int|WP_Post $post     Post ID or object.
		 * @param  string      $taxonomy Taxonomy slug.
		 * @return string
		 */
		public function get_first_term_name( $post, $taxonomy ) {

			// Uses get_the_terms() since wp_get_object_terms() does not
			// do any caching.
			$terms = get_the_terms( $post, $taxonomy );

			return ! empty( $terms ) && is_array( $terms ) ? $terms[0]->name : '';
		}
	}

}
