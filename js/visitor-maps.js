/* global visitorMaps, jQuery  */

( function( $ ) {
	'use strict';

	$.visitorMaps = $.visitorMaps || {};

	$( document ).ready(
		function() {
			$.visitorMaps.switchClick();
		}
	);

	$.visitorMaps.switchClick = function() {
		var redux = $( '.redux-main .location-enable .switch-options label' );

		redux.bind(
			'click',
			function( ) {
				if ( $( this ).hasClass( 'cb-enable' ) ) {
					$.visitorMaps.toggleNotices( true );
				} else if ( $( this ).hasClass( 'cb-disable' ) ) {
					$.visitorMaps.toggleNotices( false );
				}
			}
		);

		$.visitorMaps.toggleNotices = function( state ) {
			var notice = $( '.redux-main .visitor-maps-geolitecity' );
			var update = Boolean( notice.data( 'update' ) );
			var msg;

			if ( true === state ) {
				notice.removeClass(
					'update-message notice-error'
				).addClass(
					'updated-message notice-success'
				);

				if ( update ) {
					msg = visitorMaps.update_notice.update;
				} else {
					msg = visitorMaps.update_notice.lookup;
				}

				notice.find( 'p' ).html(
					visitorMaps.update_notice.enable + msg + '<span class="lookup-data"></span>'
				);

				if ( update ) {
					$.visitorMaps.ajax.geoLiteCityUpdate( notice );
				} else {
					$.visitorMaps.ajax.runLookupTest();
				}

			} else {
				notice.removeClass(
					'updated-message notice-success'
				).addClass(
					'update-message notice-error'
				);

				notice.find( 'p' ).html( visitorMaps.update_notice.disable );
			}
		};
	};
})( jQuery );
