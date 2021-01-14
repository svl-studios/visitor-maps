/* global visitorMaps, jQuery  */

( function( $ ) {
	'use strict';

	$.visitorMaps = $.visitorMaps || {};

	$( document ).ready(
		function() {
			$.visitorMaps.switchClick();
			$.visitorMaps.mapConsoleClick();
			$.visitorMaps.whoIsClick();
			$.visitorMaps.pageNavClick();
		}
	);

	$.visitorMaps.pageNavClick = function( ) {
		var obj = $( '.visitors-pagination .page-nav' );

		obj.on(
			'click',
			function( e ) {
				var pageno = $( this ).data( 'pageno' );

				e.preventDefault();

				$( '.visitor-maps-data #pageno' ).val( pageno );

				document.getElementById( 'vm_nav' ).submit();
			}
		);
	};

	$.visitorMaps.whoIsClick = function( ) {
		var whoIsLink = $( '.visitor-maps-data a.whois-lookup, a.whois-lookup' );

		whoIsLink.on(
			'click',
			function( e ) {
				e.preventDefault();

				window.open(
					whoIsLink.attr( 'href' ),
					'who_is_lookup',
					'height=650,width=800,toolbar=no,statusbar=no,scrollbars=yes'
				).focus();
			}
		);
	};

	$.visitorMaps.mapConsoleClick = function( ) {
		var mapLink = $( '.visitor-map-actions a.map-console, a.map-console-bottom' );

		mapLink.on(
			'click',
			function( e ) {
				e.preventDefault();

				window.open(
					mapLink.attr( 'href' ),
					'wo_map_console',
					'height=650,width=800,toolbar=no,statusbar=no,scrollbars=yes'
				).focus();
			}
		);
	};

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
