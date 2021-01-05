<?php
/**
 * Who's Online View Maps Class
 *
 * @class Whos_Online_View_Maps
 * @version 2.0.0
 * @package Visitor Maps
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Whos_Online_View_Maps' ) ) {

	/**
	 * Class Whos_Online_View_Maps
	 */
	class Whos_Online_View_Maps {


		/**
		 * Set.
		 *
		 * @var array
		 */
		private $set = array();

		/**
		 * GVar.
		 *
		 * @var array
		 */
		private $gvar = array();

		/**
		 * Display Map.
		 *
		 * @return string
		 */
		public function display_map() {
			global $wpdb;

			$wo_table_wo = $wpdb->prefix . 'visitor_maps_wo';

			if ( ! Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
				return '<p>get_visitor_maps_worldmap ' . esc_html__( 'error: geolocation data not enabled or installed', 'visitor-maps' ) . '</p>';
			}

			// worldmap image names (also set in class-wo-map-page.php and visitor-maps.php)
			// just image names only, do not add any paths.
			$this->set['image_worldmap']    = 'wo-worldmap-smallest.jpg'; // World (smallest) do not delete this one, it is the default.
			$this->set['image_worldmap_1']  = 'wo-worldmap-smallest.jpg'; // World (smallest) do not delete this one, it is the default.
			$this->set['image_worldmap_2']  = 'wo-worldmap-small.jpg';   // World (small).
			$this->set['image_worldmap_3']  = 'wo-worldmap-medium.jpg';  // World (medium).
			$this->set['image_worldmap_4']  = 'wo-worldmap-large.jpg';   // World (large).
			$this->set['image_worldmap_5']  = 'wo-us-black-map.png';     // US (black).
			$this->set['image_worldmap_6']  = 'wo-us-brown-map.png';     // US (brown).
			$this->set['image_worldmap_7']  = 'wo-akus-black-map.png';   // Canada and US (black).
			$this->set['image_worldmap_8']  = 'wo-akus-brown-map.png';   // Canada and US (brown).
			$this->set['image_worldmap_9']  = 'wo-asia-black-map.png';   // Asia (black).
			$this->set['image_worldmap_10'] = 'wo-asia-brown-map.png';   // Asia (brown).
			$this->set['image_worldmap_11'] = 'wo-aus-nz-black-map.png'; // Australia and NZ (black).
			$this->set['image_worldmap_12'] = 'wo-aus-nz-brown-map.png'; // Australia and NZ (brown).
			$this->set['image_worldmap_13'] = 'wo-ceu-black-map.png';    // Europe Central (black).
			$this->set['image_worldmap_14'] = 'wo-ceu-brown-map.png';    // Europe Central (brown).
			$this->set['image_worldmap_15'] = 'wo-eu-black-map.png';     // Europe (black).
			$this->set['image_worldmap_16'] = 'wo-eu-brown-map.png';     // Europe (brown).
			$this->set['image_worldmap_17'] = 'wo-scan-black-map.png';    // Scandinavia (black).
			$this->set['image_worldmap_18'] = 'wo-scan-brown-map.png';    // Scandinavia (brown).
			$this->set['image_worldmap_19'] = 'wo-gb-black-map.png';     // Great Britain (black).
			$this->set['image_worldmap_20'] = 'wo-gb-brown-map.png';     // Great Britain (brown).
			$this->set['image_worldmap_21'] = 'wo-mwus-black-map.png';   // US Midwest (black).
			$this->set['image_worldmap_22'] = 'wo-mwus-brown-map.png';   // US Midwest (brown).
			$this->set['image_worldmap_23'] = 'wo-ncus-black-map.png';   // US Upper Midwest (black).
			$this->set['image_worldmap_24'] = 'wo-ncus-brown-map.png';   // US Upper Midwest (brown).
			$this->set['image_worldmap_25'] = 'wo-neus-black-map.png';   // US Northeast (black).
			$this->set['image_worldmap_26'] = 'wo-neus-brown-map.png';   // US Northeast (brown).
			$this->set['image_worldmap_27'] = 'wo-nwus-black-map.png';   // US Northwest (black).
			$this->set['image_worldmap_28'] = 'wo-nwus-brown-map.png';   // US Northwest (brown).
			$this->set['image_worldmap_29'] = 'wo-rmus-black-map.png';   // US Rocky Mountain (black).
			$this->set['image_worldmap_30'] = 'wo-rmus-brown-map.png';   // US Rocky Mountain (brown).
			$this->set['image_worldmap_31'] = 'wo-scus-black-map.png';   // US South (black).
			$this->set['image_worldmap_32'] = 'wo-scus-brown-map.png';   // US South (brown).
			$this->set['image_worldmap_33'] = 'wo-seus-black-map.png';   // US Southeast (black).
			$this->set['image_worldmap_34'] = 'wo-seus-brown-map.png';   // US Southeast (brown).
			$this->set['image_worldmap_35'] = 'wo-swus-black-map.png';   // US Southwest (black).
			$this->set['image_worldmap_36'] = 'wo-swus-brown-map.png';   // US Southwest (brown).
			$this->set['image_worldmap_37'] = 'wo-es-pt-black-map.png';   // Spain/Portugal (black).
			$this->set['image_worldmap_38'] = 'wo-es-pt-brown-map.png';   // Spain/Portugal (brown).
			$this->set['image_worldmap_39'] = 'wo-finland-black-map.png';   // Finland (black).
			$this->set['image_worldmap_40'] = 'wo-finland-brown-map.png';   // Finland (brown).
			$this->set['image_worldmap_41'] = 'wo-finland-yellow-map.png';  // Finland (yellow).
			$this->set['image_worldmap_42'] = 'wo-jp-black-map.png';   // Japan (black).
			$this->set['image_worldmap_43'] = 'wo-jp-brown-map.png';   // Japan (brown).
			$this->set['image_worldmap_44'] = 'wo-nl-black-map.png';   // Netherlands (black).
			$this->set['image_worldmap_45'] = 'wo-nl-brown-map.png';   // Netherlands (brown).
			$this->set['image_worldmap_46'] = 'wo-br-black-map.png';   // Brazil (black).
			$this->set['image_worldmap_47'] = 'wo-br-brown-map.png';   // Brazil (brown).
			// you can add more, just increment the numbers.

			$this->set['image_pin']   = 'wo-pin.jpg'; // do not delete this one, it is the default.
			$this->set['image_pin_1'] = 'wo-pin.jpg'; // do not delete this one, it is the default.
			$this->set['image_pin_2'] = 'wo-pin5x5.png';
			$this->set['image_pin_3'] = 'wo-pin-green5x5.jpg';

			if ( ! isset( $_GET['nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'do_wo_map' ) ) {
				return;
			}

			// you can add more, just increment the numbers
			// set lat lon coordinates for worldmaps and custom regional maps.
			$ul_lat = 0;
			$ul_lon = 0;
			$lr_lat = 360;
			$lr_lon = 180; // default worldmap.
			if ( isset( $_GET['ul_lat'] ) && is_numeric( $_GET['ul_lat'] ) ) {
				$ul_lat = sanitize_text_field( wp_unslash( $_GET['ul_lat'] ) );
			}

			if ( isset( $_GET['ul_lon'] ) && is_numeric( $_GET['ul_lon'] ) ) {
				$ul_lon = sanitize_text_field( wp_unslash( $_GET['ul_lon'] ) );
			}

			if ( isset( $_GET['lr_lat'] ) && is_numeric( $_GET['lr_lat'] ) ) {
				$lr_lat = sanitize_text_field( wp_unslash( $_GET['lr_lat'] ) );
			}

			if ( isset( $_GET['lr_lon'] ) && is_numeric( $_GET['lr_lon'] ) ) {
				$lr_lon = sanitize_text_field( wp_unslash( $_GET['lr_lon'] ) );
			}

			$offset_x = 0;
			$offset_y = 0;

			if ( isset( $_GET['offset_x'] ) && is_numeric( $_GET['offset_x'] ) ) {
				$offset_x = floor( sanitize_text_field( wp_unslash( $_GET['offset_x'] ) ) );
			}

			if ( isset( $_GET['offset_y'] ) && is_numeric( $_GET['offset_y'] ) ) {
				$offset_y = floor( sanitize_text_field( wp_unslash( $_GET['offset_y'] ) ) );
			}

			// select text on or off.
			$this->gvar['text_display'] = false; // default.
			if ( isset( $_GET['text'] ) && 'on' === sanitize_text_field( wp_unslash( $_GET['text'] ) ) ) {
				$this->gvar['text_display'] = true;
			}

			// select text align.
			$this->gvar['text_align'] = 'cb'; // default center bottom.
			if ( isset( $_GET['textalign'] ) && Visitor_Maps::$core->validate_text_align( sanitize_text_field( wp_unslash( $_GET['textalign'] ) ) ) ) {
				$this->gvar['text_align'] = sanitize_text_field( wp_unslash( $_GET['textalign'] ) );
			}

			// select text color by hex code.
			$this->gvar['text_color'] = '800000'; // default blue.
			if ( isset( $_GET['textcolor'] ) && Visitor_Maps::$core->validate_color_wo( sanitize_text_field( wp_unslash( $_GET['textcolor'] ) ) ) ) {
				$this->gvar['text_color'] = str_replace( '#', '', sanitize_text_field( wp_unslash( $_GET['textcolor'] ) ) );  // hex.
			}

			// select text shadow color by hex code.
			$this->gvar['text_shadow_color'] = 'C0C0C0'; // default white.
			if ( isset( $_GET['textshadow'] ) && Visitor_Maps::$core->validate_color_wo( sanitize_text_field( wp_unslash( $_GET['textshadow'] ) ) ) ) {
				$this->gvar['text_shadow_color'] = str_replace( '#', '', sanitize_text_field( wp_unslash( $_GET['textshadow'] ) ) );  // hex.
			}

			// select pins on or off.
			$this->gvar['pins_display'] = true;  // default.
			if ( isset( $_GET['pins'] ) && 'off' === sanitize_text_field( wp_unslash( $_GET['pins'] ) ) ) {
				$this->gvar['pins_display'] = false;
			}

			// select time units.
			if ( isset( $_GET['time'] ) && is_numeric( $_GET['time'] ) && isset( $_GET['units'] ) ) {
				$time           = floor( sanitize_text_field( wp_unslash( $_GET['time'] ) ) );
				$units          = sanitize_text_field( wp_unslash( $_GET['units'] ) );
				$units_filtered = '';

				if ( $time > 0 && ( 'minute' === $units || 'minutes' === $units ) ) {
					$seconds_ago    = ( $time * 60 ); // minutes.
					$units_filtered = $units;
					$units_lang     = esc_html__( 'minutes', 'visitor-maps' );
				} elseif ( $time > 0 && ( 'hour' === $units || 'hours' === $units ) ) {
					$seconds_ago    = ( $time * 60 * 60 ); // hours.
					$units_filtered = $units;
					$units_lang     = esc_html__( 'hours', 'visitor-maps' );
				} elseif ( $time > 0 && ( 'day' === $units || 'days' === $units ) ) {
					$seconds_ago    = ( $time * 60 * 60 * 24 ); // days.
					$units_filtered = $units;
					$units_lang     = esc_html__( 'days', 'visitor-maps' );
				} else {
					$seconds_ago = absint( Visitor_Maps::$core->get_option( 'track_time' ) * 60 ); // default.
				}
			} else {
				$seconds_ago = absint( Visitor_Maps::$core->get_option( 'track_time' ) * 60 ); // default.
			}

			// select map image.
			$image_worldmap_path = Visitor_Maps::$dir . 'img/maps/' . $this->set['image_worldmap'];  // default.

			if ( isset( $_GET['map'] ) && is_numeric( $_GET['map'] ) ) {
				$image_worldmap_path = Visitor_Maps::$dir . 'img/maps/' . $this->set[ 'image_worldmap_' . floor( sanitize_text_field( wp_unslash( $_GET['map'] ) ) ) ];

				if ( ! file_exists( Visitor_Maps::$dir . 'img/maps/' . $this->set[ 'image_worldmap_' . floor( sanitize_text_field( wp_unslash( $_GET['map'] ) ) ) ] ) ) {
					$image_worldmap_path = Visitor_Maps::$dir . 'img/maps/' . $this->set['image_worldmap'];  // default.
				}
			}

			// select pin image.
			$image_pin_path = Visitor_Maps::$dir . 'img/maps/' . $this->set['image_pin'];  // default.

			if ( isset( $_GET['pin'] ) && is_numeric( $_GET['pin'] ) ) {
				$image_pin_path = Visitor_Maps::$dir . 'img/maps/' . $this->set[ 'image_pin_' . floor( sanitize_text_field( wp_unslash( $_GET['pin'] ) ) ) ];
				if ( ! file_exists( Visitor_Maps::$dir . 'img/maps/' . $this->set[ 'image_pin_' . floor( sanitize_text_field( wp_unslash( $_GET['pin'] ) ) ) ] ) ) {
					$image_pin_path = Visitor_Maps::$dir . 'img/maps/' . $this->set['image_pin'];  // default.
				}
			}

			// get image data.
			list( $image_worldmap_width, $image_worldmap_height, $image_worldmap_type ) = getimagesize( $image_worldmap_path );
			list( $image_pin_width, $image_pin_height, $image_pin_type )                = getimagesize( $image_pin_path );

			switch ( $image_worldmap_type ) {
				case '1':
					$map_im = imagecreatefromgif( "$image_worldmap_path" );
					break;

				case '2':
					$map_im = imagecreatefromjpeg( "$image_worldmap_path" );
					break;

				case '3':
					$map_im = imagecreatefrompng( "$image_worldmap_path" );
					break;
			}

			switch ( $image_pin_type ) {
				case '1':
					$pin_im = imagecreatefromgif( "$image_pin_path" );
					break;
				case '2':
					$pin_im              = imagecreatefromjpeg( "$image_pin_path" );
					$image_pin_path_user = str_replace( '.jpg', '-user.jpg', $image_pin_path );
					$pin_im_user         = imagecreatefromjpeg( "$image_pin_path_user" );
					$image_pin_path_bot  = str_replace( '.jpg', '-bot.jpg', $image_pin_path );
					$pin_im_bot          = imagecreatefromjpeg( "$image_pin_path_bot" );
					break;
				case '3':
					$pin_im = imagecreatefrompng( "$image_pin_path" );
					break;
			}

			// map parameters.
			$scale = 360 / $image_worldmap_width;

			// Time to remove old entries.
			$current_time = (int) current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			$xx_secs_ago  = ( $current_time - $seconds_ago );

			$rows_arr = array();
			if ( Visitor_Maps::$core->get_option( 'hide_bots' ) ) {
				// phpcs:disable
				$rows_arr = $wpdb->get_results(
					'SELECT SQL_CALC_FOUND_ROWS user_id, name, longitude, latitude FROM ' . $wo_table_wo . "
                     WHERE name = 'Guest' AND time_last_click > '" . $xx_secs_ago . "' LIMIT " . absint( Visitor_Maps::$core->get_option( 'pins_limit' ) ) . '',
					ARRAY_A
				);

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$rows_count = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$rows_arr = $wpdb->get_results(
					'SELECT SQL_CALC_FOUND_ROWS user_id, name, longitude, latitude FROM ' . $wo_table_wo . "
                     WHERE time_last_click > '" . $xx_secs_ago . "' LIMIT " . absint( Visitor_Maps::$core->get_option( 'pins_limit' ) ) . '',
					ARRAY_A
				);

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$rows_count = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
				// phpcs:enable
			}

			$count = 0;
			// create pins on the map.
			if ( $rows_arr ) { // check of there are any visitors.
				foreach ( $rows_arr as $row ) {
					if ( '0.0000' !== $row['longitude'] && '0.0000' !== $row['latitude'] ) {
						if ( 0 === $ul_lat ) { // must be the world map.
							$count ++;
							$x = floor( ( $row['longitude'] + 180 ) / $scale );
							$y = floor( ( 180 - ( $row['latitude'] + 90 ) ) / $scale );
						} else {      // its a custom map
							// filter out what we do not want.
							if ( ( $row['latitude'] > $lr_lat && $row['latitude'] < $ul_lat ) && ( $row['longitude'] < $lr_lon && $row['longitude'] > $ul_lon ) ) {
								$count ++;
								$x = floor( $image_worldmap_width * ( $row['longitude'] - $ul_lon ) / ( $lr_lon - $ul_lon ) + $offset_x );
								$y = floor( $image_worldmap_height * ( $row['latitude'] - $ul_lat ) / ( $lr_lat - $ul_lat ) + $offset_y );

								// discard pixels that are outside the image because of offsets.
								if ( ( $x < 0 || $x > $image_worldmap_width ) || ( $y < 0 || $y > $image_worldmap_height ) ) {
									$count --;
									continue;
								}
							} else {
								continue;
							}
						}

						// Now mark the point on the map using a green 2 pixel rectangle.
						if ( $this->gvar['pins_display'] ) {
							$this_pin_im = $pin_im;
							if ( Visitor_Maps::$core->get_option( 'enable_users_map_hover' ) && $row['user_id'] > 0 && '' !== $row['name'] ) {
								// different pin color for logged in user.
								$this_pin_im = $pin_im_user;
							}

							if ( ! Visitor_Maps::$core->get_option( 'hide_bots' ) && 0 === $row['user_id'] && 'Guest' !== $row['name'] ) {
								// different pin color for search bot.
								$this_pin_im = $pin_im_bot;
							}

							// put pin image on map image.
							imagecopy( $map_im, $this_pin_im, $x, $y, 0, 0, $image_pin_width, $image_pin_height );
						}
					}
				} // end foreach.
			} // end if.

			if ( $this->gvar['text_display'] && ! Visitor_Maps::$core->get_option( 'hide_text_on_worldmap' ) ) {
				if ( '' !== $units_filtered ) {
					// 5 visitors since 15 (minutes|hours|days) ago.
					// translators: %1$d = row count, %2$d = time, %3$s = time unit.
					$text = sprintf( esc_html__( '%1$d visitors since %2$d %3$s ago', 'visitor-maps' ), $rows_count, $time, $units_lang );
				} else {
					// 5 visitors since 15 minutes ago.
					// translators: %1$d = row count, %2$d = time unit.
					$text = sprintf( esc_html__( '%1$d visitors since %2$d ago', 'visitor-maps' ), $rows_count, floor( Visitor_Maps::$core->get_option( 'track_time' ) ) );
				}

				$this->textoverlay( $text, $map_im, $image_worldmap_width, $image_worldmap_height );
			}

			// Return the map image.
			if ( isset( $_GET['type'] ) && 'jpg' === $_GET['type'] ) {
				header( 'Content-Type: image/jpeg' );
				imagejpeg( $map_im );
			} elseif ( isset( $_GET['type'] ) && 'png' === $_GET['type'] ) {
				header( 'Content-Type: image/png' );
				$x = imagepng( $map_im );
			} else {
				header( 'Content-Type: image/png' );
				imagepng( $map_im );
			}

			imagedestroy( $map_im );
			imagedestroy( $pin_im );
		}

		/**
		 * Text Overlay.
		 *
		 * @param string $text Text.
		 * @param int    $image_p Pointer to image resouce.
		 * @param int    $new_width New width.
		 * @param int    $new_height New height.
		 */
		private function textoverlay( $text, $image_p, $new_width, $new_height ) {
			$fontstyle       = 5; // 1 to 5.
			$fontcolor       = $this->gvar['text_color'];
			$fontshadowcolor = $this->gvar['text_shadow_color'];
			$ttfont          = ( isset( $this->set['map_text_font'] ) ) ? $this->set['map_text_font'] : Visitor_Maps::$dir . 'fonts/vmfont.ttf';
			$fontsize        = 11; // size for True Type Font $ttfont only (8-18 recommended).
			$textalign       = $this->gvar['text_align'];
			$xmargin         = 5;
			$ymargin         = 0;

			if ( ! preg_match( '#[a-z0-9]{6}#i', $fontcolor ) ) {
				$fontcolor = 'FFFFFF'; // default white.
			}

			if ( ! preg_match( '#[a-z0-9]{6}#i', $fontshadowcolor ) ) {
				$fontshadowcolor = '808080'; // default grey.
			}

			$fcint   = hexdec( "$fontcolor" );
			$fsint   = hexdec( "$fontshadowcolor" );
			$fcarr   = array(
				'red'   => 0xFF & ( $fcint >> 0x10 ),
				'green' => 0xFF & ( $fcint >> 0x8 ),
				'blue'  => 0xFF & $fcint,
			);
			$fsarr   = array(
				'red'   => 0xFF & ( $fsint >> 0x10 ),
				'green' => 0xFF & ( $fsint >> 0x8 ),
				'blue'  => 0xFF & $fsint,
			);
			$fcolor  = imagecolorallocate( $image_p, $fcarr['red'], $fcarr['green'], $fcarr['blue'] );
			$fscolor = imagecolorallocate( $image_p, $fsarr['red'], $fsarr['green'], $fsarr['blue'] );

			if ( '' !== $ttfont ) {
				// using ttf fonts.
				$_b         = imageTTFBbox( $fontsize, 0, $ttfont, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' );
				$fontheight = abs( $_b[7] - $_b[1] );
			} else {
				$font = $fontstyle;

				// using built in fonts, find alignment.
				if ( $font < 0 || $font > 5 ) {
					$font = 1;
				}

				$fontwidth  = ImageFontWidth( $font );
				$fontheight = ImageFontHeight( $font );
			}

			$text = preg_replace( "/\r/", '', $text );

			// wordwrap line if too many characters on one line.
			if ( '' !== $ttfont ) {
				$lines = explode( "\n", $text );
				$lines = $this->ttf_wordwrap( $lines, $ttfont, $fontsize, floor( $new_width - ( $xmargin * 2 ) ) );
			} else {
				$maxcharsperline = floor( ( $new_width - ( $xmargin * 2 ) ) / $fontwidth );
				$text            = wordwrap( $text, $maxcharsperline, "\n", 1 );

				$lines = explode( "\n", $text );
			}

			$align = 'ul'; // default upper left.

			if ( 'll' === $textalign ) {
				$align = 'll'; // lowerleft.
			}

			if ( 'ul' === $textalign ) {
				$align = 'ul'; // upperleft.
			}

			if ( 'lr' === $textalign ) {
				$align = 'lr'; // lowerright.
			}

			if ( 'ur' === $textalign ) {
				$align = 'ur'; // upperright.
			}

			if ( 'c' === $textalign ) {
				$align = 'c';  // center.
			}

			if ( 'ct' === $textalign ) {
				$align = 'ct'; // centertop.
			}

			if ( 'cb' === $textalign ) {
				$align = 'cb'; // centerbottom.
			}

			if ( 'ul' === $align ) {
				$x = $xmargin;
				$y = $ymargin;
			}

			if ( 'll' === $align ) {
				$x     = $xmargin;
				$y     = $new_height - ( $fontheight + $ymargin );
				$lines = array_reverse( $lines );
			}

			if ( 'ur' === $align ) {
				$y = $ymargin;
			}
			if ( 'lr' === $align ) {
				$x     = $xmargin;
				$y     = $new_height - ( $fontheight + $ymargin );
				$lines = array_reverse( $lines );
			}

			if ( 'ct' === $align ) {
				$y = $ymargin;
			}

			if ( 'cb' === $align ) {
				$x     = $xmargin;
				$y     = $new_height - ( $fontheight + $ymargin );
				$lines = array_reverse( $lines );
			}

			if ( 'c' === $align ) {
				$y = ( $new_height / 2 ) - ( ( count( $lines ) * $fontheight ) / 2 );
			}

			if ( '' !== $ttfont ) {
				$y += $fontsize; // fudge adjustment for truetype margin.
			}

			foreach ( $lines as $num1 => $line ) {
				if ( '' !== $ttfont ) {
					$_b          = imageTTFBbox( $fontsize, 0, $ttfont, $line );
					$stringwidth = abs( $_b[2] - $_b[0] );
				} else {
					$stringwidth = strlen( $line ) * $fontwidth;
				}

				if ( 'ur' === $align || 'lr' === $align ) {
					$x = ( $new_width - ( $stringwidth ) - $xmargin );
				}

				if ( 'ct' === $align || 'cb' === $align || 'c' === $align ) {
					$x = $new_width / 2 - $stringwidth / 2;
				}

				if ( '' !== $ttfont ) {
					// write truetype font text with slight SE shadow to standout.
					imagettftext( $image_p, $fontsize, 0, $x - 1, $y, $fscolor, $ttfont, $line );
					imagettftext( $image_p, $fontsize, 0, $x, $y - 1, $fcolor, $ttfont, $line );
				} else {
					// write text with slight SE shadow to standout.
					imagestring( $image_p, $font, $x - 1, $y, $line, $fscolor );
					imagestring( $image_p, $font, $x, $y - 1, $line, $fcolor );
				}

				if ( 'ul' === $align || 'ur' === $align || 'ct' === $align || 'c' === $align ) {
					$y += $fontheight;
				}

				if ( 'll' === $align || 'lr' === $align || 'cb' === $align ) {
					$y -= $fontheight;
				}
			}
		}

		/**
		 * TTF Wordwrap.
		 *
		 * @param array  $src_lines Array of lines.
		 * @param string $font Font resource.
		 * @param float  $text_size Text size.
		 * @param int    $width Width.
		 *
		 * @return array
		 */
		private function ttf_wordwrap( $src_lines, $font, $text_size, $width ) {
			$dst_lines = array(); // The destination lines array.

			foreach ( $src_lines as $current_l ) {
				$line  = '';
				$words = explode( ' ', $current_l ); // Split line into words.

				foreach ( $words as $word ) {
					$dimensions = imagettfbbox( $text_size, 0, $font, $line . ' ' . $word );
					$line_width = $dimensions[4] - $dimensions[0]; // get the length of this line, if the word is to be included.

					if ( $line_width > $width && ! empty( $line ) ) { // check if it is too big if the word was added, if so, then move on.
						$dst_lines[] = trim( $line ); // Add the line like it was without spaces.
						$line        = '';
					}
					$line .= $word . ' ';
				}

				$dst_lines[] = trim( $line ); // Add the line when the line ends.
			}

			return $dst_lines;
		}
	}
}
