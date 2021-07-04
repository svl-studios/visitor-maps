/* global visitorMapsAjax, visitorMaps, console, jQuery, ajaxurl */

( function( $ ) {
	'use strict';

	$.visitorMaps      = $.visitorMaps || {};
	$.visitorMaps.ajax = $.visitorMaps.ajax || {};

	$( document ).ready(
		function() {
			$.visitorMaps.ajax.geoLiteCityUpdate( $( this ) );
			$.visitorMaps.ajax.runLookupTest();
		}
	);

	$.visitorMaps.ajax.runLookupTest = function() {
		$( '.geolitecity-lookup' ).on(
			'click',
			function( e ) {
				var updateParent = $( this ).parent().parent();
				var nonce        = updateParent.attr( 'data-nonce' );

				updateParent.removeClass(
					'updated-message'
				).addClass(
					'updating-message'
				);

				updateParent.find( 'p' ).html( visitorMaps.update_notice.enable + '<span class="lookup-data"></span>' );

				$.ajax(
					{
						type: 'post',
						dataType: 'html',
						url: visitorMapsAjax.ajaxurl,

						data: {
							action: 'visitor_maps_lookup',
							nonce: nonce
						},

						error: function( response ) {
							console.log( response );

							updateParent.removeClass(
								'updating-message notice-success'
							).addClass(
								'update-message notice-error'
							);

							updateParent.find( 'p' ).html( visitorMapsAjax.update_geolitecity.lookup_fail );
						},

						success: function( response ) {
							updateParent.removeClass(
								'updating-message'
							).addClass(
								'updated-message'
							);

							updateParent.find( '.lookup-data' ).html( response );

							console.log( response );
						}
					}
				);

				e.preventDefault();

				return false;
			}
		);
	};

	$.visitorMaps.ajax.geoLiteCityUpdate = function( obj ) {
		obj.find( '.update-geolitecity' ).on(
			'click',
			function( e ) {
				var updateParent = $( this ).parent().parent();
				var nonce        = updateParent.data( 'nonce' );
				var update       = Boolean( updateParent.data( 'update' ) );

				e.preventDefault();

				updateParent.find( 'p' ).text( visitorMapsAjax.update_geolitecity.updating );

				updateParent.removeClass(
					'updating-message updated-message notice-success notice-warning notice-error'
				).addClass(
					'update-message notice-warning updating-message'
				);

				$.ajax(
					{
						type: 'post',
						dataType: 'json',
						url: ajaxurl,

						data: {
							action: 'visitor_maps_geolitecity',
							nonce: nonce,
							update: update
						},

						error: function( response ) {
							console.log( response );

							updateParent.removeClass(
								'notice-warning updating-message updated-message notice-success'
							).addClass(
								'notice-error'
							);

							updateParent.find( 'p' ).html( visitorMapsAjax.update_geolitecity.error );

							$.visitorMaps.ajax.geoLiteCityUpdate( obj );
						},

						success: function( response ) {
							var geoLocateWarn;
							var descDiv;
							var table;
							var tr;
							var fs;

							console.log( response );

							if ( 'success' === response.status ) {
								updateParent.find( 'p' ).html(
									visitorMapsAjax.update_geolitecity.success + '<span class="lookup-data"></span>'
								);

								updateParent.removeClass(
									'updating-message notice-warning'
								).addClass(
									'updated-message notice-success'
								);

								$( '.visitor-maps-geolitecity' ).not( '.notice-success' ).remove();

								if ( update ) {
									updateParent.data( 'update', false );
								}

								$.visitorMaps.ajax.runLookupTest();

								// Remove option hidden notice.
								geoLocateWarn = updateParent.parent().find( '.geolocation-warn' );

								if ( 0 < geoLocateWarn.length ) {
									geoLocateWarn.remove();
								}

								// show Enable GeoLocation option.
								descDiv = updateParent.parent();
								table   = descDiv.next();
								tr      = table.find( 'tr.location-enable' );

								if ( 0 < tr.length ) {
									if ( tr.hasClass( 'hidden' ) ) {
										tr.removeClass( 'hidden' );

										fs = tr.find( 'fieldset.hidden' );

										if ( 0 < fs.length ) {
											fs.removeClass( 'hidden' );
										}
									}
								}
							} else {
								updateParent.removeClass(
									'notice-warning updating-message updated-message notice-success'
								).addClass(
									'notice-error'
								);

								updateParent.find( 'p' ).html( visitorMapsAjax.update_geolitecity.error );

								$.visitorMaps.ajax.geoLiteCityUpdate( obj );
							}
						}
					}
				);

				return false;
			}
		);
	};
})( jQuery );
