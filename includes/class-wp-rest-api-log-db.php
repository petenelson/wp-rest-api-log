<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_DB' ) ) {

	class WP_REST_API_Log_DB {

		public function plugins_loaded() {
			add_action( 'init', array( $this, 'register_custom_post_types' ) );
			add_action( 'init', array( $this, 'register_custom_taxonomies' ) );
			add_action( WP_REST_API_Log_Common::$plugin_name . '-insert', array( $this, 'insert' ), 10, 4 );
		}


		static private function plugin_name() {
			return WP_REST_API_Log_Common::$plugin_name . '-entries';
		}


		public function register_custom_post_types() {

			$name_s = 'REST API LOG Entry';
			$name_p = 'REST API LOG Entries';

			$labels = array(
				'name'                => __( $name_p, WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'singular_name'       => __( $name_s, WP_REST_API_Log_Common::TEXT_DOMAIN ),
			);

			$args = array(
				'labels'              => $labels,
				'show_in_rest'        => true,
				'rest_base'           => WP_REST_API_Log_Common::POST_TYPE, // allows the CPT to show up in the native API
				'hierarchical'        => false,
				'public'              => true,
				'show_ui'             => true,
				'show_in_menu'        => true, // true during development
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'has_archive'         => true,
				'query_var'           => true,
				'can_export'          => true,
				'rewrite'             => true,
				'capability_type'     => 'post',
				'supports'            => array(
					'title', 'author',
					'excerpt','custom-fields',
					)
			);

			$args = apply_filters( WP_REST_API_Log_Common::$plugin_name . '-register-post-type', $args );

			register_post_type( WP_REST_API_Log_Common::POST_TYPE, $args );

		}


		public function register_custom_taxonomies() {

			// HTTP Method
			$name_s = 'Method';
			$name_p = 'Methods';

			$labels = array(
				'name'                => __( $name_p, WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'singular_name'       => __( $name_s, WP_REST_API_Log_Common::TEXT_DOMAIN ),
			);

			$args = array(
				'labels'            => $labels,
				'public'            => true,
				'show_in_nav_menus' => true,
				'show_admin_column' => false,
				'hierarchical'      => false,
				'show_tagcloud'     => true,
				'show_ui'           => true,
				'query_var'         => true,
				'rewrite'           => true,
				'query_var'         => true,
				'capabilities'      => array(),
			);

			register_taxonomy( WP_REST_API_Log_Common::TAXONOMY_METHOD, array( WP_REST_API_Log_Common::POST_TYPE ), $args );


			// HTTP Status
			$name_s = 'Status';
			$name_p = 'Statuses';

			$args['labels']['name']           = __( $name_p, WP_REST_API_Log_Common::TEXT_DOMAIN );
			$args['labels']['singular_name']  = __( $name_s, WP_REST_API_Log_Common::TEXT_DOMAIN );

			register_taxonomy( WP_REST_API_Log_Common::TAXONOMY_STATUS, array( WP_REST_API_Log_Common::POST_TYPE ), $args );

			// namespace?

		}


		public function insert( $args ) {

			$args = wp_parse_args( $args, array(
				'time'                  => current_time( 'mysql' ),
				'ip_address'            => filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_SANITIZE_STRING ),
				'route'                 => '',
				'method'                => filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING ),
				'status'                => 200,
				'request'               => array(
					'body'                 => '',
					),
				'response'               => array(
					'body'                 => '',
					),
				'milliseconds'          => 0,
				)
			);


			if ( empty( $args['milliseconds'] ) ) {
				global $wp_rest_api_log_start;
				$now = WP_REST_API_Log_Common::current_milliseconds();
				$args['milliseconds'] = absint( $now -  $wp_rest_api_log_start );
			}

			// allow filtering
			$args = apply_filters( self::plugin_name() . '-pre-insert', $args );


			$new_post = array(
				'post_type'       => WP_REST_API_Log_Common::POST_TYPE,
				'post_title'      => $args['route'],
				'post_content'    => json_encode( $args['response']['body'] ),
				'post_status'     => 'publish',
				);

			$post_id = wp_insert_post( $new_post, $wp_error );

			if ( ! empty( $post_id ) ) {
				$this->insert_post_terms( $post_id, $args );
				$this->insert_post_meta( $post_id, $args );

				$this->insert_request_meta( $post_id, $args );
				//$this->insert_response_meta();

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
			wp_set_post_terms( $post_id, $args['method'], WP_REST_API_Log_Common::TAXONOMY_METHOD );

			// store status code
			$args['status'] = absint( $args['status'] );
			wp_set_post_terms( $post_id, $args['status'], WP_REST_API_Log_Common::TAXONOMY_STATUS );

		}


		private function insert_post_meta( $post_id, $args ) {

			$meta = array(
				WP_REST_API_Log_Common::POST_META_IP_ADDRESS    => $args['ip_address'],
				WP_REST_API_Log_Common::POST_META_MILLISECONDS  => $args['milliseconds'],
				WP_REST_API_Log_Common::POST_META_REQUEST_BODY  => $args['request']['body'],
				);

			foreach ( $meta as $key => $value ) {
				if ( is_array( $value ) && 1 === count( $value ) ) {
					$value = $value[0];
				}
				if ( ! empty( $value ) ) {
					add_post_meta( $post_id, $key, $value );
				}
			}

		}


		private function insert_request_meta( $post_id, $args ) {

			// TODO refactor this into more modular code

			if ( ! empty( $args['request']['headers'] ) ) {

				foreach ( $args['request']['headers'] as $key => $value ) {
					if ( is_array( $value ) && 1 === count( $value ) ) {
						$value = $value[0];
					}

					if ( ! empty( $value ) ) {

						add_post_meta( $post_id, '_request_header_key_' . md5( $key ), $key );
						add_post_meta( $post_id, '_request_header_value_' . md5( $key ), $value );

					}

				}

			}


			if ( ! empty( $args['request']['query_params'] ) ) {

				foreach ( $args['request']['query_params'] as $key => $value ) {
					if ( is_array( $value ) && 1 === count( $value ) ) {
						$value = $value[0];
					}

					if ( ! empty( $value ) ) {

						add_post_meta( $post_id, '_request_query_param_key_' . md5( $key ), $key );
						add_post_meta( $post_id, '_request_query_param_value_' . md5( $key ), $value );

					}

				}

			}


			if ( ! empty( $args['request']['body_params'] ) ) {

				foreach ( $args['request']['body_params'] as $key => $value ) {
					if ( is_array( $value ) && 1 === count( $value ) ) {
						$value = $value[0];
					}

					if ( ! empty( $value ) ) {

						add_post_meta( $post_id, '_request_body_param_key_' . md5( $key ), $key );
						add_post_meta( $post_id, '_request_body_param_value_' . md5( $key ), $value );

					}

				}

			}


		}



		public function search( $args = array() ) {


			$response = new stdClass();
			$response->log_entries = array();

			$args = wp_parse_args( $args,
				array(
					'after_id'           => 0,
					'before_id'          => 0,
					'from'               => '',
					'to'                 => current_time( 'mysql' ),
					'route'              => '',
					'route_match_type'   => 'wildcard',
					'method'             => '',
					'page'               => 1,
					'posts_per_page'     => 50,
					'id'                 => 0,
					'fields'             => 'basic',
					'params'             => array(),
				)
			);

			$query_args = array(
				'post_type'         => WP_REST_API_Log_Common::POST_TYPE,
				'posts_per_page'    => $args['posts_per_page'],
  				);

			global $post;
			$posts = array();
			$query = new WP_Query( $query_args );
			while ( $query->have_posts() ) {
				$query->the_post();
				$posts[] = $post;
				// $posts[] = new WP_REST_API_Log_Entry( $post );
			}


			$response->log_entries = $posts;

			return $response;


			// TODO implement new searching here




			$table_name   = self::table_name();
			$from         = "from $table_name where 1 ";
			$where        = '';
			$join         = '';

			if ( ! empty ( $args['id'] ) ) {
				$where .= $wpdb->prepare( " and {$table_name}.id = %d", $args['id'] );
			}

			if ( ! empty ( $args['from'] ) && empty ( $args['id'] ) ) {
				$where .= $wpdb->prepare( " and {$table_name}.time >= '%s'", $args['from'] );
			}

			if ( ! empty ( $args['to'] ) && empty ( $args['id'] ) ) {
				$where .= $wpdb->prepare( " and {$table_name}.time <= '%s'", $args['to'] );
			}

			if ( ! empty ( $args['method'] ) ) {
				$where .= $wpdb->prepare( " and {$table_name}.method = '%s'", $args['method'] );
			}

			if ( ! empty ( $args['before_id'] ) ) {
				$where .= $wpdb->prepare( " and id < %d", $args['before_id'] );
			}

			if ( ! empty ( $args['after_id'] ) ) {
				$where .= $wpdb->prepare( " and {$table_name}.id > %d", $args['after_id'] );
			}

			// TODO add query for params here

			if ( is_array( $args['params'] ) && ! empty( $args['params'] ) && !empty( $args['params'][0]['name'] ) ) {
				$data->logmeta_id_query = $db_meta->build_logmeta_id_query( $args['params'] );
				$where .= ' and id in ( ' . $data->logmeta_id_query . ')';
			}



			// TODO refactor this to accept an array
			if ( ! empty ( $args['route'] ) ) {


				switch ( $args['route_match_type'] ) {

					case 'starts-with':
						$where .= $wpdb->prepare( " and route like %s", $args['route'] . '%' );
						break;

					case 'exact':
						$where .= $wpdb->prepare( " and route like '%s'", $args['route'] );
						break;

					default:
						$where .= $wpdb->prepare( " and route like '%%%s%%'", $args['route'] );
						break;

				}

			}



			// get a total count
			$data->query_count = "select count(distinct {$table_name}.id) " . $from . $join . $where;
			$data->total_records = absint( $wpdb->get_var( $data->query_count ) );

			// get the records
			$order_by = ' order by time desc';
			$limit = '';
			if ( empty( $args['id'] ) ) {
				$args['records_per_page'] = absint( $args['records_per_page'] );
				$args['page'] = absint( $args['page'] );
				$limit = $wpdb->prepare( ' limit %d', $args['records_per_page'] );
				if ( $args['page'] > 1 ) {
					$limit .= $wpdb->prepare( ' offset %d', $args['page'] * $args['records_per_page'] );
				}
			}

			switch ( $args['fields'] ) {
				case 'basic';
					$fields = 'id, time, ip_address, method, route, status, milliseconds, char_length(response_body) as response_body_length';
					break;
				default:
					$fields = '*';
					break;
			}

			$data->args = $args;
			$data->query = 'select ' . $fields . ' ' . $from . $where . $order_by . $limit;
			$data->paged_records = $wpdb->get_results( $data->query );

			// cleanup data, set datatypes, etc
			$data = $this->cleanup_data( $data );

			// get the logmeta

			if ( ! empty( $data->paged_records ) && 'basic' !== $args['fields'] ) {
				$data->paged_records = $db_meta->add_meta_to_records( $data->paged_records );
			}



			return $data;

		}


		public function delete( $args ) {

			return;

			// TODO implement post trashing



			global $wpdb;

			$args = wp_parse_args( $args, array(
				'older_than_seconds' => DAY_IN_SECONDS * 30,
			));

			$table_name = self::table_name();
			$date = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - absint( $args['older_than_seconds'] ) );

			$data = new stdClass();
			$data->args = $args;
			$data->older_than_date = $date;
			$data->query = $wpdb->prepare( "delete from $table_name where time < %s", $date );
			$data->records_affected = $wpdb->query( $data->query );

			return $data;

		}


		private function cleanup_data( $data ) {

			// TODO refactor this


			if ( ! is_array( $data->paged_records ) ) {
				return $data;
			}

			foreach ( $data->paged_records as &$record ) {

				if ( ! empty( $record->response_body ) ) {
					$record->response_body_length = absint( strlen( $record->response_body ) );
					$record->response_body = json_decode( $record->response_body );
				} else {
					$record->response_body = '';
					$record->response_body_length = absint( $record->response_body_length );
				}

				$record->id            = absint( $record->id );
				$record->milliseconds  = absint( $record->milliseconds );

				$record->permalink     = add_query_arg( array( 'page' => WP_REST_API_Log_Common::$plugin_name, 'id' => $data->paged_records[ $i ]->id ), admin_url( 'tools.php' ) );


			}

			return $data;

		}



	} // end class

}
