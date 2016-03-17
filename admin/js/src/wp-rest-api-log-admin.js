(function( $ ) {

	WP_REST_API_Log = {

		entry_element: null,

		init: function() {
			console.log( 'WP_REST_API_Log init' );

			this.entry_element = $( document.getElementById( 'wp-rest-api-log-entry' ) );

			this.highlight_blocks();
		},

		populate_entry: function() {
			if ( this.entry_element.length > 0 && 'undefined' !== typeof WP_REST_API_Log_Entry_Data ) {

				// this.entry_element.find( '.request-headers code'        ).html( this.json_stringify( WP_REST_API_Log_Entry_Data.entry.request.headers ) );
				// this.entry_element.find( '.querystring-parameters code' ).html( this.json_stringify( WP_REST_API_Log_Entry_Data.entry.request.query_params ) );
				// this.entry_element.find( '.body-parameters code'        ).html( this.json_stringify( WP_REST_API_Log_Entry_Data.entry.request.body_params ) );

			}
		},

		highlight_blocks: function() {

			this.entry_element.find( 'code' ).each( function( i, block ) {
				hljs.highlightBlock( block );
			} );

		},

		json_stringify: function( obj ) {
			return JSON.stringify( obj, null, 2 ); // spacing level = 2;
		}


	};

	$( document ).ready( function() {
		WP_REST_API_Log.init();
	} );

})( jQuery );
