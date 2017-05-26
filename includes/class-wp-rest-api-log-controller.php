<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Controller' ) ) {

	class WP_REST_API_Log_Controller {


		static function plugins_loaded() {
			add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
			add_action( 'rest_api_init', array( __CLASS__, 'register_download_routes' ) );
		}


		static public function register_rest_routes() {

			register_rest_route( WP_REST_API_Log_Common::PLUGIN_NAME, '/entries', array(
				'methods'             => array( WP_REST_Server::READABLE ),
				'callback'            => array( __CLASS__, 'get_items' ),
				'permission_callback' => array( __CLASS__, 'get_permissions_check' ),
				'args'                => array(
					'from'            => array(
						'default'           => '',
						),
					'to'                    => array(
						'default'              => current_time( 'mysql' ),
						),
					// 'fields'                => array(
					// 	'default'              => 'basic',
					// 	),
					'route'                 => array(
						'default'              => '',
						),
					'route-match-type'      => array(
						'sanitize_callback'    => 'sanitize_key',
						'default'              => 'exact',
						),
					// 'id'                    => array(
					// 	'sanitize_callback'    => 'absint',
					// 	'default'              => 0,
					// 	),
					'after-id'              => array(
						'sanitize_callback'    => 'absint',
						'default'              => 0,
						),
					'before-id'             => array(
						'sanitize_callback'    => 'absint',
						'default'              => 0,
						),
					'page'                  => array(
						'sanitize_callback'    => 'absint',
						'default'              => 1,
						),
					'records-per-page'      => array(
						'sanitize_callback'    => 'absint',
						'default'              => 20,
						),
					// 'response_type'         => array(
					// 	'default'           => 'json',
					// 	),
					// 'params'                => array(
					// 	),
				),
			) );


			register_rest_route( WP_REST_API_Log_Common::PLUGIN_NAME, '/entry/(?P<id>[\d]+)', array(
				'methods'             => array( WP_REST_Server::READABLE ),
				'callback'            => array( __CLASS__, 'get_item' ),
				'permission_callback' => array( __CLASS__, 'get_permissions_check' ),
				'args'                => array(
					'id'                    => array(
						'sanitize_callback'    => 'absint',
						'validate_callback'    => array( __CLASS__, 'validate_entry_id' ),
						'default'              => 0,
					),
				),
			) );

			register_rest_route( WP_REST_API_Log_Common::PLUGIN_NAME, '/entry', array(
				'methods'             => array( WP_REST_Server::DELETABLE ),
				'callback'            => array( __CLASS__, 'delete_items' ),
				'permission_callback' => array( __CLASS__, 'delete_items_permissions_check' ),
				'args'                => array( // TODO refator delete, this won't work with $_REQUESTs
					'older-than-seconds'       => array(
						'sanitize_callback'    => 'absint',  // TODO add validate callback
						'default'              => DAY_IN_SECONDS * 30,
					),
				),
			) );

			// Route to delete all log entries.
			register_rest_route( WP_REST_API_Log_Common::PLUGIN_NAME, '/entries', array(
				'methods'             => array( WP_REST_Server::DELETABLE ),
				'callback'            => array( __CLASS__, 'purge_log' ),
				'permission_callback' => array( __CLASS__, 'delete_items_permissions_check' ),
			) );

			register_rest_route( WP_REST_API_Log_Common::PLUGIN_NAME, '/routes', array(
				'methods'             => array( WP_REST_Server::READABLE ),
				'callback'            => array( __CLASS__, 'get_routes' ),
				'permission_callback' => array( __CLASS__, 'get_permissions_check' ),
			) );
		}

		/**
		 * Returns a list of routes available for download.
		 *
		 * @return array
		 */
		static public function get_download_routes() {

			$routes = array(
				'request' => array(
					'body_params',
					'query_params',
					'body',
					'headers',
					),
				'response' => array(
					'body',
					'headers',
					)
			);

			return apply_filters( 'wp-rest-api-log-download-routes', $routes );
		}

		/**
		 * Gets REST API endpoint URLs to download entry properties.
		 *
		 * @param object $entry REST API Log Entry.
		 * @return array
		 */
		static public function get_download_urls( $entry ) {

			$download_routes = self::get_download_routes();
			$download_urls = array();

			foreach( $download_routes as $rr => $properties ) {
				$download_urls[ $rr ] = array();

				foreach( $properties as $property ) {
					$url = rest_url( "/wp-rest-api-log/entry/{$entry->ID}/{$rr}/{$property}/download" );
					if ( is_ssl() ) {
						$url = set_url_scheme( $url, 'https' );
					}

					// Create a hash for this request.
					$hash = wp_hash( wp_nonce_tick() . "wp-rest-api-log-download-{$rr}-{$property}" );

					// Add the hash to the URL.
					$url = add_query_arg( 'hash', $hash, $url );

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
		static public function register_download_routes() {

			foreach ( self::get_download_routes() as $request_response => $properties ) {
				foreach( $properties as $property ) {

					register_rest_route( WP_REST_API_Log_Common::PLUGIN_NAME, "/entry/(?P<id>[\d]+)/(?P<rr>{$request_response})/(?P<property>{$property})/download", array(
						'methods'             => array( WP_REST_Server::READABLE ),
						'callback'            => array( __CLASS__, 'download_json' ),
						'permission_callback' => array( __CLASS__, 'download_permissions_check' ),
						'args'                => array(
							'rr' => array(
								'required'             => true,
								'sanitize_callback'    => 'sanitize_text_field',
								),
							'property' => array(
								'required'             => true,
								'sanitize_callback'    => 'sanitize_text_field',
								),
							'hash' => array(
								'required'             => true,
								'sanitize_callback'    => 'sanitize_text_field',
								),
							'id' => array(
								'required'             => true,
								'sanitize_callback'    => 'absint',
								'validate_callback'    => array( __CLASS__, 'validate_entry_id' ),
							),
						),
					) );
				}
			}
		}

		static public function get_items( WP_REST_Request $request ) {

			$args = array(
				'id'                  => $request['id'],
				'page'                => $request['page'],
				'records_per_page'    => $request['records-per-page'],
				'after_id'            => $request['after-id'],
				'before_id'           => $request['before-id'],
				'from'                => $request['from'],
				'to'                  => $request['to'],
				'method'              => $request['method'],
				'status'              => $request['status'],
				'route'               => $request['route'],
				'route_match_type'    => $request['route-match-type'],
				'params'              => $request['params'],
				);

			$db     = new WP_REST_API_Log_DB();
			$posts  = $db->search( $args );

			return rest_ensure_response( WP_REST_API_Log_Entry::from_posts( $posts ) );
		}

		static public function get_item( WP_REST_Request $request ) {
			return rest_ensure_response( self::get_entry( $request['id'] ) );
		}

		static public function invalid_entry_id_error( $id ) {
			return new WP_Error( 'invalid_entry_id', sprintf( __( 'Invalid REST API Log ID %d.', 'wp-rest-api-log' ), $id ), array( 'status' => 404 ) );
		}

		static public function get_permissions_check() {
			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-can-view-entries', current_user_can( 'read_' . WP_REST_API_Log_DB::POST_TYPE ) );
		}

		static public function download_permissions_check( WP_REST_Request $request ) {

			$rr = ! empty( $request['rr'] ) ? sanitize_text_field( $request['rr'] ) : '';
			$property = ! empty( $request['property'] ) ? sanitize_text_field( $request['property'] ) : '';
			$hash = ! empty( $request['hash'] ) ? sanitize_text_field( $request['hash'] ) : '';

			if ( ! empty( $rr ) && ! empty( $property ) && ! empty( $hash ) ) {
				return $hash === wp_hash( wp_nonce_tick() . "wp-rest-api-log-download-{$rr}-{$property}" );
			} else {
				return false;
			}
		}

		static public function delete_items_permissions_check() {
			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-can-delete-entries', current_user_can( 'delete_' . WP_REST_API_Log_DB::POST_TYPE ) );
		}

		static public function validate_entry_id( $id ) {
			if ( $id < 1 ) {
				return invalid_entry_id_error( $id );
			} else {

				// Verify that the entry exists.
				$entry = self::get_entry( $id );

				return ! empty( $entry ) ? true : self::invalid_entry_id_error( $id );
			}
		}

		static public function get_entry( $id ) {

			$post  = get_post( $id );

			if ( ! empty( $post ) && WP_REST_API_Log_DB::POST_TYPE === $post->post_type ) {
				return new WP_REST_API_Log_Entry( $post );
			} else {
				return false;
			}
		}

		static public function get_routes( WP_REST_Request $request ) {

			global $wpdb;

			$query = $wpdb->prepare( "select distinct post_title from {$wpdb->posts} where post_type = %s and post_title is not null order by post_type",
				WP_REST_API_Log_DB::POST_TYPE );

			$routes = $wpdb->get_col( $query );

			return rest_ensure_response( $routes );

		}


		static public function delete_items( WP_REST_Request $request ) {
			// TODO refactor
			$args = array(
				'older_than_seconds'  => $request['older-than-seconds'],
				);

			$db = new WP_REST_API_Log_DB();
			return rest_ensure_response( new WP_REST_API_Log_Delete_Response( $db->delete( $args ) ) );
		}

		/**
		 * Handler to purge all log entries.
		 *
		 * @return WP_REST_Response
		 */
		static public function purge_log() {
			WP_REST_API_Log_DB::purge_all_log_entries();
			return rest_ensure_response( array( 'success' => true ) );
		}

		/**
		 * Handler for setting up the filter to allow downloading of files.
		 *
		 * @param  WP_REST_Request $request REST request.
		 * @return WP_REST_Response
		 */
		static public function download_json( WP_REST_Request $request ) {

			$entry = self::get_entry( $request['id'] );

			add_filter( 'rest_pre_serve_request', array( __CLASS__, 'download_json_pre_serve_request' ), 10, 4 );

			return rest_ensure_response(
				array(
					'wp-rest-api-log-download' => true,
					'entry' => $entry
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
		static public function download_json_pre_serve_request( $served, $response, $request, $server ) {

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
					$value = json_encode( $value, JSON_PRETTY_PRINT );
				} else {

					// See if this is a JSON field.
					$obj = json_decode( $value );
					if ( null === $obj ) {

						// Might still be a JSON string though.
						$check_json = trim( $value );
						$is_json = false;
						if ( ! empty( $check_json ) ) {
							$is_json = '{' === substr( $check_json, 0, 1 )
								&& '}' === substr( $check_json, strlen( $check_json ) - 1 );
						}

						if ( ! $is_json ) {
							header( 'Content-Type: text/plain' );
							$ext = "txt";
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
				echo $value;

				// Tell the REST API that we handled this ourselves.
				$served = true;
			}

			return $served;
		}

	}

}
