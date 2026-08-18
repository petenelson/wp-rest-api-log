<?php
/**
 * Registers the plugin's own REST API routes.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Controller' ) ) {

	/**
	 * Exposes log entries, routes and downloads over the REST API.
	 */
	class WP_REST_API_Log_Controller {


		/**
		 * Hooks the plugin's REST routes into the REST API.
		 *
		 * @return void
		 */
		public static function plugins_loaded() {
			add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
			add_action( 'rest_api_init', array( __CLASS__, 'register_download_routes' ) );
		}


		// phpcs:disable Squiz.PHP.CommentedOutCode.Found -- Commented-out argument definitions are kept as placeholders.
		/**
		 * Registers the plugin's read and delete REST routes.
		 *
		 * @return void
		 */
		public static function register_rest_routes() {

			register_rest_route(
				WP_REST_API_Log_Common::PLUGIN_NAME,
				'/entries',
				array(
					'methods'             => array( WP_REST_Server::READABLE ),
					'callback'            => array( __CLASS__, 'get_items' ),
					'permission_callback' => array( __CLASS__, 'get_permissions_check' ),
					'args'                => array(
						'from'             => array(
							'default' => '',
						),
						'to'               => array(
							'default' => current_time( 'mysql' ),
						),
						// 'fields'                => array(
						// 'default'              => 'basic',
						// ),
						'route'            => array(
							'default' => '',
						),
						'route-match-type' => array(
							'sanitize_callback' => 'sanitize_key',
							'default'           => 'exact',
						),
						// 'id'                    => array(
						// 'sanitize_callback'    => 'absint',
						// 'default'              => 0,
						// ),
						'after-id'         => array(
							'sanitize_callback' => 'absint',
							'default'           => 0,
						),
						'before-id'        => array(
							'sanitize_callback' => 'absint',
							'default'           => 0,
						),
						'page'             => array(
							'sanitize_callback' => 'absint',
							'default'           => 1,
						),
						'records-per-page' => array(
							'sanitize_callback' => 'absint',
							'default'           => 20,
						),
						// 'response_type'         => array(
						// 'default'           => 'json',
						// ),
						// 'params'                => array(
						// ),
					),
				)
			);

			register_rest_route(
				WP_REST_API_Log_Common::PLUGIN_NAME,
				'/entry/(?P<id>[\d]+)',
				array(
					'methods'             => array( WP_REST_Server::READABLE ),
					'callback'            => array( __CLASS__, 'get_item' ),
					'permission_callback' => array( __CLASS__, 'get_permissions_check' ),
					'args'                => array(
						'id' => array(
							'sanitize_callback' => 'absint',
							'validate_callback' => array( __CLASS__, 'validate_entry_id' ),
							'default'           => 0,
						),
					),
				)
			);

			register_rest_route(
				WP_REST_API_Log_Common::PLUGIN_NAME,
				'/entry',
				array(
					'methods'             => array( WP_REST_Server::DELETABLE ),
					'callback'            => array( __CLASS__, 'delete_items' ),
					'permission_callback' => array( __CLASS__, 'delete_items_permissions_check' ),
					'args'                => array( // TODO: refactor delete, this won't work with $_REQUESTs.
						'older-than-seconds' => array(
							'sanitize_callback' => 'absint',  // TODO: add validate callback.
							'default'           => DAY_IN_SECONDS * 30,
						),
					),
				)
			);

			// Route to delete all log entries.
			register_rest_route(
				WP_REST_API_Log_Common::PLUGIN_NAME,
				'/entries',
				array(
					'methods'             => array( WP_REST_Server::DELETABLE ),
					'callback'            => array( __CLASS__, 'purge_log' ),
					'permission_callback' => array( __CLASS__, 'delete_items_permissions_check' ),
				)
			);

			// Route to delete a batch of log entries.
			register_rest_route(
				WP_REST_API_Log_Common::PLUGIN_NAME,
				'/batch-purge-all',
				array(
					'methods'             => array( WP_REST_Server::DELETABLE ),
					'callback'            => array( __CLASS__, 'batch_purge_log' ),
					'permission_callback' => array( __CLASS__, 'delete_items_permissions_check' ),
				)
			);

			register_rest_route(
				WP_REST_API_Log_Common::PLUGIN_NAME,
				'/routes',
				array(
					'methods'             => array( WP_REST_Server::READABLE ),
					'callback'            => array( __CLASS__, 'get_routes' ),
					'permission_callback' => array( __CLASS__, 'get_permissions_check' ),
				)
			);
		}

		/**
		 * Returns a list of routes available for download.
		 *
		 * @return array
		 */
		public static function get_download_routes() {

			$routes = array(
				'request'  => array(
					'body_params',
					'query_params',
					'body',
					'headers',
				),
				'response' => array(
					'body',
					'headers',
				),
			);

			return apply_filters( 'wp-rest-api-log-download-routes', $routes );
		}

		/**
		 * Gets REST API endpoint URLs to download entry properties.
		 *
		 * @param object $entry REST API Log Entry.
		 * @return array
		 */
		public static function get_download_urls( $entry ) {

			$download_routes = self::get_download_routes();
			$download_urls   = array();

			foreach ( $download_routes as $rr => $properties ) {
				$download_urls[ $rr ] = array();

				foreach ( $properties as $property ) {
					$url = rest_url( "/wp-rest-api-log/entry/{$entry->ID}/{$rr}/{$property}/download" );
					if ( is_ssl() ) {
						$url = set_url_scheme( $url, 'https' );
					}

					// Create a hash for this request.
					$hash = wp_hash( wp_nonce_tick() . "wp-rest-api-log-download-{$rr}-{$property}" );

					// Add the hash to the URL.
					$url = add_query_arg( 'hash', rawurlencode( $hash ), $url );

					// Add a nonce to the URL for security.
					$nonce = wp_create_nonce( 'wp_rest' );
					$url   = add_query_arg( '_wpnonce', rawurlencode( $nonce ), $url );

					$download_urls[ $rr ][ $property ] = $url;
				}
			}

			return apply_filters( 'wp-rest-api-log-download-urls', $download_urls, $entry );
		}

		/**
		 * Registers the routes to download portions of an entry.
		 *
		 * @return void
		 */
		public static function register_download_routes() {

			foreach ( self::get_download_routes() as $request_response => $properties ) {
				foreach ( $properties as $property ) {

					register_rest_route(
						WP_REST_API_Log_Common::PLUGIN_NAME,
						"/entry/(?P<id>[\d]+)/(?P<rr>{$request_response})/(?P<property>{$property})/download",
						array(
							'methods'             => array( WP_REST_Server::READABLE ),
							'callback'            => array( __CLASS__, 'download_json' ),
							'permission_callback' => array( __CLASS__, 'download_permissions_check' ),
							'args'                => array(
								'rr'       => array(
									'required'          => true,
									'sanitize_callback' => 'sanitize_text_field',
								),
								'property' => array(
									'required'          => true,
									'sanitize_callback' => 'sanitize_text_field',
								),
								'hash'     => array(
									'required'          => true,
									'sanitize_callback' => 'sanitize_text_field',
								),
								'id'       => array(
									'required'          => true,
									'sanitize_callback' => 'absint',
									'validate_callback' => array( __CLASS__, 'validate_entry_id' ),
								),
							),
						)
					);
				}
			}
		}

		// phpcs:enable Squiz.PHP.CommentedOutCode.Found

		/**
		 * Returns a page of log entries.
		 *
		 * @param  WP_REST_Request $request The REST request.
		 * @return WP_REST_Response
		 */
		public static function get_items( WP_REST_Request $request ) {

			$args = array(
				'id'               => $request['id'],
				'page'             => $request['page'],
				'records_per_page' => $request['records-per-page'],
				'after_id'         => $request['after-id'],
				'before_id'        => $request['before-id'],
				'from'             => $request['from'],
				'to'               => $request['to'],
				'method'           => $request['method'],
				'status'           => $request['status'],
				'route'            => $request['route'],
				'route_match_type' => $request['route-match-type'],
				'params'           => $request['params'],
			);

			$db    = new WP_REST_API_Log_DB();
			$posts = $db->search( $args );

			return rest_ensure_response( WP_REST_API_Log_Entry::from_posts( $posts ) );
		}

		/**
		 * Returns a single log entry.
		 *
		 * @param  WP_REST_Request $request The REST request.
		 * @return WP_REST_Response
		 */
		public static function get_item( WP_REST_Request $request ) {
			return rest_ensure_response( self::get_entry( $request['id'] ) );
		}

		/**
		 * Builds the error returned for an unknown log entry ID.
		 *
		 * @param  int $id The requested log entry ID.
		 * @return WP_Error
		 */
		public static function invalid_entry_id_error( $id ) {
			/* translators: %d: the requested log entry ID. */
			return new WP_Error( 'invalid_entry_id', sprintf( __( 'Invalid REST API Log ID %d.', 'wp-rest-api-log' ), $id ), array( 'status' => 404 ) );
		}

		/**
		 * Determines whether the current user may read log entries.
		 *
		 * @return bool Filterable via "wp-rest-api-log-can-view-entries".
		 */
		public static function get_permissions_check() {
			$post_type = get_post_type_object( WP_REST_API_Log_DB::POST_TYPE );
			if ( $post_type instanceof WP_Post_Type ) {
				return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-can-view-entries', current_user_can( $post_type->cap->read_post ) );
			} else {
				return false;
			}
		}

		/**
		 * Determines whether the current user may download a log entry field.
		 *
		 * @param  WP_REST_Request $request The REST request.
		 * @return bool Filterable via "wp-rest-api-log-can-download-entry".
		 */
		public static function download_permissions_check( WP_REST_Request $request ) {

			$rr       = ! empty( $request['rr'] ) ? sanitize_text_field( $request['rr'] ) : '';
			$property = ! empty( $request['property'] ) ? sanitize_text_field( $request['property'] ) : '';
			$hash     = ! empty( $request['hash'] ) ? sanitize_text_field( $request['hash'] ) : '';
			$allowed  = false;

			$can_read_entries = self::get_permissions_check();

			if ( ! empty( $rr ) && ! empty( $property ) && ! empty( $hash ) && $can_read_entries ) {
				$allowed = wp_hash( wp_nonce_tick() . "wp-rest-api-log-download-{$rr}-{$property}" ) === $hash;
			} else {
				$allowed = false;
			}

			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-can-download-entry', $allowed, $rr, $property );
		}

		/**
		 * Determines whether the current user may delete log entries.
		 *
		 * @return bool Filterable via "wp-rest-api-log-can-delete-entries".
		 */
		public static function delete_items_permissions_check() {
			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-can-delete-entries', current_user_can( 'delete_' . WP_REST_API_Log_DB::POST_TYPE ) );
		}

		/**
		 * Validates that a requested log entry ID exists.
		 *
		 * @param  int $id Log entry post ID.
		 * @return bool|WP_Error True when valid, WP_Error otherwise.
		 */
		public static function validate_entry_id( $id ) {
			if ( $id < 1 ) {
				return invalid_entry_id_error( $id );
			} else {

				// Verify that the entry exists.
				$entry = self::get_entry( $id );

				return ! empty( $entry ) ? true : self::invalid_entry_id_error( $id );
			}
		}

		/**
		 * Loads a log entry by ID.
		 *
		 * @param  int $id Log entry post ID.
		 * @return WP_REST_API_Log_Entry|false False when the ID is not a log entry.
		 */
		public static function get_entry( $id ) {

			$post = get_post( $id );

			if ( ! empty( $post ) && WP_REST_API_Log_DB::POST_TYPE === $post->post_type ) {
				return new WP_REST_API_Log_Entry( $post );
			} else {
				return false;
			}
		}

		/**
		 * Returns the distinct routes that have been logged.
		 *
		 * @param  WP_REST_Request $request The REST request.
		 * @return WP_REST_Response
		 */
		public static function get_routes( WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Signature is fixed by the REST route callback.

			global $wpdb;

			$query = $wpdb->prepare(
				"select distinct post_title from {$wpdb->posts} where post_type = %s and post_title is not null order by post_type",
				WP_REST_API_Log_DB::POST_TYPE
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $query is built with $wpdb->prepare() directly above.
			$routes = $wpdb->get_col( $query );

			return rest_ensure_response( $routes );
		}


		/**
		 * Deletes log entries older than the requested age.
		 *
		 * @param  WP_REST_Request $request The REST request.
		 * @return WP_REST_Response
		 */
		public static function delete_items( WP_REST_Request $request ) {
			// TODO: refactor.
			$args = array(
				'older_than_seconds' => $request['older-than-seconds'],
			);

			$db = new WP_REST_API_Log_DB();
			return rest_ensure_response( new WP_REST_API_Log_Delete_Response( $db->delete( $args ) ) );
		}

		/**
		 * Handler to purge all log entries.
		 *
		 * @return WP_REST_Response
		 */
		public static function purge_log() {
			WP_REST_API_Log_DB::purge_all_log_entries();
			return rest_ensure_response( array( 'success' => true ) );
		}

		/**
		 * Handler to purge a batch of log entries.
		 *
		 * @return WP_REST_Response
		 */
		public static function batch_purge_log() {

			// Don't log this request.
			add_filter( WP_REST_API_Log_Common::PLUGIN_NAME . '-bypass-insert', '__return_true' );

			$query_args = array(
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'post_type'              => WP_REST_API_Log_DB::POST_TYPE,
				'fields'                 => 'ids',
				'posts_per_page'         => 50,
				'orderby'                => 'date',
				'order'                  => 'ASC',
			);

			$query_args = apply_filters( 'wp-rest-api-log-batch-purge-query-args', $query_args );

			$query = new WP_Query( $query_args );

			// Turn off term counting.
			wp_defer_term_counting( true );

			// Delete this batch of log entries.
			foreach ( $query->posts as $post_id ) {
				wp_delete_post( $post_id, true );
			}

			wp_defer_term_counting( false );

			// Run this again to get the total count of items left.
			$query_args['posts_per_page'] = 1;
			$query                        = new WP_Query( $query_args );

			$response = array(
				'entries_left'           => $query->found_posts,
				// translators: %s: formatted number of log entries still to be migrated.
				'entries_left_formatted' => sprintf( __( '%s entries remaining...' ), number_format( $query->found_posts ) ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- Text domain omitted since 1.0; adding it would change which catalogue is used.
			);

			return rest_ensure_response( $response );
		}

		/**
		 * Handler for setting up the filter to allow downloading of files.
		 *
		 * @param  WP_REST_Request $request REST request.
		 * @return WP_REST_Response
		 */
		public static function download_json( WP_REST_Request $request ) {

			$entry = self::get_entry( $request['id'] );

			add_filter( 'rest_pre_serve_request', array( __CLASS__, 'download_json_pre_serve_request' ), 10, 4 );

			return rest_ensure_response(
				array(
					'wp-rest-api-log-download' => true,
					'entry'                    => $entry,
				)
			);
		}

		/**
		 * Filter hook to download entry properties as a file.
		 *
		 * @param bool                      $served   Whether the request has already been served.
		 * @param WP_HTTP_ResponseInterface $response Result to send to the client. Usually a WP_REST_Response.
		 * @param WP_REST_Request           $request  Request used to generate the response.
		 * @param WP_REST_Server            $server   Server instance.
		 * @return bool
		 */
		public static function download_json_pre_serve_request( $served, $response, $request, $server ) {

			$data = $server->response_to_data( $response, false );

			// Is this a download request?
			if ( is_array( $data ) && ! empty( $data['wp-rest-api-log-download'] ) && ! empty( $data['entry'] ) ) {

				$entry = $data['entry'];

				// Request or response.
				$rr = $request['rr'];

				// Property.
				$property = $request['property'];

				// Get the property value.
				$value = $entry->{$rr}->{$property};

				// Default the file extension to json.
				$ext = 'json';

				// Determine what we're going to send to the browser.
				if ( is_object( $value ) || is_array( $value ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Raw json_encode() retained so the downloaded file is byte-for-byte what was logged.
					$value = json_encode( $value, JSON_PRETTY_PRINT );
				} else {

					// See if this is a JSON field.
					$obj = json_decode( $value );
					if ( null === $obj ) {

						// Might still be a JSON string though.
						$check_json = trim( $value );
						$is_json    = false;
						if ( ! empty( $check_json ) ) {
							$is_json = '{' === substr( $check_json, 0, 1 )
								&& '}' === substr( $check_json, strlen( $check_json ) - 1 );
						}

						if ( ! $is_json ) {
							header( 'Content-Type: text/plain' );
							$ext = 'txt';
						}
					}
				}

				// Create a filename for the download.
				$filename = sanitize_file_name( "{$entry->route}-{$rr}-{$property}-{$entry->ID}.{$ext}" );

				// Allow filename filtering.
				$filename = apply_filters( 'wp-rest-api-log-download-filename', $filename, $entry );

				// Set the content disposition for a download.
				header( 'Content-Disposition: attachment; filename=' . $filename );

				// Output the field value.
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a file download; escaping would corrupt the downloaded body.
				echo $value;

				// Tell the REST API that we handled this ourselves.
				$served = true;
			}

			return $served;
		}
	}

}
