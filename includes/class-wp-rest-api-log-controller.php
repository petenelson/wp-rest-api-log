<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Controller' ) ) {

	class WP_REST_API_Log_Controller {


		public function plugins_loaded() {
			add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		}


		public function register_rest_routes() {

			register_rest_route( WP_REST_API_Log_Common::PLUGIN_NAME, '/entries', array(
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
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
				'args'                => array(
					'id'                    => array(
						'sanitize_callback'    => 'absint',
						'validate_callback'    => array( $this, 'validate_entry_id' ),
						'default'              => 0,
					),
				),
			) );


			register_rest_route( WP_REST_API_Log_Common::PLUGIN_NAME, '/entry', array(
				'methods'             => array( WP_REST_Server::DELETABLE ),
				'callback'            => array( $this, 'delete_items' ),
				'permission_callback' => array( $this, 'delete_items_permissions_check' ),
				'args'                => array( // TODO refator delete, this won't work with $_REQUESTs
					'older-than-seconds'       => array(
						'sanitize_callback'    => 'absint',  // TODO add validate callback
						'default'              => DAY_IN_SECONDS * 30,
					),
				),
			) );


			register_rest_route( WP_REST_API_Log_Common::PLUGIN_NAME, '/routes', array(
				'methods'             => array( WP_REST_Server::READABLE ),
				'callback'            => array( $this, 'get_routes' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			) );

		}


		public function get_items( WP_REST_Request $request ) {

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


		public function get_item( WP_REST_Request $request ) {

			$post  = get_post( $request['id'] );
			$entry = new WP_REST_API_Log_Entry( $args['id'] );

			if ( ! empty( $post ) && WP_REST_API_Log_DB::POST_TYPE === $post->post_type ) {
				return rest_ensure_response( new WP_REST_API_Log_Entry( $post ) );
			} else {
				return new WP_Error( 'invalid_entry_id', sprintf( __( 'Invalid REST API Log ID %d.', 'wp-rest-api-log' ), $args['id'] ), array( 'status' => 404 ) );
			}

		}


		public function validate_entry_id( $id ) {
			if ( $id < 1 ) {
				return new WP_Error( 'invalid_entry_id', sprintf( __( 'Invalid REST API Log ID %d.', 'wp-rest-api-log' ), $args['id'] ), array( 'status' => 404 ) );
			} else {
				return true;
			}
		}

		public function get_routes( WP_REST_Request $request ) {

			global $wpdb;

			$query = $wpdb->prepare( "select distinct post_title from {$wpdb->posts} where post_type = %s and post_title is not null order by post_type",
				WP_REST_API_Log_DB::POST_TYPE );

			$routes = $wpdb->get_col( $query );

			return rest_ensure_response( $routes );

		}


		public function delete_items( WP_REST_Request $request ) {
			// TODO refactor
			$args = array(
				'older_than_seconds'  => $request['older-than-seconds'],
				);

			$db = new WP_REST_API_Log_DB();
			return rest_ensure_response( new WP_REST_API_Log_Delete_Response( $db->delete( $args ) ) );
		}


		public function get_permissions_check() {
			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-can-view-entries', current_user_can( 'read_' . WP_REST_API_Log_DB::POST_TYPE ) );
		}


		public function delete_items_permissions_check() {
			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-can-delete-entries', current_user_can( 'delete_' . WP_REST_API_Log_DB::POST_TYPE ) );
		}

	}

}
