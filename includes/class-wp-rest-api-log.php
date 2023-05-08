<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log' ) ) {

	class WP_REST_API_Log {


		/**
		 * The plugins_loaded WordPress hook.
		 *
		 * @return void
		 */
		static public function plugins_loaded() {

			// Filter that is called by the REST API right before it sends a response
			add_filter( 'rest_pre_serve_request', array( __CLASS__, 'log_rest_api_response' ), 9999, 4 );

			// Disabling logging for specific requests.
			add_filter( 'wp-rest-api-log-bypass-insert', array( __CLASS__, 'bypass_common_routes' ), 10, 4 );

			// Create cron job.
			add_action( 'admin_init', array( __CLASS__, 'create_purge_cron' ) );

			// Handler for cron job.
			add_action( 'wp-rest-api-log-purge-old-records', array( __CLASS__, 'purge_old_records' ) );


			// for local development
			// add_filter( 'determine_current_user', function( $user_id ) {

			// 	if ( 'hello' == $_REQUEST['dev-key'] ) {
			// 		$user = get_user_by( 'login', $_REQUEST['login'] );
			// 		if ( ! empty( $user ) ){
			// 			$user_id = $user->ID;
			// 		}
			// 	}

			// 	return $user_id;

			// } );

		}


		/**
		 * Logs the REST API request & response right before it returns the data to the client.
		 *
		 * @param  bool   $served      True if the response was served by something other than the REST API, otherwise false,
		 * @param  object $result      REST API response data.
		 * @param  object $request     REST API request data.
		 * @param  object $rest_server REST API server.
		 * @return bool   $served
		 */
		static public function log_rest_api_response( $served, $result, $request, $rest_server ) {

			// don't log anything if logging is not enabled
			$logging_enabled = apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-setting-is-enabled',
				true,
				'general',
				'logging-enabled'
				);

			if ( ! $logging_enabled ) {
				return $served;
			}


			// Allow specific requests to not be logged
			$bypass_insert = apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-bypass-insert', false, $result, $request, $rest_server );
			if ( $bypass_insert ) {
				return $served;
			}

			// Determine if this route should be logged based on route filters.
			$route = $request->get_route();
			$can_log_route = WP_REST_API_Log_Filters::can_log_route( $route );

			// Allow this to be filtered.
			$can_log_route = apply_filters( 'wp-rest-api-log-can-log-route', $can_log_route, $route, $request, $result, $rest_server );


			// Exit out if we can't log this route.
			if ( ! $can_log_route ) {
				return $served;
			}

			$current_user = wp_get_current_user();

			$server = filter_var_array(
				$_SERVER,
				[
					'REMOTE_ADDR'          => WP_REST_API_Log_Common::filter_strip_all_tags(),
					'HTTP_X_FORWARDED_FOR' => WP_REST_API_Log_Common::filter_strip_all_tags(),
				]
			);

			$args = array(
				'ip_address'            => $server[ 'REMOTE_ADDR' ],
				'user'                  => $current_user->user_login,
				'http_x_forwarded_for'  => $server[ 'HTTP_X_FORWARDED_FOR' ],
				'route'                 => $route,
				'method'                => $request->get_method(),
				'status'                => $result->get_status(),
				'request'               => array(
					'body'                 => $request->get_body(),
					'headers'              => $request->get_headers(),
					'query_params'         => $request->get_query_params(),
					'body_params'          => $request->get_body_params(),
					),
				'response'              => array(
					'body'                 => $result,
					'headers'              => self::get_response_headers( $result ),
					),
				);

			do_action( WP_REST_API_Log_Common::PLUGIN_NAME . '-insert', $args );

			return $served;
		}

		static public function get_response_headers( $result ) {
			// headers_list returns an array of headers like this: Content-Type: application/json;
			// we want a key/value array
			if ( function_exists( 'headers_list' ) ) {
				$headers = array();
				foreach ( headers_list() as $header ) {
					$header = explode( ':', $header );
					if ( count( $header ) > 1 ) {

						// Grab the header name.
						$header_name = array_shift( $header );

						// Grab any remaining items in the array as the value
						$header_value = implode( '', $header );

						$headers[ $header_name ] = trim( $header_value );
					}
				}
				return $headers;
			} else {
				return $result->get_headers();
			}
		}

		static public function create_purge_cron() {
			if ( ! wp_next_scheduled( 'wp-rest-api-log-purge-old-records' ) ) {
				wp_schedule_event( time() + 60, 'hourly', 'wp-rest-api-log-purge-old-records' );
			}
		}

		/**
		 * Gets old REST API Log record IDs.
		 *
		 * @param  int $days_old How many days back to go.
		 * @return array
		 */
		public static function get_old_log_ids( $days_old ) {

			if ( empty( $days_old ) && 0 !== $days_old ) {
				$days_old = WP_REST_API_Log_Settings_General::setting_get( 'general', 'purge-days' );
			}

			if ( empty( $days_old ) && 0 !== $days_old ) {
				return array();
			}

			$db = new WP_REST_API_Log_DB();
			$args = array(
				'fields'                 => 'ids',
				'to'                     => date( 'Y-m-d H:i', current_time( 'timestamp' ) - ( DAY_IN_SECONDS * $days_old ) ),
				'posts_per_page'         => -1,
				'update_post_meta_cache' => false,
				'update_term_meta_cache' => false,
			);

			$ids = $db->search( $args );

			return $ids;
		}

		/**
		 * Purges old REST API Log records.
		 *
		 * @param  int $days_old How many days back to go.
		 * @param  boolean $dry_run  Is this a dry run?
		 * @return int
		 */
		static public function purge_old_records( $days_old = false, $dry_run = false ) {

			if ( empty( $days_old ) ) {
				$days_old = WP_REST_API_Log_Settings_General::setting_get( 'general', 'purge-days' );
			}

			$days_old = absint( $days_old );
			if ( empty( $days_old ) ) {
				return;
			}

			$ids = self::get_old_log_ids( $days_old );

			$number_deleted = 0;

			if ( ! empty( $ids ) && is_array( $ids ) ) {
				foreach ( $ids as $id ) {
					if ( ! $dry_run ) {
						wp_delete_post( $id, true );
					}
					$number_deleted++;
				}
			}

			return $number_deleted;

		}

		static public function bypass_common_routes( $bypass_insert, $result, $request, $rest_server ) {

			// Ignore our own plugin.
			$ignore_routes = array(
				'/wp-rest-api-log',
				);

			// See if the oembed route is ignored.
			if ( '1' === apply_filters( 'wp-rest-api-log-setting-get', 'routes', 'ignore-core-oembed' ) ) {
				$ignore_routes[] = '/oembed/1.0/embed';
			}

			foreach ( $ignore_routes as $route ) {
				if ( stripos( $request->get_route(), $route ) !== false ) {
					return true;
				}
			}

			return $bypass_insert;

		}

	} // end class

}
