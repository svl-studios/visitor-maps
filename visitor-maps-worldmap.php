<?php
/**
 * Visitor Maps Wordmap output.
 *
 * @class
 * @version 2.0.0
 * @package VisitorMaps
 */

defined( 'ABSPATH' ) || exit;

$wo_table_wo = $wpdb->prefix . 'visitor_maps_wo';

if ( ! Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
	return '<p>get_visitor_maps_worldmap ' . esc_html__( 'error: geolocation data not enabled or installed', 'visitor-maps' ) . '</p>';
}

$c = array();
$g = array();

// worldmap image names.
// just image names only, do not add any paths.
$c['image_worldmap']    = 'wo-worldmap-smallest.jpg'; // World (smallest) do not delete this one, it is the default.
$c['image_worldmap_1']  = 'wo-worldmap-smallest.jpg'; // World (smallest) do not delete this one, it is the default.
$c['image_worldmap_2']  = 'wo-worldmap-small.jpg';   // World (small).
$c['image_worldmap_3']  = 'wo-worldmap-medium.jpg';  // World (medium).
$c['image_worldmap_4']  = 'wo-worldmap-large.jpg';   // World (large).
$c['image_worldmap_5']  = 'wo-us-black-map.png';     // US (black).
$c['image_worldmap_6']  = 'wo-us-brown-map.png';     // US (brown).
$c['image_worldmap_7']  = 'wo-akus-black-map.png';   // Canada and US (black).
$c['image_worldmap_8']  = 'wo-akus-brown-map.png';   // Canada and US (brown).
$c['image_worldmap_9']  = 'wo-asia-black-map.png';   // Asia (black).
$c['image_worldmap_10'] = 'wo-asia-brown-map.png';   // Asia (brown).
$c['image_worldmap_11'] = 'wo-aus-nz-black-map.png'; // Australia and NZ (black).
$c['image_worldmap_12'] = 'wo-aus-nz-brown-map.png'; // Australia and NZ (brown).
$c['image_worldmap_13'] = 'wo-ceu-black-map.png';    // Europe Central (black).
$c['image_worldmap_14'] = 'wo-ceu-brown-map.png';    // Europe Central (brown).
$c['image_worldmap_15'] = 'wo-eu-black-map.png';     // Europe (black).
$c['image_worldmap_16'] = 'wo-eu-brown-map.png';     // Europe (brown).
$c['image_worldmap_17'] = 'wo-scan-black-map.png';    // Scandinavia (black).
$c['image_worldmap_18'] = 'wo-scan-brown-map.png';    // Scandinavia (brown).
$c['image_worldmap_19'] = 'wo-gb-black-map.png';     // Great Britain (black).
$c['image_worldmap_20'] = 'wo-gb-brown-map.png';     // Great Britain (brown).
$c['image_worldmap_21'] = 'wo-mwus-black-map.png';   // US Midwest (black).
$c['image_worldmap_22'] = 'wo-mwus-brown-map.png';   // US Midwest (brown).
$c['image_worldmap_23'] = 'wo-ncus-black-map.png';   // US Upper Midwest (black).
$c['image_worldmap_24'] = 'wo-ncus-brown-map.png';   // US Upper Midwest (brown).
$c['image_worldmap_25'] = 'wo-neus-black-map.png';   // US Northeast (black).
$c['image_worldmap_26'] = 'wo-neus-brown-map.png';   // US Northeast (brown).
$c['image_worldmap_27'] = 'wo-nwus-black-map.png';   // US Northwest (black).
$c['image_worldmap_28'] = 'wo-nwus-brown-map.png';   // US Northwest (brown).
$c['image_worldmap_29'] = 'wo-rmus-black-map.png';   // US Rocky Mountain (black).
$c['image_worldmap_30'] = 'wo-rmus-brown-map.png';   // US Rocky Mountain (brown).
$c['image_worldmap_31'] = 'wo-scus-black-map.png';   // US South (black).
$c['image_worldmap_32'] = 'wo-scus-brown-map.png';   // US South (brown).
$c['image_worldmap_33'] = 'wo-seus-black-map.png';   // US Southeast (black).
$c['image_worldmap_34'] = 'wo-seus-brown-map.png';   // US Southeast (brown).
$c['image_worldmap_35'] = 'wo-swus-black-map.png';   // US Southwest (black).
$c['image_worldmap_36'] = 'wo-swus-brown-map.png';   // US Southwest (brown).
$c['image_worldmap_37'] = 'wo-es-pt-black-map.png';   // Spain/Portugal (black).
$c['image_worldmap_38'] = 'wo-es-pt-brown-map.png';   // Spain/Portugal (brown).
$c['image_worldmap_39'] = 'wo-finland-black-map.png';   // Finland (black).
$c['image_worldmap_40'] = 'wo-finland-brown-map.png';   // Finland (brown).
$c['image_worldmap_41'] = 'wo-finland-yellow-map.png';   // Finland (yellow).
$c['image_worldmap_42'] = 'wo-jp-black-map.png';   // Japan (black).
$c['image_worldmap_43'] = 'wo-jp-brown-map.png';   // Japan (brown).
$c['image_worldmap_44'] = 'wo-nl-black-map.png';   // Netherlands (black).
$c['image_worldmap_45'] = 'wo-nl-brown-map.png';   // Netherlands (brown).
$c['image_worldmap_46'] = 'wo-br-black-map.png';   // Brazil (black).
$c['image_worldmap_47'] = 'wo-br-brown-map.png';   // Brazil (brown).
// you can add more, just increment the numbers.

$c['image_pin']   = 'wo-pin.jpg'; // do not delete this one, it is the default.
$c['image_pin_1'] = 'wo-pin.jpg'; // do not delete this one, it is the default.
$c['image_pin_2'] = 'wo-pin5x5.png';
$c['image_pin_3'] = 'wo-pin-green5x5.jpg';
// you can add more, just increment the numbers
// set lat lon coordinates for worldmaps and custom regional maps.
$ul_lat = 0;
$ul_lon = 0;
$lr_lat = 360;
$lr_lon = 180; // default worldmap.

if ( isset( $ms['ul_lat'] ) && is_numeric( $ms['ul_lat'] ) ) {
	$ul_lat = $ms['ul_lat'];
}
if ( isset( $ms['ul_lon'] ) && is_numeric( $ms['ul_lon'] ) ) {
	$ul_lon = $ms['ul_lon'];
}
if ( isset( $ms['lr_lat'] ) && is_numeric( $ms['lr_lat'] ) ) {
	$lr_lat = $ms['lr_lat'];
}
if ( isset( $ms['lr_lon'] ) && is_numeric( $ms['lr_lon'] ) ) {
	$lr_lon = $ms['lr_lon'];
}
$offset_x = 0;
$offset_y = 0;

if ( isset( $ms['offset_x'] ) && is_numeric( $ms['offset_x'] ) ) {
	$offset_x = floor( $ms['offset_x'] );
}
if ( isset( $ms['offset_y'] ) && is_numeric( $ms['offset_y'] ) ) {
	$offset_y = floor( $ms['offset_y'] );
}
// select text on or off.
$g['text_display'] = 'off'; // default.
if ( isset( $ms['text'] ) && 'on' === $ms['text'] ) {
	$g['text_display'] = 'on';
}
// select text align.
$g['text_align'] = 'cb'; // default center bottom.
if ( isset( $ms['textalign'] ) && $this->validate_text_align( $ms['textalign'] ) ) {
	$g['text_align'] = $ms['textalign'];
}
// select text color by hex code.
$g['text_color'] = '336699'; // default blue.
if ( isset( $ms['textcolor'] ) && $this->validate_color_wo( $ms['textcolor'] ) ) {
	$g['text_color'] = str_replace( '#', '', $ms['textcolor'] );  // hex.
}
// select text shadow color by hex code.
$g['text_shadow_color'] = 'FFFFFF'; // default white.
if ( isset( $ms['textshadow'] ) && $this->validate_color_wo( $ms['textshadow'] ) ) {
	$g['text_shadow_color'] = str_replace( '#', '', $ms['textshadow'] );  // hex.
}
// select pins on or off.
$g['pins_display'] = true;  // default.
if ( isset( $ms['pins'] ) && 'off' === $ms['pins'] ) {
	$g['pins_display'] = false;
}

// select time units.
$g['time']  = absint( Visitor_Maps::$core->get_option( 'track_time' ) );
$g['units'] = 'minutes';
if ( isset( $ms['time'] ) && is_numeric( $ms['time'] ) && isset( $ms['units'] ) ) {
	$time           = floor( $ms['time'] );
	$units          = $ms['units'];
	$units_filtered = '';
	if ( $time > 0 && ( 'minute' === $units || 'minutes' === $units ) ) {
		$seconds_ago    = ( $time * 60 ); // minutes.
		$units_filtered = $units;
		$g['time']      = $time;
		$g['units']     = $units;
	} elseif ( $time > 0 && ( 'hour' === $units || 'hours' === $units ) ) {
		$seconds_ago    = ( $time * 60 * 60 ); // hours.
		$units_filtered = $units;
		$g['time']      = $time;
		$g['units']     = $units;
	} elseif ( $time > 0 && ( 'day' === $units || 'days' === $units ) ) {
		$seconds_ago    = ( $time * 60 * 60 * 24 ); // days.
		$units_filtered = $units;
		$g['time']      = $time;
		$g['units']     = $units;
	} else {
		$seconds_ago = absint( Visitor_Maps::$core->get_option( 'track_time' ) * 60 ); // default.
	}
} else {
	$seconds_ago = absint( Visitor_Maps::$core->get_option( 'track_time' ) * 60 ); // default.
}

// select map image.
$image_worldmap      = Visitor_Maps::$url . 'img/maps/' . $c['image_worldmap'];  // default.
$image_worldmap_path = Visitor_Maps::$dir . 'img/maps/' . $c['image_worldmap'];  // default.
$g['map']            = 1;
if ( isset( $ms['map'] ) && is_numeric( $ms['map'] ) ) {
	$g['map']            = floor( $ms['map'] );
	$image_worldmap      = Visitor_Maps::$url . 'img/maps/' . $c[ 'image_worldmap_' . $g['map'] ];
	$image_worldmap_path = Visitor_Maps::$dir . 'img/maps/' . $c[ 'image_worldmap_' . $g['map'] ];
	if ( ! file_exists( Visitor_Maps::$dir . 'img/maps/' . $c[ 'image_worldmap_' . $g['map'] ] ) ) {
		$image_worldmap      = Visitor_Maps::$url . 'img/maps/' . $c['image_worldmap'];  // default.
		$image_worldmap_path = Visitor_Maps::$dir . 'img/maps/' . $c['image_worldmap'];  // default.
		$g['map']            = 1;
	}
}
// this is a hack to fix servers with image header problems.
if ( Visitor_Maps::$core->get_option( 'hide_text_on_worldmap' ) ) {
	$image_worldmap2 = $image_worldmap;
}

// select pin image.
$image_pin      = Visitor_Maps::$url . 'img/maps/' . $c['image_pin'];  // default.
$image_pin_path = Visitor_Maps::$dir . 'img/maps/' . $c['image_pin'];  // default.
$g['pin']       = 1;
if ( isset( $ms['pin'] ) && is_numeric( $ms['pin'] ) ) {
	$g['pin']       = floor( $ms['pin'] );
	$image_pin      = Visitor_Maps::$url . 'img/maps/' . $c[ 'image_pin_' . $g['pin'] ];
	$image_pin_path = Visitor_Maps::$dir . 'img/maps/' . $c[ 'image_pin_' . $g['pin'] ];
	if ( ! file_exists( Visitor_Maps::$dir . 'img/maps/' . $c[ 'image_pin_' . $g['pin'] ] ) ) {
		$image_pin      = Visitor_Maps::$url . 'img/maps/' . $c['image_pin'];  // default.
		$image_pin_path = Visitor_Maps::$dir . 'img/maps/' . $c['image_pin'];  // default.
		$g['pin']       = 1;
	}
}
// select the map image type.
if ( isset( $ms['type'] ) && 'jpg' === $ms['type'] ) {
	$img_type = 'jpg';
} elseif ( isset( $ms['type'] ) && 'png' === $ms['type'] ) {
	$img_type = 'png';
} else {
	$img_type = 'png';
}
$current_time = (int) current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
$xx_secs_ago  = ( $current_time - $seconds_ago );

// get image data.
list( $image_worldmap_width, $image_worldmap_height, $image_worldmap_type ) = getimagesize( $image_worldmap_path );
list( $image_pin_width, $image_pin_height, $image_pin_type )                = getimagesize( $image_pin_path );

// map parameters.
$scale = 360 / $image_worldmap_width;

if ( is_array( $ms ) ) {
	$nonce = wp_create_nonce( 'do_wo_map' );

	$image_worldmap = get_bloginfo( 'url' ) . '?do_wo_map=1&amp;nonce=' . $nonce . '&amp;time=' . $g['time'] . '&amp;units=' . $g['units'] . '&amp;map=' . $g['map'] . '&amp;pin=' . $g['pin'] . '&amp;pins=off&amp;text=' . $g['text_display'] . '&amp;textcolor=' . $g['text_color'] . '&amp;textshadow=' . $g['text_shadow_color'] . '&amp;textalign=' . $g['text_align'] . '&amp;ul_lat=' . $ul_lat . '&amp;ul_lon=' . $ul_lon . '&amp;lr_lat=' . $lr_lat . '&amp;lr_lon=' . $lr_lon . '&amp;offset_x=' . $offset_x . '&amp;offset_y=' . $offset_y . '&amp;wp-minify-off=1&amp;type=' . $img_type;
}

// HTML maps automatically printed inside tables?
// (this is workaround for an IE problem. The map will be wrapped in a html table).
$maps_in_tables = 1;
$string         = '';

if ( $maps_in_tables ) {
	$string .= '<table class="wo-map">
 <tr>
   <td>
';
}

// this is a hack to fix servers with image header problems.
if ( Visitor_Maps::$core->get_option( 'hide_text_on_worldmap' ) ) {
	$image_worldmap = $image_worldmap2;
}
$string .= '<div style="position:relative; border:none; background-image:url(' . $image_worldmap . '); width:' . $image_worldmap_width . 'px; height:' . $image_worldmap_height . 'px;">';
$string .= "\n" . '<!--[if lte IE 8 ]>
<div style="position:relative; margin-top: -11px;">
<![endif]-->';
$string .= "\n";

// phpcs:disable
$rows_arr = array();
if ( ! Visitor_Maps::$core->get_option( 'hide_bots' ) ) {
	// all visitors.
	$rows_arr = $wpdb->get_results( '
         SELECT SQL_CALC_FOUND_ROWS user_id, name, nickname, country_name, country_code, city_name, state_name, state_code, latitude, longitude
         FROM ' . $wo_table_wo . "
         WHERE time_last_click > '" . absint( $xx_secs_ago ) . "' LIMIT " . absint( Visitor_Maps::$core->get_option( 'pins_limit' ) ),
		ARRAY_A
	);

	$rows_count = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
} else {
	// guests and members, no bots.
	$rows_arr = $wpdb->get_results( '
	     SELECT SQL_CALC_FOUND_ROWS user_id, name, nickname, country_name, country_code, city_name, state_name, state_code, latitude, longitude
	     FROM ' . $wo_table_wo . "
	     WHERE (name = 'Guest' AND time_last_click > '" . absint( $xx_secs_ago ) . "')
	     OR (name != 'Guest' AND user_id > 0 AND time_last_click > '" . absint( $xx_secs_ago ) . "') LIMIT " . absint( Visitor_Maps::$core->get_option( 'pins_limit' ) ),
		ARRAY_A
	);

	$rows_count = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
}
// phpcs:enable

// create pin on the map.
$count = 0;
if ( $rows_arr ) { // check of there are any visitors
	// see if the user is a spider (bot) or not
	// based on a list of spiders in spiders.txt file.
	if ( ! Visitor_Maps::$core->get_option( 'hide_bots' ) ) {
		$spiders = file( Visitor_Maps::$dir . 'spiders.txt' );
	}

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
			$title_pre      = '';
			$this_image_pin = $image_pin;
			if ( Visitor_Maps::$core->get_option( 'enable_users_map_hover' ) && $row['user_id'] > 0 && '' !== $row['name'] ) {
				// find name for logged in user
				// different pin color for logged in user.
				$title_pre = $row['name'] . ' ' . esc_html__( 'from', 'visitor-maps' ) . ' ';
				if ( 1 === $g['pin'] ) {
					$this_image_pin = str_replace( '.jpg', '-user.jpg', $image_pin );
				}
			}
			if ( ! Visitor_Maps::$core->get_option( 'hide_bots' ) && 0 === intval( $row['user_id'] ) && 'Guest' !== $row['name'] ) {
				// find name for bot.
				// different pin color for bot.
				if ( ! empty( $row['name'] ) ) {
					for ( $i = 0, $n = count( $spiders ); $i < $n; $i ++ ) {
						if ( ! empty( $spiders[ $i ] ) && is_integer( strpos( $row['name'], trim( $spiders[ $i ] ) ) ) ) {
							// Tokenize UserAgent and try to find Bots name.
							$tok = strtok( $row['name'], ' ();/' );
							while ( false !== $tok ) {
								if ( strlen( strtolower( $tok ) ) > 3 ) {
									if ( ! strstr( strtolower( $tok ), 'mozilla' ) && ! strstr( strtolower( $tok ), 'compatible' ) && ! strstr( strtolower( $tok ), 'msie' ) && ! strstr( strtolower( $tok ), 'windows' ) ) {
										$title_pre = $tok . ' ' . esc_html__( 'from', 'visitor-maps' ) . ' ';
										if ( 1 === $g['pin'] ) {
											$this_image_pin = str_replace( '.jpg', '-bot.jpg', $image_pin );
										}
										break;
									}
								}
								$tok = strtok( ' ();/' );
							}
							break;
						}
					}
				}
			}
			$location = '';
			if ( Visitor_Maps::$core->get_option( 'enable_state_display' ) ) {
				if ( '' !== $row['city_name'] ) {
					if ( 'US' === $row['country_code'] ) {
						$location = $row['city_name'];
						if ( '' !== $row['state_code'] ) {
							$location = $row['city_name'] . ', ' . strtoupper( $row['state_code'] );
						}
					} else {      // all non us countries.
						$location = $row['city_name'] . ', ' . strtoupper( $row['country_code'] );
					}
				} else {
					$location = '~ ' . $row['country_name'];
				}
			} else {
				$location = $row['country_name'];
			}
			$location = $title_pre . $location;
			$string  .= '<div style="cursor:pointer;position:absolute; top:' . $y . 'px; left:' . $x . 'px;">
      <img src="' . $this_image_pin . '" style="border:0; margin:0; padding:0;" width="' . $image_pin_width . '" height="' . $image_pin_height . '" alt="" title="' . esc_attr( $location ) . '" />
      </div>';
		}
	}
}

$string .= '<!--[if lte IE 8 ]>';
$string .= '</div>';
$string .= '<![endif]-->';
$string .= "\n";
$string .= '</div>';

if ( $maps_in_tables ) {
	$string .= '</td>';
	$string .= '</tr>';
	$string .= '</table>';
}
