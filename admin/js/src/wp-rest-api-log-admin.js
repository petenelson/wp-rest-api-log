(function( $ ) {

	WP_REST_API_Log = {

		entry_element: null,

		init: function() {

			this.entry_element = $( document.getElementById( 'wp-rest-api-log-entry' ) );

			this.highlightBlocks();
			this.adjustPostsListsTable();

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
		}

	};

	$( document ).ready( function() {
		WP_REST_API_Log.init();
	} );

})( jQuery );
