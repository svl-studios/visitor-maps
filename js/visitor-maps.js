/* global visitorMaps, jQuery, htaccess, bannedReferers, adminHost, bannedIps, mode, adminIp, vmIp, vmMessage, htaccessWarning, vmAutoUpdate, vmAutoUpdateTime, vmTableSelectorText */

( function( $ ) {
	'use strict';

	var linkMaxWidth = 400;

	$.visitorMaps = $.visitorMaps || {};

	$( document ).ready(
		function() {
			$.visitorMaps.init();
			$.visitorMaps.switchClick();
			$.visitorMaps.mapConsoleClick();
			$.visitorMaps.whoIsClick();
			$.visitorMaps.pageNavClick();
		}
	);

	$.visitorMaps.init = function() {
		var editorHtml = '';
		var numIps;
		var numReferers;
		var i = 1;
		var t;
		var int;
		var nonce;

		nonce = $( '.visitor-map-actions' ).data( 'nonce' );

		if ( vmMessage ) {
			$( '#vm_form' ).after( '<div class="updated">' + vmMessage + '</div>' );

			if ( ! htaccessWarning ) {
				$( '.updated' ).delay( 5000 ).hide( 'slow' );
			}
		}

		editorHtml += '<br /><br /><a href="#" class="vm-auto-update-form-container-toggle" title="Toggle to view or edit auto-update settings">+ ' + visitorMaps.automaticUpdate + '</a> (';

		if ( ! vmAutoUpdate ) {
			editorHtml += 'Off';
		} else {
			editorHtml += 'On';
		}

		editorHtml += ') <div class="vm-auto-update-form-container" style="padding:10px 4px;display:none;">' +
			'<form id="vm_auto_update_form" action="' + window.location.href + '" method="post">' +
			'<input type="hidden" name="vm_mode_nonce" value="' + nonce + '">' +
			'<input type="hidden" name="vm_mode" id="vm_mode" value="set auto update" />';

		editorHtml += visitorMaps.autoUpdate + ' <select title="Automatically refresh the Who\'s Been Online grid?" size="1" name="vm_auto_update" id="vm_auto_update"><option value="false"';

		if ( ! vmAutoUpdate ) {
			editorHtml += ' selected="selected"';
		}

		editorHtml += '>Off</option><option value="true"';

		if ( vmAutoUpdate ) {
			editorHtml += ' selected="selected"';
		}

		editorHtml += '>' + visitorMaps.on + '</option></select><br />' + visitorMaps.refreshGridEvery + ' ' +
			'<input type="number" min="1" value="' + vmAutoUpdateTime + '" name="vm_auto_update_time" style="width:50px;" title="Enter a number no less than 1" required="required" /> ' + visitorMaps.minutes + '.<br />' +
			'<button type="submit" class="vm_auto_update button-primary" data-mode="set auto update" title="Update settings"  style="cursor:pointer;">Update Settings</button>' +
			'<span class="vm_refresh_button button-secondary" title="Refresh the Who\'s Been Online grid now." style="cursor:pointer;">Refresh Now</span>' +
			'</form><br />Progress <div class="vm_refresh_status" title="Time remaining" style="width:200px;background-color:#DDDDDD;border:1px solid #C0C0C0;color:#333333;padding:1px;text-decoration:none;-moz-border-radius:10px;-khtml-border-radius:10px;-webkit-border-radius:10px;border-radius:10px;">' +
			'<div class="vm_refresh_progress" style="width:1%;padding:0px;line-height:10px;color:#F5F5F5;border-right:1px solid #333333;-moz-border-radius:10px;-khtml-border-radius:10px;-webkit-border-radius:10px;border-radius:10px;background:#075698;background:-webkit-gradient(linear, 0 0, 0 100%, from(#E9E9E9), to(#FFFFFF));background:-moz-linear-gradient(#E9E9E9, #FFFFFF);background:-o-linear-gradient(#E9E9E9, #FFFFFF);background:linear-gradient(#E9E9E9, #FFFFFF);">.</div></div></div>';

		if ( htaccess ) {

			// enable editors and key.
			numIps      = ( '' === bannedIps[0]) ? 0 : bannedIps.length;
			editorHtml += '<br /><a href="#" class="vm-banned-ips-form-container-toggle" title="Toggle to view or edit banned IP addresses">+ Banned IP Addresses</a> (' + numIps + ')' +
				'<div class="vm-banned-ips-form-container" style="padding:10px 4px;display:none;">';

			if ( 0 < numIps ) {
				editorHtml += '<a class="vm_ban_toggle vm_purge" data-ip="000.000.000.000" data-mode="purge" title="Unban all IP addresses." href="javascript:void(0);" style="">Unban All</a>';
			}

			editorHtml += '<div id="vm_ip_form" style="width:180px; height:80px; overflow:auto; border:1px solid #C0C0C0; background-color: #EFEFEF; padding: 5px;">';

			for ( i = 0; i < numIps; i++ ) {
				editorHtml += '<a class="vm_ban_toggle" data-ip="' + bannedIps[i] + '" data-mode="unban" title="Unban this IP address" href="javascript:void(0);" style="background-color:#B30000;color:white;padding:0px 4px;text-decoration:none;-moz-border-radius:10px;-khtml-border-radius:10px;-webkit-border-radius:10px;border-radius:10px;">X</a> ' + bannedIps[i] + '<br />';
			}

			editorHtml += '</div><a class="vm_ban_toggle add_ip" data-ip="" data-mode="ban" title="Ban any IP address" href="javascript:void(0);" style="background-color:#008000;color:white;padding:0px 4px;text-decoration:none;-moz-border-radius:10px;-khtml-border-radius:10px;-webkit-border-radius:10px;border-radius:10px;">X</a> <input type="text" value="" class="ip_to_add" placeholder="New IP Address To Ban" style="width:174px;" /></div>';

			numReferers = ( '' === bannedReferers[0]) ? 0 : bannedReferers.length;

			editorHtml += '<br /><a href="#" class="vm-banned-referers-form-container-toggle" title="Toggle to view or edit banned referers">+ Banned Referers</a> (' + numReferers + ')' +
				'<div class="vm-banned-referers-form-container" style="padding:10px 4px;display:none;">';

			if ( 0 < numReferers ) {
				editorHtml += '<a class="vm_ban_referers_toggle vm_purge_referers" data-referer="" data-mode="purge referers" title="Unban all referers." href="javascript:void(0);" style="">Unban All</a>';
			}

			editorHtml += '<div id="vm_referers_form" style="width:180px; height:80px; overflow:auto; border:1px solid #C0C0C0; background-color: #EFEFEF; padding: 5px;">';

			for ( i = 0; i < numReferers; i++ ) {
				editorHtml += '<a class="vm_ban_referers_toggle" data-referer="' + bannedReferers[i] + '" data-mode="unban referer" title="Unban this referer" href="javascript:void(0);" style="background-color:#B30000;color:white;padding:0px 4px;text-decoration:none;-moz-border-radius:10px;-khtml-border-radius:10px;-webkit-border-radius:10px;border-radius:10px;">X</a> ' + bannedReferers[i] + '<br />';
			}

			editorHtml += '</div><a class="vm_ban_referers_toggle add_referer" data-referer="" data-mode="ban referer" title="Ban any referer" href="javascript:void(0);" style="background-color:#008000;color:white;padding:0px 4px;text-decoration:none;-moz-border-radius:10px;-khtml-border-radius:10px;-webkit-border-radius:10px;border-radius:10px;">X</a> <input type="text" value="" class="vm_referer_to_add" placeholder="New Referer To Ban" style="width:174px;" /></div>';

			$( 'a.map-console' ).after( editorHtml );

			$( '.vm-banned-ips-form-container-toggle' ).click(
				function() {
					if ( $( '.vm-banned-ips-form-container' ).is( ':visible' ) ) {
						$( '.vm-banned-ips-form-container' ).hide( 'slow' );
						$( this ).html( '+ Banned IP Addresses' );
					} else {
						$( '.vm-banned-ips-form-container' ).show( 'slow' );
						$( this ).html( '- Banned IP Addresses' );
					}
				}
			);

			$( '.vm-banned-referers-form-container-toggle' ).click(
				function() {
					if ( $( '.vm-banned-referers-form-container' ).is( ':visible' ) ) {
						$( '.vm-banned-referers-form-container' ).hide( 'slow' );
						$( this ).html( '+ Banned Referers' );
					} else {
						$( '.vm-banned-referers-form-container' ).show( 'slow' );
						$( this ).html( '- Banned Referers' );
					}
				}
			);

			$( '.visitor-map-key' ).append(
				'<tr><td><span class="key-ban">X</span> Ban   </td>' +
				'<td><span class="key-unban">X</span> Unban  </td></tr>' +
				'<tr><td><span class="key-admin">A</span> Admin   </td>' +
				'<td><span class="key-disabled">X</span> Disabled  </td></tr>'
			);
		} else {
			$( 'a.map-console' ).after( editorHtml );
		} // if htaccess.

		$( '.vm-auto-update-form-container-toggle' ).click(
			function() {
				if ( $( '.vm-auto-update-form-container' ).is( ':visible' ) ) {
					$( '.vm-auto-update-form-container' ).hide( 'slow' );
					$( this ).html( '+ Automatic Update' );
				} else {
					$( '.vm-auto-update-form-container' ).show( 'slow' );
					$( this ).html( '- Automatic Update' );
				}
			}
		);

		$.visitorMaps.refresh();

		$.visitorMaps.activateGrid();

		// $( '.visitor-maps-data' ).wrap( '<div id="vm-grid-container"></div>' );

		if ( vmAutoUpdate ) {
			t   = Math.round( parseInt( vmAutoUpdateTime ) * 60 * 1000 );
			int = setInterval( $.visitorMaps.reloadGrid, t );

			$( '.vm_refresh_progress' ).css( 'width', '1%' ).animate(
				{ width: '100%' },
				t,
				function() {}
			);
		}
	};

	$.visitorMaps.refresh = function() {
		$( document ).on(
			'click',
			'.vm_refresh_button',
			function( event ) {
				event.preventDefault();

				$( '#vm-grid-container' ).load(
					window.location.href + ' table:contains("' + vmTableSelectorText + '")',
					function() {
						$.visitorMaps.reloadGrid();

						try {
							console.log( 'Grid successfully reloaded.' );
						} catch ( e ) {
						}
					}
				);
			}
		);
	};

	$.visitorMaps.getHostname = function( str ) {
		var re;
		var hn = '';
		var t  = str.split( 'file:' );

		if ( ! t[1]) {
			re = new RegExp( '^(?:f|ht)tp(?:s)?\://([^/]+)', 'im' );

			try {
				hn = str.match( re )[1].toString();
			} catch ( e ) {
			}
			if ( '' === hn ) {
				hn = 'Bot';
			}
			return hn;
		} else {
			return 'localhost';
		}
	};

	$.visitorMaps.getUrlVars = function( originalLink ) {
		var vars = {};

		originalLink.replace(
			/[?&]+([^=&]+)=([^&]*)/gi,
			function( m, key, value ) {
				vars[key] = value;
			}
		);

		return vars;
	};

	$.visitorMaps.reloadGrid = function() {
		var t = Math.round( parseInt( vmAutoUpdateTime ) * 60 * 1000 );

		$( '.vm_refresh_progress' ).stop().css( 'width', '1%' ).animate(
			{ width: '100%' },
			t,
			function() {}
		);

		$( '#vm-grid-container' ).load(
			window.location.href + ' table:contains("' + vmTableSelectorText + '")',
			function() {
				$.visitorMaps.activateGrid();
				try {
					console.log( 'Grid successfully reloaded. Reloading in ' + vmAutoUpdateTime + ' minutes.' );
				} catch ( e ) {
				}
			}
		);
	};

	$.visitorMaps.activateGrid = function() {
		$( 'td[class*="referer"] a' ).each(
			function() {
				var originalLink = $( this ).attr( 'href' );
				var q            = $.visitorMaps.getUrlVars( originalLink ).q;
				var host;
				var originalQuery;

				if ( ! q && $.visitorMaps.getUrlVars( originalLink ).p ) {
					q = $.visitorMaps.getUrlVars( originalLink ).p;
				}

				if ( originalLink ) {
					host = $.visitorMaps.getHostname( originalLink ).replace( /www./gi, '' );

					if ( q ) {
						originalQuery = q;
						q             = q.replace( /%20|%2B|\+/gi, ' ' ).replace( /%3E|%3C/gi, '' ).toLowerCase();

						$( this ).html( host );
						$( this ).after(
							' <a class="referer-string" href="https://www.google.com/#hl=en&safe=on&output=search&sclient=psy-ab&q=' +
							originalQuery + '" target="_blank" title="Search Google for ' + decodeURI( q ) + '">' + decodeURI( q ) + '</a>'
						);
					} else {
						$( this ).html( host );
					}

					// Proxify original link.
					$( this ).attr(
						{'href': originalLink, 'title': originalLink}
					);

					if ( 'localhost' === host ) {
						$( this ).replaceWith(
							'<span class="disabled" title="' + originalLink + '">X</span> ' +
							'<span class="referer-string" title="' + originalLink + '">' + host + '</span>'
						);
					}

					if ( htaccess ) {

						// enable referer banning.
						if ( -1 !== $.inArray( host, bannedReferers ) && adminHost !== host && 'localhost' !== host ) {
							$( this ).before(
								'<a class="vm_ban_referers_toggle" data-referer="' + host + '" data-mode="unban referer" title="Unban this referer" href="javascript:void(0);" style="background-color:#B30000;color:white;padding:0px 4px;text-decoration:none;-moz-border-radius:10px;-khtml-border-radius:10px;-webkit-border-radius:10px;border-radius:10px;">X</a> '
							);
						} else if ( adminHost !== host && 'localhost' !== host ) {
							$( this ).before(
								'<a class="vm_ban_referers_toggle" data-referer="' + host + '" data-mode="ban referer" title="Ban this referer" href="javascript:void(0);" style="background-color:#008000;color:white;padding:0px 4px;text-decoration:none;-moz-border-radius:10px;-khtml-border-radius:10px;-webkit-border-radius:10px;border-radius:10px;">X</a> '
							);
						} else {
							$( this ).before(
								'<span title="Home" style="background-color:#D4D4D4;color:white;padding:0px 4px;text-decoration:none;-moz-border-radius:10px;-khtml-border-radius:10px;-webkit-border-radius:10px;border-radius:10px;cursor:pointer;">X</span> '
							);
						}
					}
				}
			}
		);

		// $( '.table-top td:contains("' + refererText + '")' ).append( ' & Search String' );

		$( '.column-dark td:has(a), .column-light td:has(a)' ).each(
			function() {
				$( this ).wrapInner(
					'<div style="max-width:' + linkMaxWidth + 'px; overflow:hidden; padding:0px; margin:0px"></div>'
				);
			}
		);

		if ( htaccess ) {

			// enable ip banning.
			$( 'a[href*="http://www.ip-adress.com/ip_tracer/"]' ).each(
				function() {
					var ip = $( this ).attr( 'href' ).split( 'http://www.ip-adress.com/ip_tracer/' );
					if ( ( -1 !== $.inArray( ip[1], bannedIps ) || ( ip[1] === vmIp && 'unban' !== mode ) ) && ( ip[1] !== adminIp ) ) {
						$( this ).before(
							'<a class="vm_ban_toggle unban" data-ip="' + ip[1] + '" data-mode="unban" title="Unban this IP address" href="javascript:void(0);">X</a> '
						);
					} else if ( ip[1] !== adminIp ) {
						$( this ).before(
							'<a class="vm_ban_toggle ban" data-ip="' + ip[1] + '" data-mode="ban" title="Ban this IP address" href="javascript:void(0);">X</a> '
						);
					} else {
						$( this ).before(
							'<span class="admin" title="Admin">A</span> '
						);
					}
				}
			);
		}

		$( '.vm_ban_toggle' ).on(
			'click',
			function() {
				var c;

				if ( $( this ).hasClass( 'vm_purge' ) ) {
					c = confirm( 'Are you sure you want to unban ALL of the IP addresses?' );

					if ( ! c ) {
						return false;
					}
				}

				if ( $( this ).hasClass( 'add_ip' ) ) {
					$( '#vm_form input#vm_mode' ).val( 'ban' );
					$( '#vm_form input#vm_ip' ).val( $( '.ip_to_add' ).val() );
				} else {
					$( '#vm_form input#vm_mode' ).val( $( this ).data( 'mode' ) );
					$( '#vm_form input#vm_ip' ).val( $( this ).data( 'ip' ) );
				}

				if ( $( '#vm_form input#vm_ip' ).val() && '' !== $( '#vm_form input#vm_ip' ).val() ) {
					$( '#vm_form' ).submit();
				} else {
					alert( 'Error: No IP address was entered!' );
				}
			}
		);

		$( '.vm_ban_referers_toggle' ).on(
			'click',
			function() {
				var c;

				if ( $( this ).hasClass( 'vm_purge_referers' ) ) {
					c = confirm( 'Are you sure you want to unban ALL of the referers?' );

					if ( ! c ) {
						return false;
					} else {
						$( '#vm_form input#vm_mode' ).val( 'purge referers' );
						$( '#vm_form input#vm_referer' ).val( 'purge referers' );
					}
				} else {
					if ( $( this ).hasClass( 'add_referer' ) ) {
						$( '#vm_form input#vm_mode' ).val( 'ban referer' );
						$( '#vm_form input#vm_referer' ).val( $( '.vm_referer_to_add' ).val() );
					} else {
						$( '#vm_form input#vm_mode' ).val( $( this ).data( 'mode' ) );
						$( '#vm_form input#vm_referer' ).val( $( this ).data( 'referer' ) );
					}
				}

				if ( $( '#vm_form input#vm_referer' ).val() && '' !== $( '#vm_form input#vm_referer' ).val() ) {
					$( '#vm_form' ).submit();
				} else {
					alert( 'Error: No referer was entered!' );
				}
			}
		);

		// numGuests = $( 'img[alt="Active Guest"]' ).not( 'img[alt="Active Guest"]:first' ).length - 1;

		// $( 'title' ).html( '(' + numGuests + ') Who\'s Been Online' );
	}; // activate.

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
					$( this ).attr( 'href' ),
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
