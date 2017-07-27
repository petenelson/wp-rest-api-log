<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_DB' ) ) {

	class WP_REST_API_Log_DB {

		const POST_TYPE        = 'wp-rest-api-log';
		const TAXONOMY_METHOD  = 'wp-rest-api-log-method';
		const TAXONOMY_STATUS  = 'wp-rest-api-log-status';
		const TAXONOMY_SOURCE  = 'wp-rest-api-log-source';

		const POST_META_IP_ADDRESS             = '_ip-address';
		const POST_META_REQUEST_USER           = '_request_user';
		const POST_META_HTTP_X_FORWARDED_FOR   = '_http_x_forwarded_for';
		const POST_META_MILLISECONDS           = '_milliseconds';
		const POST_META_REQUEST_BODY           = '_request_body';


		public function plugins_loaded() {
			add_action( WP_REST_API_Log_Common::PLUGIN_NAME . '-insert', array( $this, 'insert' ), 10, 4 );

			// adds where statement when searching for routes
			add_filter( 'posts_where', array( $this, 'add_where_route' ), 10, 2 );

			// adds where statement when searching post id ranges
			add_filter( 'posts_where', array( $this, 'add_where_post_id' ), 10, 2 );

		}


		static private function plugin_name() {
			return WP_REST_API_Log_Common::PLUGIN_NAME . '-entries';
		}


		/**
		 * Inserts a REST API log custom post type record and corresponding
		 * post meta and taxonomy terms.
		 *
		 * @param  array $args
		 * @return int
		 */
		public function insert( $args ) {

			$current_user = wp_get_current_user();

			$args = wp_parse_args( $args, array(
				'time'                  => current_time( 'mysql' ),
				'ip_address'            => filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING ),
				'user'                  => $current_user->user_login,
				'http_x_forwarded_for'  => filter_input( INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_SANITIZE_STRING ),
				'route'                 => '',
				'source'                => 'WP REST API',
				'method'                => filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING ),
				'status'                => 200,
				'request'               => array(
					'body'                 => '',
					),
				'response'               => array(
					'body'                 => '',
					),
				'milliseconds'          => 0,

				// This can be a K/V array of additional post meta to store.
				'post_meta'             => array(),

				)
			);

			if ( empty( $args['milliseconds'] ) ) {
				global $wp_rest_api_log_start;
				$now = WP_REST_API_Log_Common::current_milliseconds();
				$args['milliseconds'] = absint( $now -  $wp_rest_api_log_start );
			}

			// Setup the post content with JSON from the response.
			$post_content = wp_json_encode( $args['response']['body'], JSON_PRETTY_PRINT );

			// Replace \n with PHP_EOL.
			$post_content = str_replace( '\n', PHP_EOL, $post_content );
			$args['request']['body'] = str_replace( '\n', PHP_EOL, $args['request']['body'] );

			// Allow filtering.
			$args = apply_filters( self::plugin_name() . '-pre-insert', $args );

			$new_post = array(
				'post_author'     => 0,
				'post_type'       => self::POST_TYPE,
				'post_title'      => $args['route'],
				'post_content'    => $post_content,
				'post_status'     => 'publish',

				// Append a random string to the end to attempt a unique post slug
				// route names will often be the same, so this helps WordPress from
				// having to loop through several times while generating a unique
				// post slug.
				'post_name'       => sanitize_title( $args['route'] ) . '-' . wp_generate_password( 6, true ),

				);

			// Allow filtering.
			$new_post = apply_filters( self::plugin_name() . '-pre-insert-new-post', $new_post, $args );

			$post_id = wp_insert_post( $new_post );

			if ( ! empty( $post_id ) ) {
				$this->insert_post_terms( $post_id, $args );
				$this->insert_post_meta( $post_id, $args );

				$this->insert_request_meta( $post_id, $args );
				$this->insert_response_meta( $post_id, $args );

				global $wp_rest_api_log_new_entry_id;
				$wp_rest_api_log_new_entry_id = $post_id;

			}

			return $post_id;
		}


		private function insert_post_terms( $post_id, $args ) {

			// sanitize and store method
			if ( ! WP_REST_API_Log_Common::is_valid_method( $args['method'] ) ) {
				$args['method'] = 'GET';
			}
			wp_set_post_terms( $post_id, $args['method'], self::TAXONOMY_METHOD );

			// store status code
			$args['status'] = absint( $args['status'] );
			wp_set_post_terms( $post_id, $args['status'], self::TAXONOMY_STATUS );

			// store the source
			wp_set_post_terms( $post_id, $args['source'], self::TAXONOMY_SOURCE );

		}


		private function insert_post_meta( $post_id, $args ) {

			$meta = array(
				self::POST_META_IP_ADDRESS             => $args['ip_address'],
				self::POST_META_REQUEST_USER           => $args['user'],
				self::POST_META_HTTP_X_FORWARDED_FOR   => $args['http_x_forwarded_for'],
				self::POST_META_MILLISECONDS           => $args['milliseconds'],
				self::POST_META_REQUEST_BODY           => $args['request']['body'],
				);

			foreach ( $meta as $key => $value ) {
				if ( is_array( $value ) && 1 === count( $value ) ) {
					$value = $value[0];
				}
				if ( ! empty( $value ) ) {
					add_post_meta( $post_id, $key, $value );
				}
			}

			// log any additional post meta
			if ( ! empty( $args['post_meta'] ) && is_array( $args['post_meta'] ) ) {

				foreach( $args['post_meta'] as $key => $value ){
					add_post_meta( $post_id, $key, $value );
				}

			}

		}


		private function insert_request_meta( $post_id, $args ) {

			$request = 'request';
			$types   = array( 'headers', 'query_params', 'body_params' );

			foreach( $types as $type ) {

				if ( ! empty( $args[ $request ][ $type ] ) ) {
					foreach ( $args[ $request ][ $type ] as $key => $value ) {

						if ( is_array( $value ) &&
						    1 === count( $value ) &&
						    'headers' === $type ) {
							$value = reset( $value );
						}

						if ( ! empty( $value ) ) {
							add_post_meta( $post_id, "{$request}_{$type}|{$key}", $value );
						}

					}
				}
			}

		}


		private function insert_response_meta( $post_id, $args ) {

			$response = 'response';
			$types   = array( 'headers' );

			foreach( $types as $type ) {

				if ( ! empty( $args[ $response ][ $type ] ) ) {
					foreach ( $args[ $response ][ $type ] as $key => $value ) {

						if ( is_array( $value ) &&
						    1 === count( $value ) &&
						    'headers' === $type ) {
							$value = reset( $value );
						}

						if ( ! empty( $value ) ) {
							add_post_meta( $post_id, "{$response}_{$type}|{$key}", $value );
						}

					}
				}
			}

		}


		public function search( $args = array() ) {

			$args = wp_parse_args( $args,
				array(
					'after_id'           => 0,
					'before_id'          => 0,
					'from'               => '',
					'to'                 => current_time( 'mysql' ),
					'route'              => '',
					'route_match_type'   => 'exact',
					'method'             => false,
					'status'             => false,
					'page'               => 1,
					'posts_per_page'     => 50,
					'params'             => array(),
				)
			);

			$query_args = array(
				'post_type'         => self::POST_TYPE,
				'posts_per_page'    => $args['posts_per_page'],
				'paged'             => $args['page'],
				'date_query'        => array(),
				'tax_query'         => array( 'relation' => 'AND' ),
  				);

			if ( ! empty( $args['fields'] ) ) {
				$query_args['fields'] = $args['fields'];
			}

			if ( ! empty( $args['id'] ) ) {
				$query_args['p'] = $args['id'];
			}

			// dates
			if ( ! empty( $args['from'] ) ) {
				$query_args['date_query']['after'] = $args['from'];
			}

			if ( ! empty( $args['to'] ) ) {
				$query_args['date_query']['before'] = $args['to'];
			}

			// route, handled by posts_where filter
			if ( ! empty( $args['route'] ) ) {
				$query_args['_wp-rest-api-log-route']              = $args['route'];
				$query_args['_wp-rest-api-log-route-match-type']   = $args['route_match_type'];
			}

			// post id, handled by posts_where filter
			if ( ! empty( $args['after_id'] ) ) {
				$query_args['_wp-rest-api-log-after-id']           = $args['after_id'];
			}

			if ( ! empty( $args['before_id'] ) ) {
				$query_args['_wp-rest-api-log-before-id']          = $args['before_id'];
			}

			// HTTP Method
			if ( ! empty( $args['method'] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => self::TAXONOMY_METHOD,
					'field'    => 'slug',
					'terms'    => explode( ',', $args['method'] ),
					);
			}

			// HTTP Status
			if ( ! empty( $args['status'] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy' => self::TAXONOMY_STATUS,
					'field'    => 'slug',
					'terms'    => explode( ',', $args['status'] ),
					);
			}

			$posts = array();
			$query = new WP_Query( $query_args );

			if ( $query->have_posts() ) {
				$posts = $query->posts;
			}

			return $posts;

		}

		/**
		 * Adds custom where statement for routes
		 *
		 * @param string $where original SQL
		 * @param object $query WP_Query
		 * @return string
		 */
		public function add_where_route( $where, $query ) {

			$route = $query->get( '_wp-rest-api-log-route' );
			if ( ! empty( $route ) ) {

				global $wpdb;

				$route_match_type   = $query->get( '_wp-rest-api-log-route-match-type' );
				$route_start        = '';
				$route_end          = '';

				switch ( $route_match_type ) {

					case 'starts_with':
						$route_end = '%';
						break;

					case 'ends_with':
						$route_start = '%';
						break;

					case 'wildcard':
						$route_start   = '%';
						$route_end     = '%';
						break;
				}

				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_title like %s", $route_start . $route . $route_end );

			}

			return $where;
		}


		/**
		 * Adds custom where statement for post id ranges
		 *
		 * @param string $where original SQL
		 * @param object $query WP_Query
		 * @return string
		 */
		public function add_where_post_id( $where, $query ) {

			global $wpdb;

			$after_id = $query->get( '_wp-rest-api-log-after-id' );
			if ( ! empty( $after_id ) ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.ID > %d", $after_id );
			}

			$before_id = $query->get( '_wp-rest-api-log-before-id' );
			if ( ! empty( $before_id ) ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.ID < %d", $before_id );
			}

			return $where;
		}


		/**
		 * Gets a list of log IDs from the legacy custom table that need
		 * to be migrated to a custom post type
		 *
		 * @return array
		 */
		public function get_log_ids_to_migrate() {

			global $wpdb;

			$existing_tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}wp_rest_api_log%';" );
			$log_ids = array();

			if ( ! empty( $existing_tables ) ) {

				$ids = $wpdb->get_col( "select id from {$wpdb->prefix}wp_rest_api_log" );

				foreach ( $ids as $id ) {

					$query = new WP_Query( array(
						'posts_per_page'           => 1,
						'update_post_meta_cache'   => false,
						'update_post_term_cache'   => false,
						'post_type'                => self::POST_TYPE,
						'meta_key'                 => '_wp_rest_api_log_migrated_id',
						'meta_value'               => $id,
						'fields'                   => 'ids',
						'post_status'              => 'publish',
						)
					);

					if ( ! $query->have_posts() ) {
						$log_ids[] = $id;
					}

				}

			}

			return $log_ids;

		}

		/**
		 * Migrates single record from the initial version of the plugin's
		 * custom tables to a custom post type
		 *
		 * @return int
		 */
		public function migrate_db_record( $id ) {

			global $wpdb;

			$log         = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}wp_rest_api_log where id = %d", $id ) );
			$meta_rows   = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}wp_rest_api_logmeta where log_id = %d", $log->id ) );

			$args = array(
				'time'                  => $log->time,
				'ip_address'            => $log->ip_address,
				'route'                 => $log->route,
				'method'                => $log->method,
				'status'                => $log->status,
				'request'               => array(
					'body'                 => $log->request_body,
					),
				'response'               => array(
					'body'                 => json_decode( $log->response_body ),
					),
				'milliseconds'          => $log->milliseconds,
			);


			foreach( $meta_rows as $meta_row ) {

				switch ( $meta_row->meta_type ) {
					case 'header':
						$meta_type = 'headers';
						break;
					case 'query':
						$meta_type = 'query_params';
						break;
				}

				if ( ! empty( $meta_type ) ) {
					$args[ $meta_row->meta_request_response ][ $meta_type ][ $meta_row->meta_key ] = $meta_row->meta_value;
				}

			}

			$post_id = $this->insert( $args );

			// save the legacy ID so we don't migrate it again
			add_post_meta( $post_id, '_wp_rest_api_log_migrated_id', $id );

			// manually update the post dates
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_date' => $log->time,
					'post_date_gmt' => $log->time,
					'post_modified' => $log->time,
					'post_modified_gmt' => $log->time,
					),
				array(
					'ID' => $post_id, // where clause
					)
			);

			return $post_id;

		}


		/**
		 * Returns a list of all log entry IDs in the database.
		 *
		 * @return int
		 */
		static public function get_all_log_ids( ) {

			$query = new WP_Query( array(
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
				'no_found_rows'          => true,
				'post_type'              => WP_REST_API_Log_DB::POST_TYPE,
				'fields'                 => 'ids',
				'posts_per_page'         => -1,
				)
			);

			return $query->posts;
		}

		/**
		 * Purges all log entries in the database.
		 *
		 * @return void
		 */
		static public function purge_all_log_entries() {

			$post_ids = self::get_all_log_ids();

			foreach( $post_ids as $post_id ) {
				wp_delete_post( $post_id, true );
			}
		}


	} // end class

}
