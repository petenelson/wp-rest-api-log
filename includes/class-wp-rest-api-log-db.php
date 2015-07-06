<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_DB' ) ) {

	class WP_REST_API_Log_DB {

		static $dbversion    = '15';


		public function plugins_loaded() {
			add_action( 'admin_init', 'WP_REST_API_Log_DB::create_or_update_tables' );
			add_action( WP_REST_API_Log_Common::$plugin_name . '-insert', array( $this, 'insert' ) );
		}


		static public function create_or_update_tables() {

			if ( self::$dbversion !== get_option( WP_REST_API_Log_Common::$plugin_name . '-dbversion' ) ) {

				global $wpdb;

				$charset_collate = $wpdb->get_charset_collate();
				$table_name = self::table_name();

				$sql = "CREATE TABLE $table_name (
				  id bigint NOT NULL AUTO_INCREMENT,
				  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				  ip_address varchar(30) NULL,
				  method varchar(20) DEFAULT '' NOT NULL,
				  route varchar(100) DEFAULT '' NOT NULL,
				  request_headers text NULL,
				  request_query_params text NULL,
				  request_body_params text NULL,
				  request_body text NULL,
				  response_headers text NULL,
				  response_body text NULL,
				  milliseconds smallint NOT NULL,
				  PRIMARY KEY id (id),
				  KEY ix_time (time),
				  KEY ix_route (route)
				) $charset_collate;";

				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );

				update_option( WP_REST_API_Log_Common::$plugin_name . '-dbversion', self::$dbversion );

			}

		}


		static public function table_name() {
			global $wpdb;
			return $wpdb->prefix . 'wp_rest_api_log';
		}


		public function insert( $args ) {

			global $wpdb;

			$args = wp_parse_args( $args, array(
				'time'                  => current_time( 'mysql' ),
				'ip_address'            => $_SERVER['REMOTE_ADDR'],
				'route'                 => '',
				'method'                => $_SERVER['REQUEST_METHOD'],
				'querystring'           => $_SERVER['QUERY_STRING'],
				'request_headers'       => array(),
				'request_query_params'  => array(),
				'request_body_params'   => array(),
				'request_body'          => '',
				'response_headers'      => array(),
				'response_body'         => '',
				'milliseconds'          => 0,
				)
			);

			// verify arrays
			foreach ( array( 'request_headers', 'request_query_params', 'request_body_params', 'response_headers' )  as $field ) {
				if ( ! is_array( $args[ $field ] ) ) {
					$args[ $field ] = array( $args[ $field ] );
				}
			}

			if ( empty( $args['milliseconds'] ) ) {
				global $wp_rest_api_log_start;
				$now = WP_REST_API_Log_Common::current_milliseconds();
				$args['milliseconds'] = absint( $now -  $wp_rest_api_log_start );
			}


			$id = $wpdb->insert( self::table_name(),
				array(
					'time'                  => $args['time'],
					'ip_address'            => $args['ip_address'],
					'route'                 => $args['route'],
					'method'                => $args['method'],
					'request_headers'       => json_encode( $args['request_headers'] ),
					'request_query_params'  => json_encode( $args['request_query_params'] ),
					'request_body_params'   => json_encode( $args['request_body_params'] ),
					'request_body'          => json_encode( $args['request_body'] ),
					'response_headers'      => json_encode( $args['response_headers'] ),
					'response_body'         => json_encode( $args['response_body'] ),
					'milliseconds'          => $args['milliseconds'],
					),
				array(
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%d',
					)
			);

			return $id;

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
					'page'               => 1,
					'records_per_page'   => 50,
					'id'                 => 0,
					'fields'             => 'basic',
				)
			);


			$table_name = self::table_name();
			$from = "from $table_name where 1 ";
			$where = '';

			if ( ! empty ( $args['id'] ) ) {
				$where .= $wpdb->prepare( ' and id = %d', $args['id'] );
			}

			if ( ! empty ( $args['from'] ) && empty ( $args['id'] ) ) {
				$where .= $wpdb->prepare( " and time >= '%s'", $args['from'] );
			}

			if ( ! empty ( $args['to'] ) && empty ( $args['id'] ) ) {
				$where .= $wpdb->prepare( " and time <= '%s'", $args['to'] );
			}

			if ( ! empty ( $args['before_id'] ) ) {
				$where .= $wpdb->prepare( ' and id < %d ', $args['before_id'] );
			}

			if ( ! empty ( $args['after_id'] ) ) {
				$where .= $wpdb->prepare( ' and id > %d ', $args['after_id'] );
			}

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
			$data->total_records = absint( $wpdb->get_var( 'select count(*) ' . $from . $where ) );

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
					$fields = 'id, time, ip_address, method, route, milliseconds, request_query_params, request_body_params, char_length(response_body) as response_body_length ';
					break;
				default:
					$fields = '*';
					break;
			}

			$data->args = $args;
			$data->query = 'select ' . $fields . ' ' . $from . $where . $order_by . $limit;
			$data->paged_records = $wpdb->get_results( $data->query );

			$data = $this->cleanup_data( $data );

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


		private function cleanup_data( $data ) {


			if ( ! is_array( $data->paged_records ) ) {
				return $data;
			}

			for ( $i=0; $i < count( $data->paged_records ); $i++) {
				if ( ! empty( $data->paged_records[ $i ]->request_headers ) ) {
					$data->paged_records[ $i ]->request_headers = json_decode( $data->paged_records[ $i ]->request_headers );
				}
				if ( ! empty( $data->paged_records[ $i ]->request_body_params ) ) {
					$data->paged_records[ $i ]->request_body_params = json_decode( $data->paged_records[ $i ]->request_body_params );
				}
				if ( ! empty( $data->paged_records[ $i ]->response_headers ) ) {
					$data->paged_records[ $i ]->response_headers = json_decode( $data->paged_records[ $i ]->response_headers );
				}
				if ( ! empty( $data->paged_records[ $i ]->response_body ) ) {
					$data->paged_records[ $i ]->response_body_length = absint( strlen( $data->paged_records[ $i ]->response_body ) );
					$data->paged_records[ $i ]->response_body = json_decode( $data->paged_records[ $i ]->response_body );
				} else {
					$data->paged_records[ $i ]->response_body = '';
					$data->paged_records[ $i ]->response_body_length = absint( $data->paged_records[ $i ]->response_body_length );
				}

				$data->paged_records[ $i ]->milliseconds = absint( $data->paged_records[ $i ]->milliseconds );
			}


			return $data;

		}



	} // end class

}
