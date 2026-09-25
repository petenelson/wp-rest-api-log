<?php
/**
 * Stand-in for the WP-CLI progress bar helper used by the plugin's commands.
 *
 * @package wp-rest-api-log
 */

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedNamespaceFound -- The namespace must match WP-CLI's.
namespace WP_CLI\Utils;

if ( ! function_exists( 'WP_CLI\Utils\make_progress_bar' ) ) {

	/**
	 * Creates a progress bar that counts its ticks.
	 *
	 * @param string $message  Message.
	 * @param int    $count    Number of items.
	 * @param int    $interval Refresh interval.
	 * @return \WP_REST_API_Log_Test_Progress_Bar
	 */
	function make_progress_bar( $message, $count, $interval = 100 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Signature matches WP-CLI.
		return new \WP_REST_API_Log_Test_Progress_Bar( $message );
	}
}
