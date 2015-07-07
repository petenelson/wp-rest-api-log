<?php
if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log_DB_Base' ) ) {

	class WP_REST_API_Log_DB_Base {

		protected function dbDelta( $sql ) {

			global $wpdb;
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			$charset_collate = $wpdb->get_charset_collate();

			$sql .= " $charset_collate;";

			dbDelta( $sql );

		}


	}

}