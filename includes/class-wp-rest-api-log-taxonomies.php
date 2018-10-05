<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Taxonomies' ) ) {

	class WP_REST_API_Log_Taxonomies {

		static public function plugins_loaded() {
			add_action( 'init', array( __CLASS__, 'register_custom_taxonomies' ) );
		}

		/**
		 * Registers custom taxonomies used by the REST API Log.
		 *
		 * @return void
		 */
		static public function register_custom_taxonomies() {

			$taxonomies = array(
				WP_REST_API_Log_DB::TAXONOMY_METHOD => array(
					'name'                => __( 'Method', 'wp-rest-api-log' ),
					'singular_name'       => __( 'Methods', 'wp-rest-api-log' ),
				),

				WP_REST_API_Log_DB::TAXONOMY_STATUS => array(
					'name'                => __( 'Status', 'wp-rest-api-log' ),
					'singular_name'       => __( 'Statuses', 'wp-rest-api-log' ),
				),

				WP_REST_API_Log_DB::TAXONOMY_SOURCE => array(
					'name'                => __( 'Log Source', 'wp-rest-api-log' ),
					'singular_name'       => __( 'Log Sources', 'wp-rest-api-log' ),
				),
			);

			$taxonomies = apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-custom-taxonomies', $taxonomies );

			foreach ( $taxonomies as $taxonomy => $labels ) {

				$args = array(
					'labels'            => $labels,
					'public'            => false,
					'show_in_nav_menus' => false,
					'show_admin_column' => false,
					'hierarchical'      => false,
					'show_tagcloud'     => false,
					'show_ui'           => false,
					'query_var'         => false,
					'rewrite'           => false,
					'capabilities'      => array(),
				);

				$args = apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-register-taxonomy-args', $args, $taxonomy );

				register_taxonomy( $taxonomy, array( WP_REST_API_Log_DB::POST_TYPE ), $args );
			}
		}
	}
}
