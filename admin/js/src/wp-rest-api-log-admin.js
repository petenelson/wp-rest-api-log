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

			if ( $( 'body' ).hasClass( 'settings_page_wp-rest-api-log-settings' ) ) {
				$( '.wp-rest-api-log-purge-all' ).on( 'click', this.purgeLog );
			}

			if ( this.entry_element.length > 0 ) {
				// Hook for copying to clipboard.
				var clipboard = new ClipboardJS( '.wp-rest-api-log-entry-copy-property' );
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
		},

		purgeLog: function( e ) {
			e.preventDefault();

			// Turn off the button.
			$( '.wp-rest-api-log-purge-all' ).addClass( 'hidden' );

			// Turn off the spinner.
			$( '.wp-rest-api-log-purge-all-spinner' ).removeClass( 'hidden' ).addClass( 'is-active' );

			$.ajax( {
				url: WP_REST_API_Log_Admin_Data.endpoints.purge_entries,
				method: 'DELETE',
				beforeSend: function ( xhr ) {
					xhr.setRequestHeader( 'X-WP-Nonce', WP_REST_API_Log_Admin_Data.nonce );
				}
			} ).done( function( response ) {
				// Turn off the spinner.
				$( '.wp-rest-api-log-purge-all-spinner' ).addClass( 'hidden' ).removeClass( 'is-active' );
			} );
		}
	};

	$( document ).ready( function() {
		WP_REST_API_Log.init();
	} );

})( jQuery );
