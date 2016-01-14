<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log' ) ) {

	class WP_REST_API_Log {


		/**
		 * plugins_loaded WordPress hook
		 * @return void
		 */
		public function plugins_loaded() {

			// filter that is called by the REST API right before it sends a response
			add_filter( 'rest_pre_serve_request', array( $this, 'log_rest_api_response' ), 9999, 4 );

			add_filter( 'wp-rest-api-log-bypass-insert', function( $bypass_insert, $result, $request, $rest_server ) {
				// an example of disabling logging for specific requests

				if ( stripos( $request->get_route(), '/wp-rest-api-log') !== false ) {
					$bypass_insert = true;
				}

				return $bypass_insert;

			}, 10, 4 );


			// for local development
			// remove this for deployment
			add_filter( 'determine_current_user', function( $user_id ) {

				if ( 'hello' == $_REQUEST['dev-key'] ) {
					$user = get_user_by( 'login', $_REQUEST['login'] );
					if ( ! empty( $user ) ){
						$user_id = $user->ID;
					}
				}

				return $user_id;

			} );

		}


		/**
		 * Logs the REST API request & response right before it returns the data to the client
		 *
		 * @param  bool   $served        true if the response was served by something other than the REST API, otherwise false
		 *
		 * @param  object $result        response data
		 *
		 * @param  object $request       request data
		 *
		 * @param  object $rest_server   REST API server
		 *
		 * @return bool   $served
		 */
		public function log_rest_api_response( $served, $result, $request, $rest_server ) {


			// allow specific requests to not be logged
			$bypass_insert = apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-bypass-insert', false, $result, $request, $rest_server );
			if ( $bypass_insert ) {
				return $served;
			}

			$args = array(
				'ip_address'            => $_SERVER['REMOTE_ADDR'],
				'route'                 => $request->get_route(),
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
					'headers'              => $this->get_response_headers( $result ),
					),
				);


			wp_send_json( $args  );

			do_action( WP_REST_API_Log_Common::PLUGIN_NAME . '-insert', $args );

			return $served;

		}


		private function get_response_headers( $result ) {
			// headers_list returns an array of headers like this: Content-Type: application/json;
			// we want a key/value array
			if ( function_exists( 'headers_list' ) ) {
				$headers = array();
				foreach ( headers_list() as $header ) {
					$header = explode( ':', $header );
					if ( count( $header ) > 1 ) {
						$headers[ $header[0] ] = trim( $header[1] );
					}
				}
				return $headers;
			} else {
				return $result->get_headers();
			}
		}


	} // end class

}
