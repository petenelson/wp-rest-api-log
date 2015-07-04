<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Controller' ) ) {

	class WP_REST_API_Log_Controller {

		static $namespace     = 'wp-rest-api-log';

		public function plugins_loaded() {
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		}


		public function register_rest_routes() {

			register_rest_route( self::$namespace, '/search', array(
				'methods'         => array( WP_REST_Server::METHOD_GET, WP_REST_Server::METHOD_POST ),
				'callback'        => array( $this, 'search' ),
				'args'            => array(
					'from'        => array(
						'default'           => '',
					),
					'to'                    => array(
						'default'              => current_time( 'mysql' ),
					),
					'fields'                => array(
						'sanitize_callback'    => 'sanitize_key',
						'default'              => 'basic',
					),
					'route'                 => array(
						'sanitize_callback'    => 'sanitize_key',
						'default'              => '',
					),
					'route-match-type'      => array(
						'sanitize_callback'    => 'sanitize_key',
						'default'              => 'wildcard',
					),
					'id'                    => array(
						'sanitize_callback'    => 'absint',
						'default'              => 0,
					),
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
				),
			) );

		}


		public function search( WP_REST_Request $request ) {
			$args = array(
				'id'                  => $request['id'],
				'fields'              => $request['fields'],
				'page'                => $request['page'],
				'records_per_page'    => $request['records-per-page'],
				'after_id'            => $request['after-id'],
				'before_id'           => $request['before-id'],
				'from'                => $request['from'],
				'to'                  => $request['to'],
				'route'               => $request['route'],
				'route-match-type'    => $request['route-match-type'],
				);

			$db = new WP_REST_API_Log_DB();
			return rest_ensure_response( $db->search( $args ) );

		}


	}

}
