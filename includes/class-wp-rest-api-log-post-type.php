<?php
/**
 * Registers the custom post type used to store log entries.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Post_Type' ) ) {

	/**
	 * Registers and configures the log entry post type.
	 */
	class WP_REST_API_Log_Post_Type {

		/**
		 * Hooks the post type registration into WordPress.
		 *
		 * @return void
		 */
		public static function plugins_loaded() {
			add_action( 'init', array( __CLASS__, 'register_custom_post_types' ) );
		}

		/**
		 * Registers the log entry post type.
		 *
		 * @return void
		 */
		public static function register_custom_post_types() {

			$args = self::get_post_type_args();

			register_post_type( WP_REST_API_Log_DB::POST_TYPE, $args );
		}


		/**
		 * Returns the labels used by the log entry post type.
		 *
		 * @return array Post type labels, filterable via
		 *               "wp-rest-api-log-post-type-labels".
		 */
		public static function get_post_type_labels() {

			$labels = array(
				'name'               => esc_html__( 'REST API Log Entries', 'wp-rest-api-log' ),
				'singular_name'      => esc_html__( 'REST API Log Entry', 'wp-rest-api-log' ),
				'add_new'            => esc_html__( 'Add New REST API Log Entries', 'wp-rest-api-log' ),
				'add_new_item'       => esc_html__( 'Add New REST API Log Entry', 'wp-rest-api-log' ),
				'new_item'           => esc_html__( 'New REST API Log Entry', 'wp-rest-api-log' ),
				'edit_item'          => esc_html__( 'Edit REST API Log Entry', 'wp-rest-api-log' ),
				'view_item'          => esc_html__( 'View REST API Log Entry', 'wp-rest-api-log' ),
				'all_items'          => esc_html__( 'All REST API Log Entries', 'wp-rest-api-log' ),
				'search_items'       => esc_html__( 'Search Entries', 'wp-rest-api-log' ),
				'not_found'          => esc_html__( 'No REST API Log Entries found', 'wp-rest-api-log' ),
				'not_found_in_trash' => esc_html__( 'No REST API Log Entries found in Trash', 'wp-rest-api-log' ),
			);

			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-post-type-labels', $labels );
		}


		/**
		 * Returns the registration arguments for the log entry post type.
		 *
		 * @return array Post type arguments, filterable via
		 *               "wp-rest-api-log-register-post-type".
		 */
		public static function get_post_type_args() {

			$args = array(
				'labels'              => self::get_post_type_labels(),
				'show_in_rest'        => false,
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
					'read_post'    => 'read_' . WP_REST_API_Log_DB::POST_TYPE,
					'delete_post'  => 'delete_' . WP_REST_API_Log_DB::POST_TYPE,
					'delete_posts' => 'delete_' . WP_REST_API_Log_DB::POST_TYPE . 's',
					'edit_posts'   => 'edit_' . WP_REST_API_Log_DB::POST_TYPE . 's',
					'edit_post'    => 'edit_' . WP_REST_API_Log_DB::POST_TYPE,
					'create_posts' => 'create_' . WP_REST_API_Log_DB::POST_TYPE . 's',
				),
				'supports'            => array( 'title', 'author', 'excerpt' ),
			);

			return apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-register-post-type', $args );
		}
	}
}
