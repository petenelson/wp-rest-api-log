<?php
/**
 * Admin screens, assets and menu entries for the plugin.
 *
 * @package wp-rest-api-log
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_Admin' ) ) {

	/**
	 * Registers the plugin's admin pages, assets and log entry viewer.
	 */
	class WP_REST_API_Log_Admin {

		/**
		 * Hook suffix for the hidden log entry view page, set in admin_menu().
		 *
		 * @var string|false
		 */
		public static $view_entry_hook = false;

		/**
		 * Wire up WordPress hooks and filters.
		 *
		 * @return void
		 */
		public static function plugins_loaded() {
			add_filter( 'post_type_link', array( __CLASS__, 'entry_permalink' ), 10, 2 );
			add_filter( 'get_edit_post_link', array( __CLASS__, 'entry_permalink' ), 10, 2 );
			add_action( 'admin_init', array( __CLASS__, 'register_scripts' ), 10 );
			add_action( 'admin_init', array( __CLASS__, 'localize_script_data' ), 11 );
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
			add_filter( 'wp_link_query_args', array( __CLASS__, 'wp_link_query_args' ) );
			add_filter( 'admin_title', array( __CLASS__, 'admin_title' ) );
			add_filter( 'user_has_cap', array( __CLASS__, 'add_admin_caps' ), 10, 3 );
			add_filter( 'plugin_action_links_' . WP_REST_API_LOG_BASENAME, array( __CLASS__, 'plugin_action_links' ), 10, 4 );
			add_action( 'current_screen', array( __CLASS__, 'maybe_enqueue_scripts' ) );

			// Custom actions for out plugin.
			add_action( 'wp-rest-api-log-entry-property-links', array( __CLASS__, 'display_entry_property_links' ), 10, 3 );
		}

		/**
		 * Adds the REST API Log menu to the tools page.
		 *
		 * @return void
		 */
		public static function admin_menu() {

			self::$view_entry_hook = add_submenu_page(
				'',
				__( 'REST API Log Entries', 'wp-rest-api-log' ),
				'',
				'read_' . WP_REST_API_Log_DB::POST_TYPE,
				WP_REST_API_Log_Common::PLUGIN_NAME . '-view-entry',
				array( __CLASS__, 'display_log_entry' )
			);

			// The view entry page has no parent menu, so WordPress core never resolves
			// a page title for it. Set one early to avoid a null value being passed to
			// strip_tags() in wp-admin/admin-header.php.
			if ( ! empty( self::$view_entry_hook ) ) {
				add_action( 'load-' . self::$view_entry_hook, array( __CLASS__, 'set_view_entry_title' ) );
			}

			global $submenu;
			if ( ! empty( $submenu['tools.php'] ) ) {
				foreach ( $submenu['tools.php'] as &$item ) {
					if ( 'edit.php?post_type=wp-rest-api-log' === $item[2] ) {
						$item[0] = __( 'REST API Log', 'wp-rest-api-log' );
						if ( ! empty( $item[3] ) ) {
							$item[3] = $item[0];
						}
					}
				}
			}
		}


		/**
		 * Registers admin scripts and styles.
		 *
		 * @return void
		 */
		public static function register_scripts() {

			$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			// See https://highlightjs.org/.
			$highlight_version = apply_filters( 'wp-rest-api-log-admin-highlight-js-version', '11.7.0' );
			$highlight_style   = apply_filters( 'wp-rest-api-log-admin-highlight-js-style', 'default' );

			// See https://github.com/zenorocha/clipboard.js.
			$clipboard_version = apply_filters( 'wp-rest-api-log-admin-clipboard-js-version', '2.0.11' );

			// These are versioned CDN URLs, so no ?ver= query argument is added.
			// The $in_footer argument is left at its default to preserve the
			// existing load order.
			// phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion, WordPress.WP.EnqueuedResourceParameters.NotInFooter
			wp_register_script( 'wp-rest-api-log-admin-highlight-js', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/' . $highlight_version . '/highlight.min.js' );
			wp_register_style( 'wp-rest-api-log-admin-highlight-js', 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/' . $highlight_version . '/styles/' . $highlight_style . '.min.css' );
			wp_register_script( 'wp-rest-api-log-admin-clipboard-js', 'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/' . $clipboard_version . '/clipboard.min.js' );

			wp_register_script( 'wp-rest-api-log-admin', WP_REST_API_LOG_URL . 'dist/js/admin.js', 'jquery', WP_REST_API_Log_Common::VERSION );
			// phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion, WordPress.WP.EnqueuedResourceParameters.NotInFooter
			wp_register_style( 'wp-rest-api-log-admin', WP_REST_API_LOG_URL . 'dist/css/admin.css', '', WP_REST_API_Log_Common::VERSION );
		}

		/**
		 * Localizes script data for admin scripts.
		 *
		 * @return void
		 */
		public static function localize_script_data() {

			$data = array(
				'nonce'     => wp_create_nonce( 'wp_rest' ),
				'endpoints' => array(
					'purge_entries' => rest_url( WP_REST_API_Log_Common::PLUGIN_NAME . '/batch-purge-all' ),
				),
			);

			// Ensure admin URLs in SSL get pointed to SSL on the frontend.
			if ( is_ssl() ) {
				foreach ( $data['endpoints'] as &$endpoint ) {
					$endpoint = set_url_scheme( $endpoint, 'https' );
				}
			}

			wp_localize_script( 'wp-rest-api-log-admin', 'WP_REST_API_Log_Admin_Data', $data );
		}

		/**
		 * Sets the global $title for the log entry view page before WordPress
		 * core computes the admin page title.
		 *
		 * @return void
		 */
		public static function set_view_entry_title() {
			global $title;
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Deliberately sets core's $title for this admin screen.
			$title = __( 'REST API Log Entry', 'wp-rest-api-log' );
		}

		/**
		 * Displays the log entry template
		 *
		 * @return void
		 */
		public static function display_log_entry() {

			include_once apply_filters( 'wp-rest-api-log-admin-view-entry-template', WP_REST_API_LOG_PATH . 'admin/partials/wp-rest-api-log-view-entry.php' );

			self::enqueue_scripts();
		}

		/**
		 * Creates a permalink for a log enty.
		 *
		 * @param  string      $permalink Default permalink.
		 * @param  int|WP_Post $post      Post ID or object.
		 * @return string
		 */
		public static function entry_permalink( $permalink, $post ) {
			$post = get_post( $post );
			if ( WP_REST_API_Log_DB::POST_TYPE === $post->post_type ) {
				$permalink = add_query_arg(
					array(
						'page' => WP_REST_API_Log_Common::PLUGIN_NAME . '-view-entry',
						'id'   => rawurlencode( $post->ID ),
					),
					admin_url( 'tools.php' )
				);
			}
			return $permalink;
		}


		/**
		 * Returns the plugin slug used for menu and page names.
		 *
		 * @return string
		 */
		private function plugin_name() {
			return WP_REST_API_Log_Common::PLUGIN_NAME . '-admin';
		}


		/**
		 * Removes the wp-rest-api-log post type from the link query args
		 *
		 * @param  array $query Query args.
		 * @return array
		 */
		public static function wp_link_query_args( $query ) {

			if ( isset( $query['post_type'] ) && is_array( $query['post_type'] ) ) {
				for ( $i = count( $query['post_type'] ) - 1; $i >= 0; $i-- ) {
					if ( WP_REST_API_Log_DB::POST_TYPE === $query['post_type'][ $i ] ) {
						unset( $query['post_type'][ $i ] );
						break;
					}
				}
			}

			return $query;
		}


		/**
		 * Adjusts the title tag when viewing a log entry.
		 *
		 * No longer needs a special case for the log entry view screen:
		 * set_view_entry_title() already sets the correct $title for that
		 * screen, so core's own title-building logic handles it.
		 *
		 * @param  string $admin_title The page title, with extra context added.
		 * @return string
		 */
		public static function admin_title( $admin_title ) {
			return $admin_title;
		}


		/**
		 * Adds capabilities for the custom post type to the administrator role
		 *
		 * @param array $allcaps All of the user's capabilities.
		 * @param array $caps    The requested capabilities.
		 * @param array $args    Requested cap, user ID, and object ID.
		 */
		public static function add_admin_caps( $allcaps, $caps, $args ) {

			// Get the user.
			$user = get_userdata( $args[1] );

			// Give the administrator role access to the custom post type.
			if ( ! empty( $user ) && ! empty( $user->roles ) && in_array( 'administrator', $user->roles, true ) ) {

				$post_type = get_post_type_object( WP_REST_API_Log_DB::POST_TYPE );

				if ( ! empty( $post_type ) ) {
					$allcaps[ $post_type->cap->edit_posts ]        = true;
					$allcaps[ $post_type->cap->edit_others_posts ] = true;
					$allcaps[ $post_type->cap->delete_posts ]      = true;
					$allcaps[ $post_type->cap->read_post ]         = true;
					$allcaps[ $post_type->cap->edit_post ]         = true;
					$allcaps[ $post_type->cap->delete_post ]       = true;
				}
			}

			return $allcaps;
		}

		/**
		 * Adds additional links to the row on the plugins page.
		 *
		 * @param array  $actions     An array of plugin action links.
		 * @param string $plugin_file Path to the plugin file relative to the plugins directory.
		 * @param array  $plugin_data An array of plugin data.
		 * @param string $context     The plugin context. Defaults are 'All', 'Active',
		 *                            'Inactive', 'Recently Activated', 'Upgrade',
		 *                            'Must-Use', 'Drop-ins', 'Search'.
		 */
		public static function plugin_action_links( $actions, $plugin_file, $plugin_data, $context ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Signature is fixed by the filter.

			if ( is_plugin_active( $plugin_file ) && current_user_can( 'manage_options' ) ) {

				// Build the URL for the settings page.
				$url = add_query_arg(
					'page',
					rawurlencode( WP_REST_API_Log_Settings::$settings_page ),
					admin_url( 'admin.php' )
				);

				// Add the anchor tag to the list of plugin links.
				$new_actions = array(
					'settings' => sprintf(
						'<a href="%1$s">%2$s</a>',
						esc_url( $url ),
						esc_html__( 'Settings', 'default' )
					),
					'log'      => sprintf(
						'<a href="%1$s">%2$s</a>',
						esc_url( admin_url( 'edit.php?post_type=wp-rest-api-log' ) ),
						esc_html__( 'Log', 'default' )
					),
				);

				$actions = array_merge( $actions, $new_actions );
			}

			return $actions;
		}

		/**
		 * Enqueues scripts based on current screent.
		 *
		 * @return void
		 */
		public static function maybe_enqueue_scripts() {
			$screen = get_current_screen();

			$screen_ids = array(
				'settings_page_wp-rest-api-log-settings',
				'edit-wp-rest-api-log',
			);

			if ( in_array( $screen->id, $screen_ids, true ) ) {
				self::enqueue_scripts();
			}
		}

		/**
		 * Enqueues admin scripts and styles.
		 *
		 * @return void
		 */
		public static function enqueue_scripts() {
			wp_enqueue_script( 'wp-rest-api-log-admin-highlight-js' );
			wp_enqueue_style( 'wp-rest-api-log-admin-highlight-js' );

			wp_enqueue_script( 'wp-rest-api-log-admin-clipboard-js' );

			wp_enqueue_script( 'wp-rest-api-log-admin' );
			wp_enqueue_style( 'wp-rest-api-log-admin' );
		}

		/**
		 * Displays the property links (Download, Copy) for a log entry.
		 *
		 * @param  array $args Property link arguments passed to the partial.
		 * @return void
		 */
		public static function display_entry_property_links( $args ) {

			$args = wp_parse_args(
				$args,
				array(
					'rr'            => '',
					'property'      => '',
					'download_urls' => array(),
					'entry'         => null,
				)
			);

			include apply_filters( 'wp-rest-api-log-admin-view-entry-links-template', WP_REST_API_LOG_PATH . 'admin/partials/entry-property-links.php' );
		}
	}
}
