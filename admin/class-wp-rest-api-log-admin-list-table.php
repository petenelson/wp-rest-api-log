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

			// Add Dropdowns.
			add_action( 'restrict_manage_posts', [ $this, 'add_dropdowns' ] );
			add_action( 'pre_get_posts', [ $this, 'add_tax_queries' ] );
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

		/**
		 * Adds additional dropdowns for filtering.
		 *
		 * @param string $post_type The post type.
		 */
		public function add_dropdowns( $post_type ) {
			if ( WP_REST_API_Log_Db::POST_TYPE === $post_type ) {
				foreach ( $this->get_dropdown_taxonomies() as $taxonomy ) {
					WP_REST_API_Log_Common::dropdown_terms( $taxonomy );
				}
			}
		}

		/**
		 * Gets a list of taxonomies used for admin list table dropdowns.
		 *
		 * @return array
		 */
		public function get_dropdown_taxonomies() {
			$taxonomies = [
				WP_REST_API_Log_DB::TAXONOMY_METHOD,
				WP_REST_API_Log_DB::TAXONOMY_STATUS,
				WP_REST_API_Log_DB::TAXONOMY_SOURCE,
			];

			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-taxonomy-dropdowns', $taxonomies );
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

		/**
		 * Adds taxonomy queries to the admin list table query.
		 *
		 * @param WP_Query $query The query.
		 * @return void
		 */
		public function add_tax_queries( $query ) {

			if ( is_admin() && $query->is_main_query() ) {

				if ( function_exists( 'get_current_screen' ) ) {
					$screen = get_current_screen();

					if ( 'edit-' . WP_REST_API_Log_Db::POST_TYPE !== $screen->id ) {
						return;
					}
				}

				$tax_query = [
					'relation' => 'AND',
				];

				foreach ( $this->get_dropdown_taxonomies() as $taxonomy ) {

					$get = filter_var_array(
						$_GET,
						[
							$taxonomy => WP_REST_API_Log_Common::filter_strip_all_tags(),
						]
					);

					if ( ! empty( $get[ $taxonomy ] ) ) {
						$tax_query[] = [
							'taxonomy' => $taxonomy,
							'field'    => 'slug',
							'terms'    => $get[ $taxonomy ],
						];
					}
				}

				if ( count( $tax_query ) > 1 ) {
					$query->set( 'tax_query', $tax_query );
				}
			}
		}
	}
}