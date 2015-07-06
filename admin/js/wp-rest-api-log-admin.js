(function( $ ) {
	'use strict';

	$.extend( {
		wp_rest_api_log: {

			search: function() {
				// search function
				var args = [];

				$.post( wp_rest_api_log_admin.route, args, function( response ) {




				} ).fail( function() {

				});



			},

			display_entries: function() {
				if ( ! wp_rest_api_log_admin.entries_html_rows ) {
					return;
				}

				$( '.wp-rest-api-log-wrap .tbody').html( wp_rest_api_log_admin.entries_html_rows );

			}

		} // end wp_rest_api_log object
	} );


	$( document ).ajaxSend( function( event, xhr ) {
		xhr.setRequestHeader( 'X-WP-Nonce', wp_rest_api_log_admin.nonce );
	});


	$( document ).ready(function() {
		$.wp_rest_api_log.search();
	});


})( jQuery );
