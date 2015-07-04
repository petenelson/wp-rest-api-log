<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_DB' ) ) {

	class WP_REST_API_Log_DB {

		static $dbversion    = '12';


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
				  id mediumint(9) NOT NULL AUTO_INCREMENT,
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
				'request_headers'       => json_encode( array() ),
				'request_query_params'  => json_encode( array() ),
				'request_body_params'   => json_encode( array() ),
				'request_body'          => '',
				'response_headers'      => json_encode( array() ),
				'response_body'         => '',
				)
			);

			$id = $wpdb->insert( self::table_name(),
				array(
					'time'                  => $args['time'],
					'ip_address'            => $args['ip_address'],
					'route'                 => $args['route'],
					'method'                => $args['method'],
					'request_headers'       => $args['request_headers'],
					'request_query_params'  => $args['request_query_params'],
					'request_body_params'   => $args['request_body_params'],
					'request_body'          => $args['request_body'],
					'response_headers'      => $args['response_headers'],
					'response_body'         => $args['response_body'],
					)
			);

			return $id;

		}


		public function search( $args ) {

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
				)
			);


			$table_name = self::table_name();
			$from = "from $table_name where 1 ";
			$where = '';

			if ( ! empty ( $args['id'] ) ) {
				$where .= $wpdb->prepare( ' and id = %d ', $args['id'] );
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

			$data->args = $args;
			$data->query = 'select * ' . $from . $where . $order_by . $limit;
			$data->paged_records = $wpdb->get_results( $data->query );

			$data = $this->cleanup_data( $data );

			return $data;

		}


		private function cleanup_data( $data ) {

			if ( ! is_array( $data->paged_records ) ) {
				return $data;
			}



			return $data;

		}



	} // end class

}
