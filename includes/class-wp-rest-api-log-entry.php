<?php

if ( ! defined( 'ABSPATH' ) ) die( 'restricted access' );

if ( ! class_exists( 'WP_REST_API_Log' ) ) {

	class WP_REST_API_Log_Entry {


		public function __construct( $post = null ) {
			if ( is_object( $post ) ) {
				$this->load( $post );
			}
		}


		private function load( $post ) {

			// TODO flush out this class

		}


	}

}

