<?php
$url = $args['download_urls'][ $args['rr'] ][ $args['property'] ];
$data_property = $args['rr'] . '-' . $args['property'];
?>
<p>
	<a href="<?php echo esc_url( $url ); ?>"><?php esc_attr_e( 'Download', 'wp-rest-api-log' ); ?></a> | 
	<a href="#copy-clipboard" data-clipboard-target="#wp-rest-api-log-entry .<?php echo esc_attr( $data_property ); ?> code" class="wp-rest-api-log-entry-copy-property"><?php esc_attr_e( 'Copy', 'wp-rest-api-log' ); ?></a>
</p>
