<?php
/**
 * Download and copy-to-clipboard links for a single log entry property.
 *
 * Included by WP_REST_API_Log_Admin::entry_property_links(), which supplies
 * the $args array, so the variables below are method-scoped rather than global.
 *
 * @package wp-rest-api-log
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included inside a method; these variables are method-scoped.

$url           = $args['download_urls'][ $args['rr'] ][ $args['property'] ];
$data_property = $args['rr'] . '-' . $args['property'];
?>
<p>
	<a href="<?php echo esc_url( $url ); ?>"><?php esc_attr_e( 'Download', 'wp-rest-api-log' ); ?></a> | 
	<a href="#copy-clipboard" data-clipboard-target="#wp-rest-api-log-entry .<?php echo esc_attr( $data_property ); ?> code" class="wp-rest-api-log-entry-copy-property"><?php esc_attr_e( 'Copy', 'wp-rest-api-log' ); ?></a>
</p>
