<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_DB_Entries' ) ) {

	class WP_REST_API_Log_DB_Entries extends WP_REST_API_Log_DB_Base {

		const DB_VERSION          = '35';


		public function plugins_loaded() {
			add_action( 'admin_init', 'WP_REST_API_Log_DB_Entries::create_or_update_tables' );
			add_action( WP_REST_API_Log_Common::$plugin_name . '-insert', array( $this, 'insert' ) );
		}


		static private function plugin_name() {
			return WP_REST_API_Log_Common::$plugin_name . '-entries';
		}


		static public function create_or_update_tables() {

			$key = self::plugin_name() . '-dbversion';
			if ( self::DB_VERSION !== get_option( $key ) ) {

				$table_name = self::table_name();

				$sql = "CREATE TABLE $table_name (
				  id bigint NOT NULL AUTO_INCREMENT,
				  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				  ip_address varchar(30) NULL,
				  method varchar(20) DEFAULT '' NOT NULL,
				  route varchar(100) DEFAULT '' NOT NULL,
				  status smallint NULL,
				  request_body text NULL,
				  response_body longtext NULL,
				  milliseconds smallint NOT NULL,
				  PRIMARY KEY id (id),
				  KEY time (time),
				  KEY route (route)
				)";

				parent::dbdelta( $sql );
				update_option( $key, self::DB_VERSION );

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

			$inserted = $wpdb->insert( self::table_name(),
				array(
					'time'                  => $args['time'],
					'ip_address'            => $args['ip_address'],
					'route'                 => $args['route'],
					'method'                => $args['method'],
					'request_body'          => json_encode( $args['request']['body'] ),
					'response_body'         => json_encode( $args['response']['body'] ),
					'milliseconds'          => $args['milliseconds'],
					'status'                => $args['status'],
					),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					'%d',
					)
			);

			// insert logmeta
			$log_id = 0;
			if ( 1 === $inserted ) {
				$log_id = $wpdb->insert_id;

				do_action( self::plugin_name() . '-inserted', $log_id );

				$this->insert_meta_values( $log_id, $args );

			}


			return $log_id;

		}


		private function insert_meta_values( $log_id, $args ) {

			$db_meta = new WP_REST_API_Log_DB_Meta();
			$db_meta->insert_meta_values( $log_id, $args );

		}


		public function search( $args = array() ) {

			global $wpdb;
			$db_meta = new WP_REST_API_Log_DB_Meta();

			$data = new stdClass();


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
					'params'             => array(),
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


		public function distinct_routes() {
			global $wpdb;
			$table_name = self::table_name();
			return $wpdb->get_col( "select distinct route from $table_name order by route" );
		}


		private function cleanup_data( $data ) {


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
