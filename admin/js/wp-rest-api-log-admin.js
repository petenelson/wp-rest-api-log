(function( $ ) {
	'use strict';

	$.extend( {
		wp_rest_api_log: {

			reset_html: '',
			search_args: { },

			document_ready: function() {

				$( '.wp-rest-api-log-wrap' ).on( 'click', '.log-entries .entry-row', function() {
					$.wp_rest_api_log.display_details( $( this ).data( 'id' ) );
				});

				$( '.wp-rest-api-log-wrap' ).on( 'click', '.log-entries .postbox h3', function() {
					$.wp_rest_api_log.toggle_inside_element( $( this ) );
				});

				$( '.wp-rest-api-log-wrap .search-form .datetimepicker' ).datepicker( {
					dateFormat:'yy-mm-dd'
				} );

				$( '.wp-rest-api-log-wrap .search-form .button-reset').click( function( e ) {

					e.preventDefault();

					// clear search params
					$( '.wp-rest-api-log-wrap .search-form .clear-on-reset').val( '' );

					$( '.wp-rest-api-log-wrap .table-wrap' ).html( $.wp_rest_api_log.reset_html );

				} );


				$( '.wp-rest-api-log-wrap .search-form form' ).submit( function( e ) {

					$( this ).find('.ajax-wait').addClass( 'visible-inline' );

					e.preventDefault();

					$.wp_rest_api_log.search_args = {
						response_type:'wp_admin_html',
						from:$( this ).find('.from').val(),
						to:$( this ).find('.to').val(),
						method:$( this ).find('.method').val(),
						route:$( this ).find('.route').val(),
						param:$( this ).find('.param').val()
					};

					$.wp_rest_api_log.search( $.wp_rest_api_log.search_args, $.wp_rest_api_log.display_entries );

				});

				$.wp_rest_api_log.reset_html = $( '.wp-rest-api-log-wrap .table-wrap' ).html();

			},

			search: function( args, callback ) {

				$.get( wp_rest_api_log_admin.route, args, function( response ) {

					callback( response );

					$( '.wp-rest-api-log-wrap .ajax-wait' ).removeClass( 'visible' ).removeClass('visible-inline');

				}).fail( function() {

				});

			},

			display_entries: function( response ) {

				console.debug(response.query);

				if ( response && response.entries_html ) {
					$( '.wp-rest-api-log-wrap .table-wrap').html( response.entries_html );
				}

			},

			show_empty_results: function() {
				// TOD
			},

			display_details: function( id ) {

				var elemDetails = $.wp_rest_api_log.get_entry_details_element( id );

				if ( elemDetails.hasClass( 'entry-details-populated') ) {
					$.wp_rest_api_log.toggle_entry_details_element( elemDetails );
					return;
				}

				$( '.wp-rest-api-log-wrap .log-entries .entry-row-' + id + ' .ajax-wait' ).addClass( 'visible' );

				$.wp_rest_api_log.search( { id:id, fields:'all' }, $.wp_rest_api_log.populate_entry_details );

			},

			populate_entry_details: function( details ) {

				if ( details.paged_records && details.paged_records.length === 1 ) {
					details = details.paged_records[0];
				}

				var elemDetails = $.wp_rest_api_log.get_entry_details_element( details.id );

				// populate data
				elemDetails.find( '.request-headers .inside pre').html( $.wp_rest_api_log.json_stringify( details.request_headers ) );
				elemDetails.find( '.querystring-parameters .inside pre').html( $.wp_rest_api_log.json_stringify( details.request_query_params ) );
				elemDetails.find( '.body-parameters .inside pre').html( $.wp_rest_api_log.json_stringify( details.request_body_params ) );
				elemDetails.find( '.response-body .inside pre').html( $.wp_rest_api_log.json_stringify( details.response_body ) );

				// toggle display
				$.wp_rest_api_log.toggle_entry_details_element( elemDetails );

				elemDetails.addClass( 'entry-details-populated' );

			},

			json_stringify: function( obj ) {

				var json = JSON.stringify(obj, null, 2); // spacing level = 2;

				// https://jsfiddle.net/KJQ9K/554/
				json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
				return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
					var cls = 'number';
					if (/^"/.test(match)) {
						if (/:$/.test(match)) {
							cls = 'key';
						} else {
							cls = 'string';
						}
					} else if (/true|false/.test(match)) {
						cls = 'boolean';
					} else if (/null/.test(match)) {
						cls = 'null';
					}
					return '<span class="' + cls + '">' + match + '</span>';
				});

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
					element.removeClass( 'visible' );
				} else {
					element.addClass( 'visible' );
				}
			},

			get_entry_details_element: function( id ) {
				return $( '.wp-rest-api-log-wrap .log-entries .entry-details-' + id );
			}

		} // end wp_rest_api_log object
	} );



	// send nonce to the WP API
	$( document ).ajaxSend( function( event, xhr ) {
		xhr.setRequestHeader( 'X-WP-Nonce', wp_rest_api_log_admin.nonce );
	});


	// DOM handlers
	$( document ).ready(function() {

		$.wp_rest_api_log.document_ready();


	});


})( jQuery );
