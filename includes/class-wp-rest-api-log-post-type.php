<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_Post_Type' ) ) {

	class WP_REST_API_Log_Post_Type {

		static public function plugins_loaded() {
			add_action( 'init', array( __CLASS__, 'register_custom_post_types' ) );
		}

		static public function register_custom_post_types() {

			$args = self::get_post_type_args();

			register_post_type( WP_REST_API_Log_DB::POST_TYPE, $args );
		}


		static public function get_post_type_labels() {

			$labels = array(
				'name'               => esc_html__( 'REST API Log Entries', 'ms-research' ),
				'singular_name'      => esc_html__( 'REST API Log Entry', 'ms-research' ),
				'add_new'            => esc_html__( 'Add New REST API Log Entries', 'ms-research' ),
				'add_new_item'       => esc_html__( 'Add New REST API Log Entry', 'ms-research' ),
				'new_item'           => esc_html__( 'New REST API Log Entry', 'ms-research' ),
				'edit_item'          => esc_html__( 'Edit Publication Page', 'ms-research' ),
				'view_item'          => esc_html__( 'View REST API Log Entry', 'ms-research' ),
				'all_items'          => esc_html__( 'All REST API Log Entries', 'ms-research' ),
				'search_items'       => esc_html__( 'Search Entries', 'ms-research' ),
				'not_found'          => esc_html__( 'No REST API Log Entries found', 'ms-research' ),
				'not_found_in_trash' => esc_html__( 'No REST API Log Entries found in Trash', 'ms-research' ),
			);

			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-post-type-labels', $labels );
		}


		static public function get_post_type_args() {

			$args = array(
				'labels'              => self::get_post_type_labels(),
				'show_in_rest'        => true,
				'rest_base'           => WP_REST_API_Log_DB::POST_TYPE, // allows the CPT to show up in the native API
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'tools.php',
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'query_var'           => false,
				'can_export'          => true,
				'rewrite'             => false,
				'map_meta_cap'        => false,
				'capabilities'        => array(
					'read_post'     => 'read_' . WP_REST_API_Log_DB::POST_TYPE,
					'delete_post'   => 'delete_' . WP_REST_API_Log_DB::POST_TYPE,
					'delete_posts'  => 'delete_' . WP_REST_API_Log_DB::POST_TYPE . 's',
					'edit_posts'    => 'edit_' . WP_REST_API_Log_DB::POST_TYPE . 's',
					'edit_post'     => 'edit_' . WP_REST_API_Log_DB::POST_TYPE,
					'create_posts'  => 'create_' . WP_REST_API_Log_DB::POST_TYPE . 's',
					),
				'supports'            => array( 'title', 'author', 'excerpt' ),
			);

			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-register-post-type', $args );
		}
	}
}
