<?php
/**
 * Provides ElasticPress integration.
 *
 * @package WP_REST_API_Log\ElasticPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'restricted access' );
}

if ( ! class_exists( 'WP_REST_API_Log_ElasticPress' ) ) {

	/**
	 * Provides ElasticPress integration.
	 */
	class WP_REST_API_Log_ElasticPress {

		/**
		 * Plugins_loaded WordPress hook.
		 *
		 * @return void
		 */
		public static function plugins_loaded() {
			add_action( 'ep_add_query_log', 'WP_REST_API_Log_ElasticPress::log_query' );
			add_filter( 'ep_post_sync_kill', 'WP_REST_API_Log_ElasticPress::sync_kill', 10, 2 );
		}

		/**
		 * Filter hook to determine if we kill a sync request.
		 *
		 * @param  bool  $kill      Default value.
		 * @param  array $post_args EP post args.
		 * @return bool
		 */
		public static function sync_kill( $kill, $post_args ) {
			// Don't sync our log entries to ElasticSearch.
			if ( ! empty( $post_args ) && ! empty( $post_args['post_type'] ) && WP_REST_API_Log_DB::POST_TYPE === $post_args['post_type'] ) {
				$kill = false;
			}

			return $kill;
		}

		/**
		 * Logs an ElasticPress search and results to the database
		 *
		 * @param  object $query The ElasticPress query.
		 *
		 * @return void
		 */
		public static function log_query( $query ) {

			if ( empty( $query ) || ! is_array( $query ) ) {
				return;
			}

			// Don't log anything if logging is not enabled.
			$logging_enabled = apply_filters(
				WP_REST_API_Log_Common::PLUGIN_NAME . '-setting-is-enabled',
				true,
				'elasticpress',
				'logging-enabled'
			);

			if ( ! $logging_enabled ) {
				return;
			}

			$log_query = true;

			$route = '';
			if ( ! empty( $query['url'] ) && ! empty( $query['host'] ) ) {
				$route = $query['url'];

				$skip_urls = array(
					// Don't log the _stats/indexing request by default.
					'_stats/indexing',

					// Don't log the bulk indexing.
					'post/_bulk',

					// Don't log the plugins list.
					'_nodes/plugins',
					'_nodes?plugin=true',
				);

				foreach ( $skip_urls as $skip_url ) {
					if ( false !== strpos( $query['url'], $skip_url ) ) {
						$log_query = false;
						break;
					}
				}

				if ( $log_query ) {
					$skip_urls_regex = array(
						// Don't log requests for individual posts.
						'\/post\/\d+$',
					);

					foreach ( $skip_urls_regex as $skip_url_regex ) {
						if ( 1 === preg_match( '/' . $skip_url_regex . '/', $query['url'] ) ) {
							$log_query = false;
							break;
						}
					}
				}
			}

			// Filter for enabling/disabling logging of a specific
			// ElasticPress query.
			$log_query = apply_filters( WP_REST_API_Log_Common::PLUGIN_NAME . '-elasticpress-log-query', $log_query, $query );

			if ( ! $log_query ) {
				return;
			}

			// Set up some defaults.
			$args = array(
				'route'                 => $route,
				'method'                => '',
				'status'                => '',
				'source'                => 'ElasticPress',
				'milliseconds'          => 0,
				'request'               => array(
					'body_params'          => array(),
					'headers'              => array(),
					'body'                 => '',
				),
				'response'              => array(
					'body'                 => '',
					'headers'              => array(),
				),
			);

			// Add elapsed time.
			if ( ! empty( $query['time_start'] ) && ! empty( $query['time_finish'] ) ) {
				$args['milliseconds'] = absint( ( $query['time_finish'] * 1000 ) - ( $query['time_start'] * 1000 ) );
			}

			if ( ! empty( $query['args'] ) ) {

				// Store the JSON sent to ElasticSearch.
				if ( ! empty( $query['args']['body'] ) ) {
					$args['request']['body'] = base64_encode( $query['args']['body'] );
				}

				// Add the method.
				if ( ! empty( $query['args']['method'] ) ) {
					$args['method'] = $query['args']['method'];
				}
			}

			if ( ! empty( $query['request'] ) && is_array( $query['request'] ) ) {

				// This is actually the response headers.
				if ( ! empty( $query['request']['headers'] ) && is_array( $query['request']['headers'] ) ) {

					foreach ( $query['request']['headers'] as $header => $value ) {
						$args['response']['headers'][ $header ] = $value;
					}
				}

				// Store the HTTP response code.
				if ( ! empty( $query['request']['response'] ) && ! empty( $query['request']['response']['code'] ) ) {
					$args['status'] = $query['request']['response']['code'];
				}

				// Store the response body.
				if ( ! empty( $query['request']['body'] ) ) {
					$args['response']['body'] = json_decode( $query['request']['body'] );
				}
			}

			// Log the EP request/response.
			do_action( WP_REST_API_Log_Common::PLUGIN_NAME . '-insert', $args );
		}
	}
}
