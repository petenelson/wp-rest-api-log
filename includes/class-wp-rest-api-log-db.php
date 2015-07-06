<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_DB' ) ) {

	class WP_REST_API_Log_DB {

		const DB_VERSION          = '27';
		const META_REQUEST        = 'request';
		const META_RESPONSE       = 'response';
		const META_PARAM_HEADER   = 'header';
		const META_PARAM_QUERY    = 'query';
		const META_PARAM_BODY     = 'body';


		public function plugins_loaded() {
			add_action( 'admin_init', 'WP_REST_API_Log_DB::create_or_update_tables' );
			add_action( WP_REST_API_Log_Common::$plugin_name . '-insert', array( $this, 'insert' ) );
		}


		static public function create_or_update_tables() {

			if ( self::DB_VERSION !== get_option( WP_REST_API_Log_Common::$plugin_name . '-dbversion' ) ) {

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

				global $wpdb;

				$charset_collate = $wpdb->get_charset_collate();
				$table_name = self::table_name();
				$table_name_logmeta = self::table_name_logmeta();

				$sql = "CREATE TABLE $table_name (
				  id bigint NOT NULL AUTO_INCREMENT,
				  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				  ip_address varchar(30) NULL,
				  method varchar(20) DEFAULT '' NOT NULL,
				  route varchar(100) DEFAULT '' NOT NULL,
				  querystring text NULL,
				  request_body text NULL,
				  response_body longtext NULL,
				  milliseconds smallint NOT NULL,
				  PRIMARY KEY id (id),
				  KEY time (time),
				  KEY route (route)
				) $charset_collate;";

				dbDelta( $sql );


				$sql = "CREATE TABLE $table_name_logmeta (
				  id bigint NOT NULL AUTO_INCREMENT,
				  log_id bigint NOT NULL,
				  meta_request_response varchar(10) NOT NULL,
				  meta_type varchar(10) NOT NULL,
				  meta_key varchar(255) NULL,
				  meta_value longtext NULL,
				  PRIMARY KEY id (id),
				  KEY log_id (log_id),
				  KEY req_type_key (meta_request_response,meta_type,meta_key)
				) $charset_collate;";

				dbDelta( $sql );

				update_option( WP_REST_API_Log_Common::$plugin_name . '-dbversion', self::DB_VERSION );

			}

		}


		static public function table_name() {
			global $wpdb;
			return $wpdb->prefix . 'wp_rest_api_log';
		}


		static public function table_name_logmeta() {
			return self::table_name() . 'meta';
		}


		public function insert( $args ) {

			global $wpdb;

			$args = wp_parse_args( $args, array(
				'time'                  => current_time( 'mysql' ),
				'ip_address'            => $_SERVER['REMOTE_ADDR'],
				'route'                 => '',
				'method'                => $_SERVER['REQUEST_METHOD'],
				'querystring'           => $_SERVER['QUERY_STRING'],
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


			$inserted = $wpdb->insert( self::table_name(),
				array(
					'time'                  => $args['time'],
					'ip_address'            => $args['ip_address'],
					'route'                 => $args['route'],
					'method'                => $args['method'],
					'request_body'          => json_encode( $args['request']['body'] ),
					'response_body'         => json_encode( $args['response']['body'] ),
					'milliseconds'          => $args['milliseconds'],
					),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					)
			);

			// insert logmeta
			$log_id = 0;
			if ( 1 === $inserted ) {
				$log_id = $wpdb->insert_id;

				$this->insert_meta_values( $log_id, $args );

			}



			return $log_id;

		}


		private function insert_meta_values( $log_id, $args ) {

			// process request
			if ( ! empty( $args['request'] ) ) {
				$request        = $args['request'];
				$request_type   = self::META_REQUEST;

				$inserts = array(
					'headers'       => self::META_PARAM_HEADER,
					'query_params'  => self::META_PARAM_QUERY,
					'body_params'   => self::META_PARAM_QUERY,
					);

				foreach ( $inserts as $key => $param_type ) {
					if ( is_array( $request[ $key ] ) ) {
						$this->insert_meta_array( $log_id, $request_type, $param_type, $request[ $key ] );
					}
				}

			}


			// process response
			if ( ! empty( $args['response'] ) ) {
				$response        = $args['response'];
				$response_type   = self::META_RESPONSE;


				$inserts = array(
					'headers'       => self::META_PARAM_HEADER,
					);

				foreach ( $inserts as $key => $param_type ) {
					if ( is_array( $response[ $key ] ) ) {
						$this->insert_meta_array( $log_id, $response_type, $param_type, $response[ $key ] );
					}
				}

			}

		}


		private function insert_meta_array( $log_id, $request_type, $param_type, $array ) {
			foreach ( $array as $key => $value ) {
				$this->insert_meta( $log_id, $request_type, $param_type, $key, $value );
			}
		}


		private function insert_meta( $log_id, $request_type, $param_type, $key, $value ) {

			global $wpdb;

			// turn one-element arrays into a more simple value
			if ( is_array( $value ) && 1 === count( $value ) ) {
				$value = $value[0];
			}

			$inserted = $wpdb->insert( $this->table_name_logmeta(),
				array(
					'log_id'                 => $log_id,
					'meta_request_response'  => $request_type,
					'meta_type'              => $param_type,
					'meta_key'               => $key,
					'meta_value'             => maybe_serialize( $value ),
					),
				array(
					'%d',
					'%s',
					'%s',
					'%s',
					'%s',
					)
			);

			if ( ! empty( $inserted ) ) {
				return $wpdb->inserted_id;
			} else {
				return false;
			}

		}


		public function search( $args = array() ) {

			global $wpdb;

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
					'records_per_page'   => 50,
					'id'                 => 0,
					'fields'             => 'basic',
				)
			);


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


			$data = new stdClass();

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
					$fields = 'id, time, ip_address, method, route, milliseconds, char_length(response_body) as response_body_length ';
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
				$data->paged_records = $this->add_meta_to_records( $data->paged_records );
			}



			return $data;

		}


		public function purge( $args ) {

			global $wpdb;

			$args = wp_parse_args( $args, array(
				'older_than_seconds' => DAY_IN_SECONDS * 30,
			));

			$table_name = self::table_name();
			$date = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - absint( $args['older_than_seconds'] ) );

			$data = new stdClass();
			$data->older_than_date = $date;
			$data->query = $wpdb->prepare( "delete from $table_name where time < %s", $date );
			$data->records_affected = $wpdb->query( $data->query );

			return $data;

		}


		public function distinct_routes() {
			global $wpdb;
			$table_name = self::table_name();
			return $wpdb->get_col( "select distinct route from $table_name order by route" );
		}


		private function get_all_meta( array $ids ) {
			global $wpdb;
			$table_name_logmeta = $this->table_name_logmeta();

			$meta = $wpdb->get_results( "select * from {$table_name_logmeta} where log_id in ( " . implode( ',', $ids ) . ' );' );

			if ( is_array( $meta ) ) {
				for ( $i=0; $i < count( $meta ); $i++) {
					$meta[ $i ]->id      = absint( $meta[ $i ]->id );
					$meta[ $i ]->log_id  = absint( $meta[ $i ]->log_id );
				}
			}

			return $meta;
		}


		private function add_meta_to_records( $records ) {

			$ids    = array_map( 'absint', wp_list_pluck( $records, 'id' ) );
			$metas  = $this->get_all_meta( $ids );

			if ( empty( $metas ) ) {
				return $records;
			}

			for ( $i_record=0; $i_record < count( $records ); $i_record++ ) {

				$record = $records[ $i_record ];

				$meta = $this->find_meta_for_log( $record->id, $metas );

				$record->request = new stdClass();
				$record->request->headers        = array();
				$record->request->query_params   = array();
				$record->request->body_params    = array();


				$record->response = new stdClass();
				$record->response->headers       = array();


				if ( ! empty( $meta ) ) {

					// map meta values to the objects above
					foreach ( $meta as $meta_record ) {

						$data = array( 'name' => $meta_record->meta_key, 'value' => maybe_unserialize( $meta_record->meta_value ) );

						switch ( $meta_record->meta_request_response ) {

							case 'request':
								switch ( $meta_record->meta_type ) {
									case 'header':
										$record->request->headers[] = $data;
										break;
									case 'query':
										$record->request->query_params[] = $data;
										break;
									case 'body':
										$record->request->body_params[] = $data;
										break;
								}

								break;

							case 'response':

								switch ( $meta_record->meta_type ) {
									case 'header':
										$record->response->headers[] = $data;
										break;
								}

								break;
						}


					}

				}

				$records[ $i_record ] = $record;

			}

			return $records;

		}


		private function find_meta_for_log( $log_id, array $metas ) {
			$matches = array();
			foreach ( $metas as $meta ) {
				if ( $log_id === $meta->log_id ) {
					$matches[] = $meta;
				}
			}
			return $matches;
		}



		private function cleanup_data( $data ) {


			if ( ! is_array( $data->paged_records ) ) {
				return $data;
			}


			for ( $i=0; $i < count( $data->paged_records ); $i++) {

				if ( ! empty( $data->paged_records[ $i ]->response_body ) ) {
					$data->paged_records[ $i ]->response_body_length = absint( strlen( $data->paged_records[ $i ]->response_body ) );
					$data->paged_records[ $i ]->response_body = json_decode( $data->paged_records[ $i ]->response_body );
				} else {
					$data->paged_records[ $i ]->response_body = '';
					$data->paged_records[ $i ]->response_body_length = absint( $data->paged_records[ $i ]->response_body_length );
				}

				$data->paged_records[ $i ]->id            = absint( $data->paged_records[ $i ]->id );
				$data->paged_records[ $i ]->milliseconds  = absint( $data->paged_records[ $i ]->milliseconds );
			}


			return $data;

		}



	} // end class

}
