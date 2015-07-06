<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Controller' ) ) {

	class WP_REST_API_Log_Controller {

		static $namespace     = 'wp-rest-api-log';

		public function plugins_loaded() {
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		}


		public function register_rest_routes() {

			register_rest_route( self::$namespace, '/entries', array(
				'methods'             => array( WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
				'args'                => array(
					'from'            => array(
						'default'           => '',
					),
					'to'                    => array(
						'default'              => current_time( 'mysql' ),
					),
					'fields'                => array(
						'default'              => 'basic',
					),
					'route'                 => array(
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
					'response_type'         => array(
						'default'           => 'json',
					)
				),
			) );


			register_rest_route( self::$namespace, '/entries/(?P<id>[\d]+)', array(
				'methods'             => array( WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
				'args'                => array(
					'fields'                => array(
						'default'              => 'basic',
					),
					'id'                    => array(
						'sanitize_callback'    => 'absint',
						'default'              => 0,
					),
				),
			) );


			register_rest_route( self::$namespace, '/entries', array(
				'methods'             => array( WP_REST_Server::DELETABLE ),
				'callback'            => array( $this, 'purge_items' ),
				'permission_callback' => array( $this, 'purge_items_permissions_check' ),
				'args'                => array(
					'older-than-seconds'       => array(
						'sanitize_callback'    => 'absint',
						'default'              => DAY_IN_SECONDS * 30,
					),
				),
			) );


			register_rest_route( self::$namespace, '/routes', array(
				'methods'             => array( WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'get_routes' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),

			) );

		}


		public function get_items( WP_REST_Request $request ) {
			$args = array(
				'id'                  => $request['id'],
				'fields'              => $request['fields'],
				'page'                => $request['page'],
				'records_per_page'    => $request['records-per-page'],
				'after_id'            => $request['after-id'],
				'before_id'           => $request['before-id'],
				'from'                => $request['from'],
				'to'                  => $request['to'],
				'method'              => $request['method'],
				'route'               => $request['route'],
				'route_match_type'    => $request['route-match-type'],
				);

			$db = new WP_REST_API_Log_DB();
			$response = $db->search( $args );

			if ( 'wp_admin_html' === $request['response_type'] && ! empty( $response->paged_records ) ) {
				$admin = new WP_REST_API_Log_Admin();
				$response->entries_html = $admin->entries_to_html( $response->paged_records );
			}

			return rest_ensure_response( $response );

		}


		public function get_item( WP_REST_Request $request ) {
			$args = array(
				'id'                  => $request['id'],
				'fields'              => $request['fields'],
				);

			$db = new WP_REST_API_Log_DB();
			return rest_ensure_response( $db->search( $args ) );

		}


		public function get_routes( WP_REST_Request $request ) {

			$db = new WP_REST_API_Log_DB();
			return rest_ensure_response( $db->distinct_routes() );

		}


		public function purge_items( WP_REST_Request $request ) {
			$args = array(
				'older_than_seconds'  => $request['older-than-seconds'],
				);

			$db = new WP_REST_API_Log_DB();
			return rest_ensure_response( $db->purge( $args ) );
		}


		public function get_permissions_check() {
			return apply_filters( WP_REST_API_Log_Common::$plugin_name . '-can-view-entries', current_user_can( 'manage_options' ) );
		}


		public function purge_items_permissions_check() {
			return apply_filters( WP_REST_API_Log_Common::$plugin_name . '-can-purge-entries', current_user_can( 'manage_options' ) );
		}

	}

}
