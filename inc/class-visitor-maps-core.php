<?php
/**
 * Visitor Maps Core Class
 *
 * @class Visitor_Maps_Core
 * @version 2.0.0
 * @package VisitorMaps
 */

defined( 'ABSPATH' ) || exit;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;

if ( ! class_exists( 'Visitor_Maps_Core' ) ) {

	/**
	 * Class Visitor_Maps_Core
	 */
	class Visitor_Maps_Core {

		/**
		 * Visitor_Maps_Core constructor.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'plugins_loaded', array( $this, 'register_widget' ), 11 );
			add_action( 'wp_dashboard_setup', array( $this, 'dashboard_widget' ) );

			add_action( 'admin_menu', array( $this, 'admin_menu' ), 1 );
			add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
			add_action( 'wp_head', array( $this, 'activity' ), 1 );
			add_action( 'admin_head', array( $this, 'activity' ), 1 );
			add_action( 'wp_footer', array( $this, 'public_footer_stats' ), 1 );
			add_action( 'admin_footer', array( $this, 'admin_footer_stats' ), 1 );

			add_shortcode( 'visitor-maps', array( $this, 'shortcode' ), 1 );

			add_action( 'admin_head', array( $this, 'admin_view_header' ), 1 );
			add_action( 'parse_request', array( $this, 'do_map_console' ), 1 );
			add_action( 'parse_request', array( $this, 'do_map_image' ), 2 );
		}

		/**
		 * Check URL last mod time.
		 *
		 * @param string $url    URL to check.
		 *
		 * @return false|int|void
		 */
		public function http_last_mod( string $url ) {
			$response = wp_remote_get( $url, array( 'timeout' => 1200 ) );

			if ( is_array( $response ) && ! is_wp_error( $response ) ) {
				$headers = wp_remote_retrieve_headers( $response );

				return strtotime( $headers['last-modified'] );
			}
		}

		/**
		 * Add menus to WP admin.
		 */
		public function admin_menu(): void {
			global $admin_page_hooks;
			$admin_page_hooks['whos-been-online'] = sanitize_title( 'whos-been-online' ); // phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited

			add_menu_page(
				'Visitor Maps',
				'Visitor Maps',
				Visitor_Maps::$core->get_option( 'dashboard_permissions' ),
				'visitor-maps',
				array(
					$this,
					'visitor_maps_admin_view',
				),
				'dashicons-groups',
				2
			);

			add_submenu_page(
				'visitor-maps',
				esc_html__( "Who's Online", 'visitor-maps' ),
				esc_html__( "Who's Online", 'visitor-maps' ),
				Visitor_Maps::$core->get_option( 'dashboard_permissions' ),
				'visitor-maps',
				array(
					$this,
					'visitor_maps_admin_view',
				)
			);

			add_submenu_page(
				'visitor-maps',
				esc_html__( "Who's Been Online", 'visitor-maps' ),
				esc_html__( "Who's Been Online", 'visitor-maps' ),
				Visitor_Maps::$core->get_option( 'dashboard_permissions' ),
				'whos-been-online',
				array(
					$this,
					'visitor_maps_whos_been_online',
				)
			);
		}

		/**
		 * "Who's Been Online" page.
		 */
		public function visitor_maps_whos_been_online(): void {
			if ( ! current_user_can( Visitor_Maps::$core->get_option( 'dashboard_permissions' ) ) ) {
				wp_die( esc_html__( 'You do not have permissions for managing this option', 'visitor-maps' ) );
			}

			echo '<div class="wrap">';
			echo '<h2>' . esc_html__( 'Visitor Maps', 'visitor-maps' ) . ' - ' . esc_html__( "Who's Been Online", 'visitor-maps' ) . '</h2>';

			require_once Visitor_Maps::$dir . 'inc/classes/class-whos-online-been.php';

			$wo_view = new Whos_Online_Been();
			$wo_view->view_whos_been_online();

			if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) && Visitor_Maps::$core->get_option( 'enable_dash_map' ) ) {
				echo '<br /><br />';

				$map_settings = array(
					'time'       => Visitor_Maps::$core->get_option( 'track_time' ),
					// digits of time.
					'units'      => 'minutes',
					// minutes, hours, or days (with or without the "s").
					'map'        => '2',
					// 1,2 3, etc. (you can add more map images in settings)
					'pin'        => '1',
					// 1,2,3, etc. (you can add more pin images in settings)
					'pins'       => 'off',
					// off (off is required for html map).
					'text'       => 'on',
					// on or off.
					'textcolor'  => '000000',
					// any hex color code.
					'textshadow' => 'FFFFFF',
					// any hex color code.
					'textalign'  => 'cb',
					// ll, ul, lr, ur, c, ct, cb (codes for: lower left, upper left, upper right, center, center top, center bottom).
					'ul_lat'     => '0',
					// default 0 for worldmap.
					'ul_lon'     => '0',
					// default 0 for worldmap.
					'lr_lat'     => '360',
					// default 360 for worldmap.
					'lr_lon'     => '180',
					// default 180 for worldmap.
					'offset_x'   => '0',
					// + or - offset for x axis  - moves pins left, + moves pins right.
					'offset_y'   => '0',
					// + or - offset for y axis  - moves pins up,   + moves pins down.
					'type'       => 'png',
					// jpg or png (map output type).
				);

				echo $this->get_visitor_maps_worldmap( $map_settings ); // phpcs:ignore WordPress.Security.EscapeOutput

				if ( ! Visitor_Maps::$core->get_option( 'hide_console' ) || ( Visitor_Maps::$core->get_option( 'hide_console' ) && current_user_can( 'manage_options' ) ) ) {

					// translators: %1$s: Blog URL.
					echo '<p>' . sprintf( esc_html__( 'View more maps in the %1$s', 'visitor-maps' ), '<a class="map-console-bottom" href="' . esc_url( get_bloginfo( 'url' ) ) . '?wo_map_console=1">' . esc_html__( 'Visitor Map Viewer', 'visitor-maps' ) . '</a>' ) . '</p>';
				}
			}

			if ( Visitor_Maps::$core->get_option( 'enable_credit_link' ) ) {
				echo '<p><small>' . esc_html__( 'Powered by', 'visitor-maps' ) . ' <a href="https://www.svlstudios.com" target="_new">' . esc_html__( 'Visitor Maps', 'visitor-maps' ) . '</a></small></p>';
			}

			echo '</div>';
		}

		/**
		 * "Visitor's Maps" page.
		 */
		public function visitor_maps_admin_view(): void {
			if ( ! current_user_can( Visitor_Maps::$core->get_option( 'dashboard_permissions' ) ) ) {
				wp_die( esc_html__( 'You do not have permissions for managing this option', 'visitor-maps' ) );
			}

			echo '<div class="wrap">';
			echo '<h2>' . esc_html__( 'Visitor Maps', 'visitor-maps' ) . ' - ' . esc_html__( "View Who's Online", 'visitor-maps' ) . '</h2>';

			require_once Visitor_Maps::$dir . 'inc/classes/class-whos-online-view.php';

			$wo_view = new Whos_Online_View();
			$wo_view->view_whos_online();

			if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) && Visitor_Maps::$core->get_option( 'enable_dash_map' ) ) {
				$map_settings = array(
					'time'       => Visitor_Maps::$core->get_option( 'track_time' ),
					// digits of time.
					'units'      => 'minutes',
					// minutes, hours, or days (with or without the "s").
					'map'        => '2',
					// 1,2 3, etc. (you can add more map images in settings)
					'pin'        => '1',
					// 1,2,3, etc. (you can add more pin images in settings)
					'pins'       => 'off',
					// off (off is required for html map).
					'text'       => 'on',
					// on or off.
					'textcolor'  => '000000',
					// any hex color code.
					'textshadow' => 'FFFFFF',
					// any hex color code.
					'textalign'  => 'cb',
					// ll, ul, lr, ur, c, ct, cb (codes for: lower left, upper left, upper right, center, center top, center bottom).
					'ul_lat'     => '0',
					// default 0 for worldmap.
					'ul_lon'     => '0',
					// default 0 for worldmap.
					'lr_lat'     => '360',
					// default 360 for worldmap.
					'lr_lon'     => '180',
					// default 180 for worldmap.
					'offset_x'   => '0',
					// + or - offset for x axis  - moves pins left, + moves pins right.
					'offset_y'   => '0',
					// + or - offset for y axis  - moves pins up,   + moves pins down.
					'type'       => 'png',
					// jpg or png (map output type).
				);

				echo $this->get_visitor_maps_worldmap( $map_settings ); // phpcs:ignore WordPress.Security.EscapeOutput

				if ( ! Visitor_Maps::$core->get_option( 'hide_console' ) || ( Visitor_Maps::$core->get_option( 'hide_console' ) && current_user_can( 'manage_options' ) ) ) {

					// translators: %1$s: Blog URL.
					echo '<p>' . sprintf( esc_html__( 'View more maps in the %1$s', 'visitor-maps' ), '<a class="map-console-bottom" href="' . esc_url( get_bloginfo( 'url' ) ) . '?wo_map_console=1">' . esc_html__( 'Visitor Map Viewer', 'visitor-maps' ) . '</a>' ) . '</p>';
				}
			}

			if ( Visitor_Maps::$core->get_option( 'enable_credit_link' ) ) {
				echo '<p><small>' . esc_html__( 'Powered by', 'visitor-maps' ) . ' <a href="https://www.svlstudios.com" target="_new">' . esc_html__( 'Visitor Maps', 'visitor-maps' ) . '</a></small></p>';
			}

			echo '</div>';
		}

		/**
		 * Do Map Console.
		 */
		public function do_map_console(): void {
			global $visitor_maps_stats;

			if ( isset( $_GET['wo_map_console'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( Visitor_Maps::$core->get_option( 'hide_console' ) && ! current_user_can( 'manage_options' ) ) {
					return;
				}

				$visitor_maps_stats = $this->visitor_maps_activity_do();
				?>
				<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
				<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
				<head profile="http://gmpg.org/xfn/11">
					<meta http-equiv="Content-Type" content="<?php bloginfo( 'html_type' ); ?>" charset="<?php bloginfo( 'charset' ); ?>"/>
					<title>
						<?php echo esc_html__( 'Visitor Maps', 'visitor-maps' ) . ' - ' . bloginfo( 'name' ); ?>
					</title>
					<style>
						table.wo_map {
							margin-left: auto;
							margin-right: auto;
						}

						#wrapper {
							margin-left: auto;
							margin-right: auto;
							text-align: center;
						}

						h3 {
							font-family: "Lucida Grande", Verdana, Arial, "Bitstream Vera Sans", sans-serif;
							font-size: 14px;
						}

						p {
							font-family: "Lucida Grande", Verdana, Arial, "Bitstream Vera Sans", sans-serif;
							font-size: 12px;
						}
					</style>
					<script>
						function getRefToDivMod( divID, oDoc ) {
							if ( !oDoc ) {
								oDoc = document;
							}
							if ( document.layers ) {
								if ( oDoc.layers[divID] ) {
									return oDoc.layers[divID];
								} else {
									for ( var x = 0, y; !y && x < oDoc.layers.length; x++ ) {
										y = getRefToDivNest( divID, oDoc.layers[x].document );
									}
									return y;
								}
							}
							if ( document.getElementById ) {
								return oDoc.getElementById( divID );
							}
							if ( document.all ) {
								return oDoc.all[divID];
							}
							return oDoc[divID];
						}

						function resizeWinTo( idOfDiv ) {
							var oH = getRefToDivMod( idOfDiv );
							if ( !oH ) {
								return false;
							}
							var x = window;
							x.resizeTo( screen.availWidth, screen.availWidth );
							var oW = oH.clip ? oH.clip.width : oH.offsetWidth;
							var oH = oH.clip ? oH.clip.height : oH.offsetHeight;
							if ( !oH ) {
								return false;
							}
							x.resizeTo( oW + 200, oH + 200 );
							var myW = 0, myH = 0, d = x.document.documentElement, b = x.document.body;
							if ( x.innerWidth ) {
								myW = x.innerWidth;
								myH = x.innerHeight;
							} else if ( d && d.clientWidth ) {
								myW = d.clientWidth;
								myH = d.clientHeight;
							} else if ( b && b.clientWidth ) {
								myW = b.clientWidth;
								myH = b.clientHeight;
							}
							if ( window.opera && !document.childNodes ) {
								myW += 16;
							}
							//second sample, as the table may have resized
							var oH2 = getRefToDivMod( idOfDiv );
							var oW2 = oH2.clip ? oH2.clip.width : oH2.offsetWidth;
							var oH2 = oH2.clip ? oH2.clip.height : oH2.offsetHeight;
							x.resizeTo( oW2 + ((oW + 200) - myW), oH2 + ((oH + 200) - myH) );
						}
					</script>
				</head>
				<?php
				if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
					?>
				<body onload="resizeWinTo('wrapper');" style="padding:0;margin:0;">
				<div style="position:absolute;left:0px;top:0px;" id="wrapper">
					<table>
						<tr>
							<td>
								<?php
								require_once Visitor_Maps::$dir . 'inc/classes/class-whos-online-map-page.php';

								$wo_map_page = new Whos_Online_Map_Page();
								$wo_map_page->do_map_page( false );

								echo '<p><a href="javascript:window.close()">' . esc_html__( 'Close', 'visitor-maps' ) . '</a></p>';

								if ( Visitor_Maps::$core->get_option( 'enable_credit_link' ) ) {
									echo '<p><small>' . esc_html__( 'Powered by Visitor Maps', 'visitor-maps' ) . '</small></p>';
								}
								?>
							</td>
						</tr>
					</table>
				</div>'
					<?php
				} else {
					echo '<body><p>' . esc_html__( 'Visitor Maps geolocation is disabled in settings.', 'visitor-maps' ) . '</p>';
				}
				?>
				</body>
				</html>
				<?php
				exit;
			}
		}

		/**
		 * Do Map Image.
		 */
		public function do_map_image(): void {
			if ( isset( $_GET['do_wo_map'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {

					require_once Visitor_Maps::$dir . 'inc/classes/class-whos-online-view-maps.php';

					$wo_view_map = new Whos_Online_View_Maps();
					$wo_view_map->display_map();
				}

				exit;
			}
		}

		/**
		 * Shortcode.
		 *
		 * @return string
		 */
		public function shortcode(): string {
			global $wpdb;

			$string = '';

			if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) && Visitor_Maps::$core->get_option( 'enable_page_map' ) ) {
				$nonce = wp_create_nonce( 'do_wo_map' );

				if ( Visitor_Maps::$core->get_option( 'enable_visitor_map_hover' ) || Visitor_Maps::$core->get_option( 'hide_text_on_worldmap' ) ) {
					$map_settings = array(
						// html map settings
						// set these settings as needed.
						'time'       => Visitor_Maps::$core->get_option( 'default_map_time' ),
						// digits of time.
						'units'      => Visitor_Maps::$core->get_option( 'default_map_units' ),
						// minutes, hours, or days (with or without the "s").
						'map'        => Visitor_Maps::$core->get_option( 'default_map' ),
						// 1,2 3, etc.
						'pin'        => '1',
						// 1,2,3, etc. (you can add more pin images in settings).
						'pins'       => 'off',
						// off (off is required for html map).
						'text'       => 'on',
						// on or off.
						'textcolor'  => '000000',
						// any hex color code.
						'textshadow' => 'FFFFFF',
						// any hex color code.
						'textalign'  => 'cb',
						// ll, ul, lr, ur, c, ct, cb (codes for: lower left, upper left, upper right, center, center top, center bottom).
						'ul_lat'     => '0',
						// default 0 for worldmap.
						'ul_lon'     => '0',
						// default 0 for worldmap.
						'lr_lat'     => '360',
						// default 360 for worldmap.
						'lr_lon'     => '180',
						// default 180 for worldmap.
						'offset_x'   => '0',
						// + or - offset for x axis  - moves pins left, + moves pins right
						'offset_y'   => '0',
						// + or - offset for y axis  - moves pins up,   + moves pins down
						'type'       => 'png',
						// jpg or png (map output type).
					);
					$string .= $this->get_visitor_maps_worldmap( $map_settings );
				} else {
					$string .= '<img alt="' . esc_html__( 'Visitor Maps', 'visitor-maps' ) . '" src="' . get_bloginfo( 'url' ) . '?do_wo_map=1&amp;nonce=' . $nonce . '&amptime=' . Visitor_Maps::$core->get_option( 'default_map_time' ) . '&amp;units=' . Visitor_Maps::$core->get_option( 'default_map_units' ) . '&amp;map=' . Visitor_Maps::$core->get_option( 'default_map' ) . '&amp;pin=1&amp;pins=on&amp;text=on&amp;textcolor=000000&amp;textshadow=FFFFFF&amp;textalign=cb&amp;ul_lat=0&amp;ul_lon=0&amp;lr_lat=360&amp;lr_lon=180&amp;offset_x=0&amp;offset_y=0&amp;type=png&amp;wp-minify-off=1" />';
				}

				if ( ! Visitor_Maps::$core->get_option( 'hide_console' ) || ( Visitor_Maps::$core->get_option( 'hide_console' ) && current_user_can( 'manage_options' ) ) ) {
					$string .= '<p>' . esc_html__( 'View more maps in the ', 'visitor-maps' ) . '<a class="map-console-bottom" href="' . get_bloginfo( 'url' ) . '?wo_map_console=1">' . esc_html__( 'Visitor Map Viewer', 'visitor-maps' ) . '</a></p>';
				}
			}

			if ( Visitor_Maps::$core->get_option( 'enable_records_page' ) ) {
				$wo_table_st = $wpdb->prefix . 'visitor_maps_st';

				// phpcs:disable
				$visitors_arr = $wpdb->get_results( $wpdb->prepare( 'SELECT type, count, time FROM %1$s', $wo_table_st ), ARRAY_A );
				// phpcs:enable

				foreach ( $visitors_arr as $visitors ) {
					if ( 'day' === $visitors['type'] ) {
						$day = esc_html__( 'Max visitors today', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'time_format' ), strtotime( current_time( $visitors['time'] ) ) );
					}

					if ( 'month' === $visitors['type'] ) {
						$month = esc_html__( 'This month', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), strtotime( current_time( $visitors['time'] ) ) );
					}

					if ( 'year' === $visitors['type'] ) {
						$year = esc_html__( 'This year', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), strtotime( current_time( $visitors['time'] ) ) );
					}

					if ( 'all' === $visitors['type'] ) {
						$all = esc_html__( 'All time', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), strtotime( current_time( $visitors['time'] ) ) );
					}
				}

				$string .= '<p>' . esc_html__( 'Records of the most visitors online at once:', 'visitor-maps' );
				$string .= "<br />$day";
				$string .= "<br />$month";
				$string .= "<br />$year";
				$string .= "<br />$all";
				$string .= '</p>';
			}

			if ( Visitor_Maps::$core->get_option( 'enable_credit_link' ) ) {
				$string .= '<p><small>' . esc_html__( 'Powered by', 'visitor-maps' ) . ' <a href="http://requitedesigns.com/visitor-maps/" target="_new">' . esc_html__( 'Visitor Maps', 'visitor-maps' ) . '</a></small></p>';
			}

			return $string;
		}

		/**
		 * Admin options for pages (at top).
		 */
		public function admin_view_header(): void {
			if ( isset( $_GET['page'] ) && 'visitor-maps' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$wo_prefs_arr_def = array(
					'bots'    => '0',
					'refresh' => 'none',
					'show'    => 'none',
				);

				$wo_prefs_arr = get_option( 'visitor_maps_wop' );
				if ( ( ! $wo_prefs_arr ) || ! is_array( $wo_prefs_arr ) ) {
					update_option( 'visitor_maps_wop', $wo_prefs_arr_def );

					$wo_prefs_arr = $wo_prefs_arr_def;
				}

				$bots = ( isset( $wo_prefs_arr['bots'] ) ) ? $wo_prefs_arr['bots'] : '0';

				if ( isset( $_GET['bots'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$get_bots = sanitize_key( $_GET['bots'] ); // phpcs:ignore WordPress.Security.NonceVerification

					if ( in_array( $get_bots, array( '0', '1' ), true ) ) {
						$wo_prefs_arr['bots'] = $get_bots;
						$bots                 = $get_bots;
					}
				}

				$refresh = ( isset( $wo_prefs_arr['refresh'] ) ) ? $wo_prefs_arr['refresh'] : 'none';

				if ( isset( $_GET['refresh'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$get_refresh = sanitize_key( $_GET['refresh'] ); // phpcs:ignore WordPress.Security.NonceVerification

					if ( in_array( $get_refresh, array( 'none', '30', '60', '120', '300', '600' ), true ) ) {
						$wo_prefs_arr['refresh'] = $get_refresh;
						$refresh                 = $get_refresh;
					}
				}

				$show = ( isset( $wo_prefs_arr['show'] ) ) ? $wo_prefs_arr['show'] : 'none';

				if ( isset( $_GET['show'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$get_show = sanitize_key( $_GET['show'] ); // phpcs:ignore WordPress.Security.NonceVerification

					if ( in_array( $get_show, array( 'none', 'all', 'bots', 'guests' ), true ) ) {
						$wo_prefs_arr['show'] = $get_show;
						$show                 = $get_show;
					}
				}

				update_option( 'visitor_maps_wop', $wo_prefs_arr );

				echo '<!-- begin visitor maps - whos online page header code -->' . "\n";

				if ( isset( $wo_prefs_arr['refresh'] ) && in_array(
					$wo_prefs_arr['refresh'],
					array(
						'30',
						'60',
						'120',
						'300',
						'600',
					),
					true
				)
				) {
					$query = '&amp;refresh=' . $wo_prefs_arr['refresh'];

					if ( isset( $wo_prefs_arr['show'] ) && in_array(
						$wo_prefs_arr['show'],
						array(
							'all',
							'bots',
							'guests',
						),
						true
					)
					) {
						$query .= '&amp;show=' . $wo_prefs_arr['show'];
					}

					if ( isset( $wo_prefs_arr['bots'] ) && in_array(
						$wo_prefs_arr['bots'],
						array(
							'0',
							'1',
						),
						true
					)
					) {
						$query .= '&amp;bots=' . $wo_prefs_arr['bots'];
					}

					echo '<meta http-equiv="refresh" content="' . esc_html( $wo_prefs_arr['refresh'] ) . ';URL=' . esc_url( admin_url( 'admin.php?page=visitor-maps' ) ) . $query . '" />'; // phpcs:ignore WordPress.Security.EscapeOutput
				}
			}
		}

		/**
		 * Log visitor activity.
		 */
		public function activity(): void {
			global $visitor_maps_stats;

			$visitor_maps_stats = $this->visitor_maps_activity_do();
		}

		/**
		 * Add stats to admin footer.
		 */
		public function admin_footer_stats(): void {
			global $visitor_maps_stats;

			if ( Visitor_Maps::$core->get_option( 'enable_admin_footer' ) && ( current_user_can( Visitor_Maps::$core->get_option( 'dashboard_permissions' ) ) ) ) {
				echo '<div class="footer" style="text-align:center"><p>';
				echo $visitor_maps_stats . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput
				echo '</div>';
			}
		}

		/**
		 * Add stats to public footer.
		 */
		public function public_footer_stats(): void {
			global $visitor_maps_stats;

			if ( Visitor_Maps::$core->get_option( 'enable_blog_footer' ) ) {
				echo $visitor_maps_stats; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}

		/**
		 * Add Settings link to plugin page entry.
		 *
		 * @param array  $links Array of links.
		 * @param string $file  Plugin filename.
		 *
		 * @return array
		 */
		public function plugin_action_links( array $links, string $file ): array {
			static $this_plugin;

			if ( ! $this_plugin ) {
				$this_plugin = plugin_basename( __FILE__ );
			}

			if ( $file === $this_plugin ) {
				$settings_link = '<a href="admin.php?page=visitor_maps_opt">' . esc_html( esc_html__( 'Settings', 'visitor-maps' ) ) . '</a>';
				array_unshift( $links, $settings_link );
			}

			return $links;
		}

		/**
		 * Init core.
		 */
		public function init(): void {
			load_plugin_textdomain( 'visitor-maps', false, 'visitor-maps/languages' );
		}

		/**
		 * Log activity stats.
		 *
		 * @return string
		 */
		private function visitor_maps_activity_do(): string {
			global $wpdb, $current_user, $user_ID;

			$wo_table_wo = $wpdb->prefix . 'visitor_maps_wo';

			$ip_address    = $this->get_ip_address();
			$last_page_url = $this->get_request_uri();

			$urls_to_ignore = array();
			if ( Visitor_Maps::$core->get_option( 'urls_to_ignore' ) !== '' ) {
				$urls_to_ignore = explode( "\n", Visitor_Maps::$core->get_option( 'urls_to_ignore' ) );

				if ( ! empty( $urls_to_ignore ) && ! empty( $ip_address ) ) {
					foreach ( $urls_to_ignore as $checked_url ) {
						$regexp = trim( $checked_url );

						if ( preg_match( "|$regexp|i", $last_page_url ) ) {
							// ignore this url.
							$ip_address = '';
						}
					}
				}
			}

			$http_referer     = $this->get_http_referer();
			$user_agent       = $this->get_http_user_agent();
			$user_agent_lower = strtolower( $user_agent );
			$current_time     = (int) current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			$xx_mins_ago      = ( $current_time - absint( ( intval( Visitor_Maps::$core->get_option( 'track_time' ) ) * 60 ) ) );

			// see if the user is a spider (bot) or not
			// based on a list of spiders in spiders.txt file.
			$spider_flag = 0;

			$spiders = file( Visitor_Maps::$dir . '/spiders.txt' );

			if ( ! empty( $user_agent_lower ) && is_array( $spiders ) ) {
				for ( $i = 0, $n = count( $spiders ); $i < $n; $i++ ) {
					if ( ! empty( $spiders[ $i ] ) && is_integer( strpos( $user_agent_lower, trim( $spiders[ $i ] ) ) ) ) {
						$spider_flag = $spiders[ $i ];
						break;
					}
				}
			}

			wp_get_current_user();

			$wo_user_id = 0;

			if ( $spider_flag ) {
				// is a bot, the bot name is extracted from the User Agent name later on in the whos-online viewer script.
				$name = $user_agent_lower;
			} elseif ( 0 !== $user_ID && '' !== $current_user->user_login ) {
				// logged in wp user.
				$name       = $current_user->user_login;
				$wo_user_id = $user_ID;
			} else {
				// is not a bot, must be a regular visitor.
				$name = 'Guest';
			}

			// truncate to 64 chars or less.
			$name = substr( $name, 0, 64 );

			if ( Visitor_Maps::$core->get_option( 'store_days' ) > 0 ) {
				// remove visitor entries that have expired after Visitor_Maps::$core->get_option('store_days'), save nickname friends.
				$xx_days_ago_time = ( $current_time - ( Visitor_Maps::$core->get_option( 'store_days' ) * 60 * 60 * 24 ) );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query(
					$wpdb->prepare(
					// phpcs:disable
						'DELETE from ' . $wo_table_wo . "
                        WHERE (time_last_click < %d and nickname = '')
                        OR   (time_last_click < %d and nickname IS NULL)",
						$xx_days_ago_time,
						$xx_days_ago_time
					// phpcs:enable
					)
				);
			} else {
				// remove visitor entries that have expired after Visitor_Maps::$core->get_option('track_time'), save nickname friends.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query(
					$wpdb->prepare(
					// phpcs:disable
						'DELETE from ' . $wo_table_wo . "
                        WHERE (time_last_click < %d and nickname = '')
                        OR   (time_last_click < %d and nickname IS NULL)",
						$xx_mins_ago,
						$xx_mins_ago
					// phpcs:enable
					)
				);
			}

			// see if the current site visitor has an entry.
			// phpcs:disable
			$stored_user = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT ip_address, country_code, nickname, hostname, time_last_click, num_visits 
					FROM ' . $wo_table_wo . ' 
                    WHERE session_id = %s',
					$ip_address
				)
			);
			// phpcs:enable

			if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
				clearstatcache();

				if ( ! file_exists( Visitor_Maps::$upload_dir . Visitor_Maps::DATABASE_NAME . Visitor_Maps::DATABASE_EXT ) ) {
					Redux::setOption( Visitor_Maps::OPT_NAME, 'enable_location_plugin', false );
				}
			}

			// ignore these IPs.
			$ips_to_ignore = array();
			$ips_to_ignore = explode( "\n", Visitor_Maps::$core->get_option( 'ips_to_ignore' ) );

			if ( ! empty( $ips_to_ignore ) && ! empty( $ip_address ) ) {
				foreach ( $ips_to_ignore as $checked_ip ) {
					$regexp = str_replace( '.', '\\.', $checked_ip );
					$regexp = str_replace( '*', '.+', $regexp );
					if ( preg_match( "/^$regexp$/", $ip_address ) ) {

						// phpcs:disable
						$wpdb->query(
							$wpdb->prepare( 'DELETE from ' . $wo_table_wo . ' WHERE ip_address = %s', $ip_address )
						);
						// phpcs:enable

						$ip_address = '';

						break;
					}
				}
			}

			if ( Visitor_Maps::$core->get_option( 'hide_administrators' ) && '' !== $user_ID && current_user_can( 'manage_options' ) ) {
				$ip_address = '';

				// phpcs:disable
				$wpdb->query(
					$wpdb->prepare( 'DELETE from ' . $wo_table_wo . ' WHERE name = %s', $name )
				);
				// phpcs:enable
			}

			if ( '' !== $name && '' !== $ip_address ) { // skip if empty.
				if ( isset( $stored_user ) && '' !== $stored_user->ip_address ) {

					// have an entry, update it.
					$query = 'UPDATE ' . $wo_table_wo . "
                        SET
                        user_id          = '" . esc_sql( $wo_user_id ) . "',
                        name             = '" . esc_sql( $name ) . "',
                        ip_address       = '" . esc_sql( $ip_address ) . "',";

					// sometimes the country is blank, look it up again.
					// this can happen if you just enabled the location plugin.
					if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) && '' === $stored_user->country_code ) {
						$location_info = $this->get_location_info( $ip_address );

						$query .= "country_name = '" . esc_sql( $location_info['country_name'] ) . "',
                            country_code = '" . esc_sql( $location_info['country_code'] ) . "',
                            city_name    = '" . esc_sql( $location_info['city_name'] ) . "',
                            state_name   = '" . esc_sql( $location_info['state_name'] ) . "',
                            state_code   = '" . esc_sql( $location_info['state_code'] ) . "',
                            latitude     = '" . esc_sql( $location_info['latitude'] ) . "',
                            longitude    = '" . esc_sql( $location_info['longitude'] ) . "',";
					}

					// is a nickname user coming back online? then need to re-set the time entry and online time.
					if ( $stored_user->time_last_click < $xx_mins_ago ) {
						$hostname = ( Visitor_Maps::$core->get_option( 'enable_host_lookups' ) ) ? $this->gethostbyaddr_timeout( $ip_address ) : '';
						$query   .= "num_visits       = '" . esc_sql( $stored_user->num_visits + 1 ) . "',
                            time_entry       = '" . esc_sql( $current_time ) . "',
                            time_last_click  = '" . esc_sql( $current_time ) . "',
                            last_page_url    = '" . esc_sql( $last_page_url ) . "',
                            http_referer     = '" . esc_sql( $http_referer ) . "',
                            hostname         = '" . esc_sql( $hostname ) . "',
                            user_agent       = '" . esc_sql( $user_agent ) . "'
                            WHERE session_id = '" . esc_sql( $ip_address ) . "'";
					} else {
						if ( Visitor_Maps::$core->get_option( 'enable_host_lookups' ) ) {
							$hostname = ( empty( $stored_user->hostname ) ) ? $this->gethostbyaddr_timeout( $ip_address ) : $stored_user->hostname;
						} else {
							$hostname = '';
						}

						$query .= "time_last_click  = '" . esc_sql( $current_time ) . "',
                            hostname         = '" . esc_sql( $hostname ) . "',
                            last_page_url    = '" . esc_sql( $last_page_url ) . "'
                            WHERE session_id = '" . esc_sql( $ip_address ) . "'";
					}
				} else {
					if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
						$location_info = $this->get_location_info( $ip_address );
						$country_name  = $location_info['country_name'];
						$country_code  = $location_info['country_code'];
						$city_name     = $location_info['city_name'];
						$state_name    = $location_info['state_name'];
						$state_code    = $location_info['state_code'];
						$latitude      = $location_info['latitude'];
						$longitude     = $location_info['longitude'];
					} else {
						$country_name = '';
						$country_code = '';
						$city_name    = '';
						$state_name   = '';
						$state_code   = '';
						$latitude     = '0.0000';
						$longitude    = '0.0000';
					}

					$hostname = ( Visitor_Maps::$core->get_option( 'enable_host_lookups' ) ) ? $this->gethostbyaddr_timeout( $ip_address ) : '';

					$query = 'INSERT IGNORE INTO ' . $wo_table_wo . "
                        (session_id,
                        ip_address,
                        user_id,
                        name,
                        country_name,
                        country_code,
                        city_name,
                        state_name,
                        state_code,
                        latitude,
                        longitude,
                        last_page_url,
                        http_referer,
                        user_agent,
                        hostname,
                        time_entry,
                        time_last_click,
                        num_visits)
                        values (
                            '" . esc_sql( $ip_address ) . "',
                            '" . esc_sql( $ip_address ) . "',
                            '" . esc_sql( $wo_user_id ) . "',
                            '" . esc_sql( $name ) . "',
                            '" . esc_sql( $country_name ) . "',
                            '" . esc_sql( $country_code ) . "',
                            '" . esc_sql( $city_name ) . "',
                            '" . esc_sql( $state_name ) . "',
                            '" . esc_sql( $state_code ) . "',
                            '" . esc_sql( $latitude ) . "',
                            '" . esc_sql( $longitude ) . "',
                            '" . esc_sql( $last_page_url ) . "',
                            '" . esc_sql( $http_referer ) . "',
                            '" . esc_sql( $user_agent ) . "',
                            '" . esc_sql( $hostname ) . "',
                            '" . esc_sql( $current_time ) . "',
                            '" . esc_sql( $current_time ) . "',
                            '1')";
				}

				// phpcs:ignore WordPress.DB
				$x = $wpdb->query( "$query" );
			}

			$visitors_count = $this->set_whos_records();

			return $this->get_whos_records( $visitors_count );
		}

		/**
		 * Get location info from GeoLiteCity.
		 *
		 * @param string $user_ip Visitor IP address.
		 *
		 * @return array
		 * @throws AddressNotFoundException Address not found.
		 * @throws InvalidDatabaseException Invalid database.
		 */
		public function get_location_info( string $user_ip ): array {
			// lookup country info for this ip.
			// geoip lookup.

			require_once Visitor_Maps::$dir . 'vendor/autoload.php';

			$reader = new Reader( Visitor_Maps::$upload_dir . Visitor_Maps::DATABASE_NAME . Visitor_Maps::DATABASE_EXT );

			if ( '127.0.0.1' !== $user_ip ) {
				$record = $reader->city( $user_ip );
			}

			$location_info = array();

			$location_info['provider']     = '';
			$location_info['city_name']    = ( isset( $record->city->name ) ) ? $record->city->name : '-';
			$location_info['state_name']   = ( isset( $record->mostSpecificSubdivision->name ) ) ? $record->mostSpecificSubdivision->name : '-'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$location_info['state_code']   = ( isset( $record->mostSpecificSubdivision->isoCode ) ) ? strtoupper( $record->mostSpecificSubdivision->isoCode ) : '-'; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
			$location_info['country_name'] = ( isset( $record->country->name ) ) ? $record->country->name : '-';
			$location_info['country_code'] = ( isset( $record->country->isoCode ) ) ? strtoupper( $record->country->isoCode ) : '-';
			$location_info['latitude']     = ( isset( $record->location->latitude ) ) ? $record->location->latitude : '-';
			$location_info['longitude']    = ( isset( $record->location->longitude ) ) ? $record->location->longitude : '-';

			if ( strtolower( get_option( 'blog_charset' ) ) === 'utf-8' && function_exists( 'utf8_encode' ) ) {
				if ( '' !== $location_info['city_name'] ) {
					$location_info['city_name'] = mb_convert_encoding( $location_info['city_name'], 'UTF-8', 'ISO-8859-1' );
				}

				if ( '' !== $location_info['state_name'] ) {
					$location_info['state_name'] = mb_convert_encoding( $location_info['state_name'], 'UTF-8', 'ISO-8859-1' );
				}

				if ( '' !== $location_info['country_name'] ) {
					$location_info['country_name'] = mb_convert_encoding( $location_info['country_name'], 'UTF-8', 'ISO-8859-1' );
				}
			}

			return $location_info;
		}

		/**
		 * Set who's visiting record.
		 *
		 * @return string|null
		 */
		private function set_whos_records(): ?string {
			global $wpdb;

			$wo_table_wo = $wpdb->prefix . 'visitor_maps_wo';
			$wo_table_st = $wpdb->prefix . 'visitor_maps_st';
			$wo_table_ge = $wpdb->prefix . 'visitor_maps_ge';

			$mysql_now    = current_time( 'mysql' );
			$current_time = (int) current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			$query_time   = ( $current_time - absint( ( intval( Visitor_Maps::$core->get_option( 'track_time' ) ) * 60 ) ) );

			if ( Visitor_Maps::$core->get_option( 'hide_bots' ) ) {

				// phpcs:disable
				// select the 'visitors online now' count, except for bots and our nickname friends not online now
				$visitors_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT count(*) FROM ' . $wo_table_wo . "
                        WHERE (name = 'Guest' AND time_last_click > %d)
                        OR (user_id > '0' AND time_last_click > %d)",
						$query_time,
						$query_time
					)
				);
			} else {
				// select the 'visitors online now' count, all users.
				$visitors_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT count(*) FROM ' . $wo_table_wo . '
                        WHERE time_last_click > %d',
						$query_time
					)
				);
			}

			// set today record if day changes or count is higher than stored count
			$wpdb->query(
				$x = $wpdb->prepare(
					'UPDATE ' . $wo_table_st . "
                    SET
                    count = %d,
                    time = %d
                    WHERE (day(%d) != day(time) AND type = 'day')
                    OR (count < %d AND type = 'day')",
					absint( $visitors_count ),
					$mysql_now,
					$mysql_now,
					absint( $visitors_count )
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					'UPDATE ' . $wo_table_st . "
                    SET
                    count = %d,
                    time = %d
                    WHERE (month(%d) != month(time) AND type = 'month')
                    OR (count < %d AND type = 'month')",
					absint( $visitors_count ),
					$mysql_now,
					$mysql_now,
					absint( $visitors_count )
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					'UPDATE ' . $wo_table_st . "
                    SET
                    count = %d,
                    time = %d
                    WHERE (year(%d) != year(time) AND type = 'year')
                    OR (count < %d AND type = 'year')",
					absint( $visitors_count ),
					$mysql_now,
					$mysql_now,
					absint( $visitors_count )
				)
			);

			$wpdb->query(
				$wpdb->prepare(
					'UPDATE ' . $wo_table_st . "
                    SET
                    count = %d,
                    time = %d
                    WHERE count < %d
                    AND type = 'all'",
					absint( $visitors_count ),
					$mysql_now,
					absint( $visitors_count )
				)
			);
			// phpcs:enable

			return $visitors_count;
		}

		/**
		 * Get who's visited record.
		 *
		 * @param int $visitors_count Visitor Count.
		 *
		 * @return string
		 */
		private function get_whos_records( int $visitors_count ): string {
			// get the day, month, year, all time records for display on website,
			// use the recycled the 'visitors online now' count.
			global $visitor_maps_stats, $wpdb;

			$wo_table_st  = $wpdb->prefix . 'visitor_maps_st';
			$wo_table_wo  = $wpdb->prefix . 'visitor_maps_wo';
			$current_time = (int) current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp

			$query_time = ( $current_time - absint( ( intval( Visitor_Maps::$core->get_option( 'track_time' ) ) * 60 ) ) );

			// phpcs:disable
			$guests_count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT count(*) FROM ' . $wo_table_wo . "
					WHERE user_id = '0' 
					AND name = 'Guest' 
					AND time_last_click > %d",
					$query_time
				)
			);

			if ( ! Visitor_Maps::$core->get_option( 'hide_bots' ) ) {
				$bots_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT count(*) FROM ' . $wo_table_wo . "
                        WHERE user_id = '0' 
                        AND name != 'Guest' 
                        AND time_last_click > %d",
						$query_time
					)
				);
			}

			$members_count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT count(*) FROM ' . $wo_table_wo . "
                    WHERE user_id > '0' 
                    AND time_last_click > %d",
					$query_time
				)
			);
			// phpcs:enable

			// translators: %d: Visitors count.
			$visitor_maps_stats['visitors'] = sprintf( esc_html__( '%d visitors online now', 'visitor-maps' ), $visitors_count );

			// translators: %d: Guest count.
			$visitor_maps_stats['guests'] = sprintf( esc_html__( '%d guests', 'visitor-maps' ), $guests_count );

			if ( ! Visitor_Maps::$core->get_option( 'hide_bots' ) ) {
				// translators: %d: Bot count.
				$visitor_maps_stats['bots'] = sprintf( esc_html__( '%d bots', 'visitor-maps' ), $bots_count );
			}

			// translators: %d: Members count.
			$visitor_maps_stats['members'] = sprintf( esc_html__( '%d members', 'visitor-maps' ), $members_count );
			$string                        = $visitor_maps_stats['visitors'] . '<br />';
			$string                       .= $visitor_maps_stats['guests'] . ', ';

			if ( ! Visitor_Maps::$core->get_option( 'hide_bots' ) ) {
				$string .= $visitor_maps_stats['bots'] . ', ';
			}

			$string .= $visitor_maps_stats['members'] . '<br />';

			// fetch the day, month, year, all time records.
			// phpcs:disable
			$visitors_arr = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT type, count, time FROM %1$s',
					$wo_table_st
				),
				ARRAY_A
			);
			// phpcs:enable

			foreach ( $visitors_arr as $visitors ) {
				if ( 'day' === $visitors['type'] ) {
					$visitor_maps_stats['today'] = esc_html__( 'Max visitors today', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'time_format' ), strtotime( current_time( $visitors['time'] ) ) );
					$string                     .= esc_html__( 'Max visitors today', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'time_format' ), strtotime( current_time( $visitors['time'] ) ) ) . '<br />';
				}

				if ( 'month' === $visitors['type'] ) {
					$visitor_maps_stats['month'] = esc_html__( 'This month', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), strtotime( current_time( $visitors['time'] ) ) );
					$string                     .= esc_html__( 'This month', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), strtotime( current_time( $visitors['time'] ) ) ) . '<br />';
				}

				if ( 'year' === $visitors['type'] ) {
					$visitor_maps_stats['year'] = esc_html__( 'This year', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), strtotime( current_time( $visitors['time'] ) ) );
					$string                    .= esc_html__( 'This year', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), strtotime( current_time( $visitors['time'] ) ) ) . '<br />';
				}

				if ( 'all' === $visitors['type'] ) {
					$visitor_maps_stats['all'] = esc_html__( 'All time', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), strtotime( current_time( $visitors['time'] ) ) );
					$string                   .= esc_html__( 'All time', 'visitor-maps' ) . ': ' . $visitors['count'] . ' ' . esc_html__( 'at', 'visitor-maps' ) . ' ' . gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), strtotime( current_time( $visitors['time'] ) ) ) . '<br />';
				}
			}

			return $string;
		}

		/**
		 * Get World map.
		 *
		 * @param array $ms Map settings.
		 *
		 * @return string
		 */
		public function get_visitor_maps_worldmap( array $ms = array() ): string { //phpcs:ignore
			global $wpdb;

			require_once Visitor_Maps::$dir . '/visitor-maps-worldmap.php';

			return $string;
		}

		/**
		 * Find Y coordinates.
		 *
		 * @param float $myLat Latitude.
		 * @param float $lr_lat LR Latitude.
		 * @param int   $mapHeight Map height.
		 * @param int   $rfactor Factor.
		 *
		 * @return float
		 */

		/**
		 * Get request URL.
		 *
		 * @return string
		 */
		private function get_request_uri(): string {
			if ( isset( $_SERVER['REQUEST_URI'] ) ) {
				$uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
			} elseif ( isset( $_SERVER['PHP_SELF'] ) ) {
				if ( isset( $_SERVER['argv'][0] ) ) {
					$uri = sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) . '?' . sanitize_text_field( wp_unslash( $_SERVER['argv'][0] ) );
				} elseif ( isset( $_SERVER['QUERY_STRING'] ) ) {
					$uri = sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) . '?' . sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
				}
			}

			return $uri;
		}

		/**
		 * Get IP Address.
		 *
		 * @return string
		 */
		public function get_ip_address(): string {
			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			} else {
				$ip = 'unknown';
			}

			return $ip;
		}

		/**
		 * Get HTTP USer Agent.
		 *
		 * @return array|bool|string
		 */
		private function get_http_user_agent(): bool|array|string {
			if ( getenv( 'HTTP_USER_AGENT' ) ) {
				$agent = getenv( 'HTTP_USER_AGENT' );
			} elseif ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
				$agent = sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) );
			} else {
				$agent = 'unknown';
			}

			return $agent;
		}

		/**
		 * Get HTTP Referer.
		 *
		 * @return array|bool|string
		 */
		private function get_http_referer(): bool|array|string {
			if ( getenv( 'HTTP_REFERER' ) ) {
				$referer = getenv( 'HTTP_REFERER' );
			} elseif ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$referer = sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
			} else {
				$referer = '';
			}

			return $referer;
		}

		/**
		 * Validate Color.
		 *
		 * @param string $str Color string.
		 *
		 * @return bool
		 */
		public function validate_color_wo( string $str ): bool {
			if ( preg_match( '/^#[a-f0-9]{6}$/i', trim( $str ) ) ) {
				return true;
			}

			if ( preg_match( '/^[a-f0-9]{6}$/i', trim( $str ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Validate text align.
		 *
		 * @param string $str Text to check.
		 *
		 * @return bool
		 */
		public function validate_text_align( string $str ): bool {
			$allowed = array( 'll', 'ul', 'lr', 'ur', 'c', 'ct', 'cb' );

			if ( in_array( $str, $allowed, true ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Host to domain.
		 *
		 * @param string $host Hotname.
		 *
		 * @return string
		 */
		public function host_to_domain( string $host ): string {
			if ( 'n/a' === $host || ! preg_match( '/\.[a-zA-Z]{2,3}/', $host ) ) {
				return $host;
			}

			$isp    = array_reverse( explode( '.', $host ) );
			$domain = $isp[1] . '.' . $isp[0];
			$slds   = array(
				'\.com\.au',
				'\.net\.au',
				'\.org\.au',
				'\.on\.net',
				'\.ac\.uk',
				'\.co\.uk',
				'\.gov\.uk',
				'\.ltd\.uk',
				'\.me\.uk',
				'\.mod\.uk',
				'\.net\.uk',
				'\.nic\.uk',
				'\.nhs\.uk',
				'\.org\.uk',
				'\.plc\.uk',
				'\.police\.uk',
				'\.sch\.uk',
			);

			foreach ( $slds as $k ) {
				if ( preg_match( "/$k$/i", $host ) ) {
					$domain = $isp[2] . '.' . $isp[1] . '.' . $isp[0];
					break;
				}
			}

			return ( preg_match( '/[0-9]{1,3}\.[0-9]{1,3}/', $domain ) ) ? 'n/a' : $domain;
		}

		/**
		 * Get host by address timeout.
		 *
		 * @param string $ip           WP address.
		 * @param int    $timeout_secs Seconds.
		 *
		 * @return string
		 */
		private function gethostbyaddr_timeout( string $ip, int $timeout_secs = 2 ): string {
			if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
				return $this->gethost_win( $ip, $timeout_secs );
			} else {
				return $this->gethost_lin( $ip, $timeout_secs );
			}
		}

		/**
		 * Get host command line.
		 *
		 * @param string $ip           IP address.
		 * @param int    $timeout_secs Seconds.
		 *
		 * @return string
		 */
		private function gethost_lin( string $ip, int $timeout_secs = 2 ): string {
			$time_start = microtime( true ); // set a timer.

			// phpcs:ignore WordPress.PHP
			@exec( 'host -W ' . escapeshellarg( $timeout_secs ) . ' ' . escapeshellarg( $ip ), $output ); // plan a.
			$time_end = microtime( true );  // check the timer.

			if ( ( $time_end - $time_start ) > $timeout_secs ) {
				return 'n/a'; // bail because it timed out.
			}

			if ( empty( $output ) ) {
				return gethostbyaddr( $ip ); // plan b, but without timeout.
			}

			if ( isset( $output[0] ) ) {
				$array = explode( ' ', $output[0] );
				$host  = end( $array );
			} else {
				$host = $ip;
			}

			$host = rtrim( $host, "\n" );
			$host = rtrim( $host, '.' );

			return ( preg_match( '/\.[a-zA-Z]{2,3}/', $host ) ) ? $host : 'n/a';
		}

		/**
		 * Get host Windows.
		 *
		 * @param string $ip           IP address.
		 * @param int    $timeout_secs Seconds.
		 *
		 * @return string
		 */
		private function gethost_win( string $ip, int $timeout_secs = 2 ): string {
			$time_start = microtime( true ); // set a timer.

			// phpcs:ignore WordPress.PHP
			@exec( 'nslookup -timeout=' . escapeshellarg( $timeout_secs ) . ' ' . escapeshellarg( $ip ), $output ); // plan a.
			$time_end = microtime( true );  // check the timer.

			if ( ( $time_end - $time_start ) > $timeout_secs ) {
				return 'n/a'; // bail because it timed out.
			}

			if ( empty( $output ) ) {
				return gethostbyaddr( $ip ); // plan b, but without timeout.
			}

			foreach ( $output as $line ) { // plan a continues.
				if ( preg_match( '/^Name:\s+(.*)$/', $line, $parts ) ) {
					$host = trim( ( isset( $parts[1] ) ) ? $parts[1] : '' );

					return ( preg_match( '/\.[a-zA-Z]{2,3}/', $host ) ) ? $host : 'n/a';
				}
			}

			return 'n/a';
		}

		/**
		 * Dashboard widget.
		 */
		public function dashboard_widget(): void {
			if ( current_user_can( Visitor_Maps::$core->get_option( 'dashboard_permissions' ) ) ) {
				wp_add_dashboard_widget(
					'visitor_maps_dashboard_widget',
					esc_html__( 'Visitor Maps', 'visitor-maps' ) . ' - ' . esc_html__( "Who's Online", 'visitor-maps' ),
					array(
						&$this,
						'visitor_maps_dashboard_widget',
					)
				);
			}
		}

		/**
		 * Map dashboard widget.
		 */
		public function visitor_maps_dashboard_widget(): void {
			global $visitor_maps_stats;

			echo '<p>' . $visitor_maps_stats . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput

			if ( Visitor_Maps::$core->get_option( 'enable_credit_link' ) ) {
				echo '<p><small>' . esc_html__( 'Powered by', 'visitor-maps' ) . ' <a href="http://www.svlstudios.com/" target="_new">' . esc_html__( 'Visitor Maps', 'visitor-maps' ) . '</a></small></p>';
			}
		}

		/**
		 * Register widget.
		 */
		public function register_widget(): void {
			wp_register_sidebar_widget(
				'visitor-maps',
				esc_html__( "Who's Online", 'visitor-maps' ),
				array(
					$this,
					'visitor_maps_widget',
				)
			);
		}

		/**
		 * Visitor Maps widget.
		 *
		 * @param array $args Widget arguments.
		 */
		public function visitor_maps_widget( array $args ): void {
			extract( $args ); // phpcs:ignore WordPress.PHP.DontExtract

			echo esc_html( $before_widget ) . esc_html( $before_title ) . esc_html__( "Who's Online", 'visitor-maps' ) . esc_html( $after_title );
			$this->visitor_maps_widget_content();
			echo esc_html( $after_widget );
		}

		/**
		 * Visitor Maps Sidebar.
		 */
		public function visitor_maps_manual_sidebar(): void {
			echo '<h2>' . esc_html__( "Who's Online", 'visitor-maps' ) . '</h2>';

			$this->visitor_maps_widget_content();
		}

		/**
		 * Widget content.
		 */
		private function visitor_maps_widget_content(): void {
			global $visitor_maps_stats, $wpdb;

			$wo_table_wo  = $wpdb->prefix . 'visitor_maps_wo';
			$current_time = (int) current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			$query_time   = ( $current_time - absint( ( Visitor_Maps::$core->get_option( 'track_time' ) * 60 ) ) );

			if ( Visitor_Maps::$core->get_option( 'hide_bots' ) ) {
				// phpcs:disable
				$visitors_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT count(*) FROM ' . $wo_table_wo . "
                        WHERE (name = 'Guest' AND time_last_click > %d)
                        OR (user_id > '0' AND time_last_click > %d)",
						$query_time,
						$query_time
					)
				);

				$guests_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT count(*) FROM ' . $wo_table_wo . "
                        WHERE user_id = '0' AND name = 'Guest' AND time_last_click > %d",
						$query_time
					)
				);
			} else {
				$visitors_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT count(*) FROM ' . $wo_table_wo . '
                        WHERE time_last_click > %d',
						$query_time
					)
				);

				$guests_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT count(*) FROM ' . $wo_table_wo . "
                        WHERE user_id = '0' AND name = 'Guest' AND time_last_click > %d",
						$query_time
					)
				);

				$bots_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT count(*) FROM ' . $wo_table_wo . "
                        WHERE user_id = '0' AND name != 'Guest' AND time_last_click > %d",
						$query_time
					)
				);
			}

			$members_count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT count(*) FROM ' . $wo_table_wo . "
                    WHERE user_id > '0' AND time_last_click > %d",
					$query_time
				)
			);
			// phpcs:enable

			// translators: %d: Visitors count.
			$stats_visitors = sprintf( esc_html__( '%d visitors online now', 'visitor-maps' ), $visitors_count );

			// translators: %d: Guests count.
			$stats_guests = sprintf( esc_html__( '%d guests', 'visitor-maps' ), $guests_count );

			// translators: %d: Members count.
			$stats_members = sprintf( esc_html__( '%d members', 'visitor-maps' ), $members_count );

			if ( ! Visitor_Maps::$core->get_option( 'hide_bots' ) ) {
				// translators: %d: Bots count.
				$stats_bots = sprintf( esc_html__( '%d bots', 'visitor-maps' ), $bots_count );

				if ( ! Visitor_Maps::$core->get_option( 'combine_members' ) ) {
					echo '<div>' . esc_html( $stats_visitors ) . '</div><div><span style=\"white-space:nowrap\">' . esc_html( $stats_guests ) . ',</span> <span style=\"white-space:nowrap\">' . esc_html( $stats_bots ) . ',</span> <span style=\"white-space:nowrap\">' . esc_html( $stats_members ) . '</span>';
				} else {
					// translators: %d: Guest & Member count.
					$stats_guests = sprintf( esc_html__( '%d guests', 'visitor-maps' ), ( $guests_count + $members_count ) );
					echo '<div>' . esc_html( $stats_visitors ) . '</div><div><span style=\"white-space:nowrap\">' . esc_html( $stats_guests ) . ',</span> <span style=\"white-space:nowrap\">' . esc_html( $stats_bots ) . '</span>';
				}
			} elseif ( ! Visitor_Maps::$core->get_option( 'combine_members' ) ) {
					echo '<div>' . esc_html( $stats_visitors ) . '</div><div><span style=\"white-space:nowrap\">' . esc_html( $stats_guests ) . ',</span> <span style=\"white-space:nowrap\">' . esc_html( $stats_members ) . '</span>';
			} else {
				echo '<div>' . esc_html( $stats_visitors );
			}

			if ( Visitor_Maps::$core->get_option( 'enable_widget_link' ) && Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
				if ( ! Visitor_Maps::$core->get_option( 'hide_console' ) || ( Visitor_Maps::$core->get_option( 'hide_console' ) && current_user_can( 'manage_options' ) ) ) {
					echo '</div><div><a id="visitor-maps-link" onclick="wo_map_console(this.href); return false;" href="' . esc_url( get_bloginfo( 'url' ) ) . '">' . esc_html__( 'Map of Visitors</a>', 'visitor-maps' ) . '?wo_map_console=1>';
				}
			}

			if ( Visitor_Maps::$core->get_option( 'enable_credit_link' ) ) {
				echo '</div><div><small>' . esc_html__( 'Powered by', 'visitor-maps' ) . ' <a href="http://wordpress.org/extend/plugins/visitor-maps/">' . esc_html__( 'Visitor Maps', 'visitor-maps' ) . '</a></small>';
			}

			echo '</div>';
		}

		/**
		 * Get pluygin option.
		 *
		 * @param string $option Option key.
		 * @param string $def    Default value.
		 *
		 * @return string
		 */
		public function get_option( string $option, string $def = '' ): string {
			global $visitor_maps_opt;

			if ( isset( $visitor_maps_opt[ $option ] ) ) {
				return $visitor_maps_opt[ $option ];
			} else {
				return $def;
			}
		}
	}
}
