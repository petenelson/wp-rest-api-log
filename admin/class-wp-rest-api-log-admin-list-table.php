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
			add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );
			add_filter( 'manage_edit-' . WP_REST_API_Log_Db::POST_TYPE . '_columns' , array( $this, 'custom_columns' ) );
			add_action( 'manage_' . WP_REST_API_Log_Db::POST_TYPE . '_posts_custom_column',  array( $this, 'custom_column' ), 10, 2 );
		}


		public function post_row_actions( $actions, $post ) {

			if ( WP_REST_API_Log_Db::POST_TYPE === $post->post_type ) {

				// turn off items
				unset( $actions['edit'] );
				unset( $actions[ 'inline hide-if-no-js' ] );

			}

			return $actions;
		}


		public function custom_columns( $columns ) {

			unset( $columns['author'] );
			$columns['method'] = 'Method';
			$columns = array(
				'cb'         => '<input type="checkbox" />',
				'date'       => __( 'Date' ),
				'method'     => __( 'Method', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'title'      => __( 'Title' ),
				'status'     => __( 'Status', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'elapsed'    => __( 'Elapsed Time', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'length'     => __( 'Response Length', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				'ip-address' => __( 'IP Address', WP_REST_API_Log_Common::TEXT_DOMAIN ),
				);


			return $columns;
		}


		public function custom_column( $column, $post_id ) {
			$entry = $this->get_entry( $post_id );

			if ( ! empty( $entry ) ) {

				switch ( $column ) {
					case 'method';
						echo esc_html( $entry->method );
						break;
					case 'status';
						echo esc_html( $entry->status );
						break;
					case 'elapsed';
						echo esc_html( number_format( $entry->milliseconds ) );
						break;
					case 'length';
						echo esc_html( number_format( strlen( $entry->response->body ) ) );
						break;
					case 'ip-address';
						echo esc_html( $entry->ip_address );
						break;
				}

			}

		}


		private function get_entry( $post_id ) {
			if ( empty( $this->_post ) || $post_id !== $this->_post_id ) {
				$this->_post = new WP_REST_API_Log_Entry( $post_id );
				$this->_post_id = $post_id;
			}
			return $this->_post;
		}


	}

}