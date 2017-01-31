<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Admin_List_Table' ) ) {

	class WP_REST_API_Log_Admin_List_Table {

		private $_post      = null;
		private $_post_id   = 0;

		public function plugins_loaded() {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}


		public function admin_init() {
			$post_type = WP_REST_API_Log_Db::POST_TYPE;

			add_filter( 'post_row_actions',                          array( $this, 'post_row_actions' ), 10, 2 );
			add_filter( "manage_edit-{$post_type}_columns" ,         array( $this, 'custom_columns' ) );
			add_action( "manage_{$post_type}_posts_custom_column",   array( $this, 'custom_column' ), 10, 2 );

			// remove edit and add new
			add_filter( "bulk_actions-edit-{$post_type}",            array( $this, 'remove_edit_bulk_action' ) );

			// add dropdowns
			add_action( 'restrict_manage_posts',                     array( $this, 'add_method_dropdown' ) );
			add_action( 'restrict_manage_posts',                     array( $this, 'add_status_dropdown' ) );
			add_action( 'restrict_manage_posts',                     array( $this, 'add_source_dropdown' ) );
		}


		public function post_row_actions( $actions, $post ) {

			if ( WP_REST_API_Log_Db::POST_TYPE === $post->post_type ) {

				// turn off items
				unset( $actions['edit'] );
				unset( $actions['inline hide-if-no-js'] );

				wp_enqueue_script( 'wp-rest-api-log-admin' );

			}

			return $actions;
		}


		public function custom_columns( $columns ) {

			unset( $columns['author'] );
			$columns['method'] = 'Method';
			$columns = array(
				'cb'         => '<input type="checkbox" />',
				'date'       => __( 'Date' ),
				'method'     => __( 'Method', 'wp-rest-api-log' ),
				'title'      => __( 'Title' ),
				'status'     => __( 'Status', 'wp-rest-api-log' ),
				'elapsed'    => __( 'Elapsed Time', 'wp-rest-api-log' ),
				'length'     => __( 'Response Length', 'wp-rest-api-log' ),
				'ip-address' => __( 'IP Address', 'wp-rest-api-log' ),
				'user'       => __( 'User', 'wp-rest-api-log' ),
				);


			return $columns;
		}


		public function custom_column( $column, $post_id ) {
			$entry = $this->get_entry( $post_id );

			if ( ! empty( $entry ) ) {

				switch ( $column ) {
					case 'method':
						echo esc_html( $entry->method );
						break;

					case 'status':
						echo esc_html( $entry->status );
						break;

					case 'elapsed':
						echo esc_html( number_format( $entry->milliseconds ) . 'ms' );
						break;

					case 'length':
						echo esc_html( number_format( strlen( $entry->response->body ) ) );
						break;

					case 'user':
						echo esc_html( $entry->user );
						break;

					case 'ip-address':
						$ip_address_display = apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-setting-get',
							'general',
							'ip-address-display',
							'ip_address'
							);

						if ( 'http_x_forwarded_for' === $ip_address_display ) {
							echo esc_html( $entry->http_x_forwarded_for );
						} else {
							echo esc_html( $entry->ip_address );
						}
						break;
				}

			}

		}

		public function add_method_dropdown( $post_type ) {
			if ( WP_REST_API_Log_Db::POST_TYPE === $post_type ) {

				$method = WP_REST_API_Log_DB::TAXONOMY_METHOD;

				WP_REST_API_Log_Common::taxonomy_dropdown(
					__( 'Method', 'wp-rest-api-log' ),
					__( 'All Methods', 'wp-rest-api-log' ),
					$method,
					filter_input( INPUT_GET, $method, FILTER_SANITIZE_STRING )
					);

			}
		}

		public function add_status_dropdown( $post_type ) {
			if ( WP_REST_API_Log_Db::POST_TYPE === $post_type ) {

				$status = WP_REST_API_Log_DB::TAXONOMY_STATUS;

				WP_REST_API_Log_Common::taxonomy_dropdown(
					__( 'Status', 'wp-rest-api-log' ),
					__( 'All Statuses', 'wp-rest-api-log' ),
					$status,
					filter_input( INPUT_GET, $status, FILTER_SANITIZE_STRING )
					);

			}
		}

		public function add_source_dropdown( $post_type ) {
			if ( WP_REST_API_Log_Db::POST_TYPE === $post_type ) {

				$source = WP_REST_API_Log_DB::TAXONOMY_SOURCE;

				WP_REST_API_Log_Common::taxonomy_dropdown(
					__( 'Source', 'wp-rest-api-log' ),
					__( 'All Sources', 'wp-rest-api-log' ),
					$source,
					filter_input( INPUT_GET, $source, FILTER_SANITIZE_STRING )
					);

			}
		}

		private function get_entry( $post_id ) {
			if ( empty( $this->_post ) || $post_id !== $this->_post_id ) {
				$this->_post = new WP_REST_API_Log_Entry( $post_id );
				$this->_post_id = $post_id;
			}
			return $this->_post;
		}

		/**
		 * Removes the Edit option from bulk actions
		 *
		 * @param  array $actions
		 * @return array
		 */
		public function remove_edit_bulk_action( $actions ) {
			unset( $actions['edit'] );
			return $actions;
		}

	}

}