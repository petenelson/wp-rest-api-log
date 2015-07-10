<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_DB_Meta' ) ) {

	class WP_REST_API_Log_DB_Meta extends WP_REST_API_Log_DB_Base {

		const DB_VERSION          = '35';
		const META_REQUEST        = 'request';
		const META_RESPONSE       = 'response';
		const META_PARAM_HEADER   = 'header';
		const META_PARAM_QUERY    = 'query';
		const META_PARAM_BODY     = 'body';


		public function plugins_loaded() {
			add_action( 'admin_init', 'WP_REST_API_Log_DB_Meta::create_or_update_tables' );
		}


		static private function plugin_name() {
			return WP_REST_API_Log_Common::$plugin_name . '-meta';
		}


		static public function create_or_update_tables() {

			$key = self::plugin_name() . '-dbversion';
			if ( self::DB_VERSION !== get_option( $key ) ) {

				$table_name = self::table_name();
				$sql = "CREATE TABLE $table_name (
				  id bigint NOT NULL AUTO_INCREMENT,
				  log_id bigint NOT NULL,
				  meta_request_response varchar(10) NOT NULL,
				  meta_type varchar(10) NOT NULL,
				  meta_key varchar(255) NULL,
				  meta_value longtext NULL,
				  PRIMARY KEY id (id),
				  KEY log_id (log_id),
				  KEY req_type_key (meta_request_response,meta_type,meta_key)
				)";

				parent::dbDelta( $sql );
				update_option( $key, self::DB_VERSION );

			}

		}


		static public function table_name() {
			global $wpdb;
			return $wpdb->prefix . 'wp_rest_api_logmeta';
		}


		public function insert_meta_values( $log_id, $args ) {

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

			$inserted = $wpdb->insert( $this->table_name(),
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

			$meta_id = 0;

			if ( 1 === $inserted ) {
				do_action( self::plugin_name() . '-inserted', $meta_id );
				$meta_id = $wpdb->inserted_id;
			}

			return $meta_id;

		}


		public function get( $id ) {
			global $wpdb;
			$table_name = $this->table_name();

			$meta = $wpdb->get_results( $wpdb->prepare( "select * from {$table_name} where id = %d", $id ) );
			$meta = $this->cleanup_data( $meta );

			return $meta;
		}


		public function get_all_meta( array $log_ids ) {
			global $wpdb;
			$table_name = $this->table_name();

			$meta = $wpdb->get_results( "select * from {$table_name} where log_id in ( " . implode( ',', $log_ids ) . ' );' );
			$meta = $this->cleanup_data( $meta );

			return $meta;
		}


		private function cleanup_data( $metas ) {

			if ( is_array( $metas ) ) {
				foreach ( $metas as &$meta ) {
					$meta->id      = absint( $meta->id );
					$meta->log_id  = absint( $meta->log_id );
				}
			}

			return $data;
		}


		public function add_meta_to_records( $records ) {

			$ids    = array_map( 'absint', wp_list_pluck( $records, 'id' ) );
			$metas  = $this->get_all_meta( $ids );


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


		private function find_meta_for_log( $log_id, $metas ) {
			$matches = array();
			if ( ! empty( $metas ) ) {
				foreach ( $metas as $meta ) {
					if ( $log_id === $meta->log_id ) {
						$matches[] = $meta;
					}
				}
			}
			return $matches;
		}


		public function build_logmeta_id_query( $params ) {

			global $wpdb;
			$table_name = $this->table_name();

			$sql = "select log_id from {$table_name} where meta_request_response = 'request' and meta_type in ( 'query', 'body' ) and ( ";

			$ors = array();
			for ( $i=0; $i < count( $params ); $i++) {

				$ors[] = $wpdb->prepare( "( meta_key = %s and meta_value = %s )",
					$params[ $i ]['name'],
					$params[ $i ]['value']
				);

			}

			$sql .= implode( ' or ' , $ors ) . ' )';

			return $sql;

		}



	} // end class

}
