<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log' ) ) {

	class WP_REST_API_Log {


		public function plugins_loaded() {

			// filter that is called by the REST API right before it sends a response
			add_filter( 'rest_pre_serve_request', array( $this, 'log_rest_api_dispatch' ), 10, 4 );

			add_filter( 'wp-rest-api-log-bypass-insert', function( $bypass_insert, $result, $request, $rest_server ) {
				// an example of disabling logging for specific requests

				// other conditions can be checked here
				if ( '/hello/world' === $request->get_route() ) {
					$bypass_insert = true;
				}

				return $bypass_insert;

			}, 10, 4 );

		}


		public function log_rest_api_dispatch( $served, $result, $request, $rest_server ) {

			// allow specific requests to not be logged
			$bypass_insert = apply_filters( WP_REST_API_Log_Common::$plugin_name . '-bypass-insert', false, $result, $request, $rest_server );
			if ( $bypass_insert ) {
				return $served;
			}


			$args = array(
				'ip_address'            => $_SERVER['REMOTE_ADDR'],
				'route'                 => $request->get_route(),
				'method'                => $request->get_method(),
				'request_headers'       => json_encode( $request->get_headers() ),
				'request_query_params'  => json_encode( $request->get_query_params() ),
				'request_body_params'   => json_encode( $request->get_body_params() ),
				'request_body'          => json_encode( $request->get_body() ),
				'response_headers'      => json_encode( function_exists( 'headers_list' ) ? headers_list() : $result->get_headers() ),
				'response_body'         => json_encode( $result ),
			);

			do_action( WP_REST_API_Log_Common::$plugin_name . '-insert', $args );

			return $served;

		}


	} // end class

}
