(function( $ ) {
	"use strict";

	var WP_REST_API_Log = {

		entry_element: null,

		init: function() {

			this.entry_element = $( document.getElementById( 'wp-rest-api-log-entry' ) );

			this.highlightBlocks();
			this.adjustPostsListsTable();

			if ( 'undefined' !== typeof WP_REST_API_Log_Migrate_Data ) {
				this.migrateLegacyDB();
			}

		},

		highlightBlocks: function() {

			if ( this.entry_element.length > 0 ) {
				this.entry_element.find( 'code' ).each( function( i, block ) {
					hljs.highlightBlock( block );
				} );
			}

		},

		adjustPostsListsTable: function() {
			if ( $( 'body' ).hasClass( 'post-type-wp-rest-api-log') ) {
				$( '.wp-list-table' ).removeClass( 'fixed' );
			}
		},

		migrateLegacyDB: function() {
			$.get( ajaxurl, { "action":WP_REST_API_Log_Migrate_Data.action, "nonce":WP_REST_API_Log_Migrate_Data.nonce } );
		}

	};

	$( document ).ready( function() {
		WP_REST_API_Log.init();
	} );

})( jQuery );
