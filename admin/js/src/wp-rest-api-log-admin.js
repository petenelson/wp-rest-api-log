(function( $ ) {
	'use strict';

	$.extend( {
		wp_rest_api_log: {

			reset_html: '',
			search_args: { },

			document_ready: function() {
				$.wp_rest_api_log.highlight_block();
			},

			highlight_block: function() {

				$( '.wp-rest-api-log-entry' ).find( 'code' ).each( function( i, block ) {
					hljs.highlightBlock( block );
				} );

			},

			json_stringify: function( obj ) {
				return JSON.stringify( obj, null, 2 ); // spacing level = 2;
			},

			toggle_entry_details_element: function( elemDetails ) {
				if ( elemDetails.hasClass('entry-details-visible') ) {
					elemDetails.removeClass( 'entry-details-visible' );
				} else {
					elemDetails.addClass( 'entry-details-visible' );
				}
			},

			toggle_inside_element: function( element ) {
				element = element.parent().find( '.inside' );
				if ( element.hasClass('visible') ) {
					element.removeClass( 'visible' ).addClass( 'collapsed' );
				} else {
					element.addClass( 'visible' ).removeClass( 'collapsed' );
				}
			}


		} // end wp_rest_api_log object
	} );



	// send nonce to the WP API
	$( document ).ajaxSend( function( event, xhr ) {
		// you can also send _wp_rest_nonce in the GET or POST params
		xhr.setRequestHeader( 'X-WP-Nonce', wp_rest_api_log_admin.nonce );
	});


	// hook up our JS
	$( document ).ready(function() {
		$.wp_rest_api_log.document_ready();
	});


})( jQuery );
