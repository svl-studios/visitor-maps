<?php
/**
 * Whos Online Page Class
 *
 * @class   Whos_Online_Map_Page
 * @version 2.0.0
 * @package VisitorMaps
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Whos_Online_Map_Page' ) ) {

	/**
	 * Class Whos_Online_Map_Page
	 */
	class Whos_Online_Map_Page {


		/**
		 * Render map console page.
		 *
		 * @param bool $nonce_check Flag to bypass nonce check (for initial load as none is needed).
		 */
		public function do_map_page( $nonce_check = true ) {
			$map_time  = Visitor_Maps::$core->get_option( 'default_map_time' );  // default.
			$map_units = Visitor_Maps::$core->get_option( 'default_map_units' ); // default.

			$map_text_color        = 'FBFB00';  // default.
			$map_text_shadow_color = '3F3F3F';  // default.
			$map_selected          = Visitor_Maps::$core->get_option( 'default_map' );  // default.

			if ( true === $nonce_check ) {
				if ( ! isset( $_POST['nonce'] ) ) {
					return;
				}

				if ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'], 'wo_map_console' ) ) ) {
					return;
				}
			}

			if ( isset( $_POST['time'] ) && is_numeric( $_POST['time'] ) ) {
				$map_time = floor( sanitize_text_field( wp_unslash( $_POST['time'] ) ) );
			}

			if ( isset( $_POST['units'] ) && $this->validate_map_units( sanitize_text_field( wp_unslash( $_POST['units'] ) ) ) ) {
				$map_units = sanitize_text_field( wp_unslash( $_POST['units'] ) );
			}

			if ( isset( $_POST['textcolor'] ) && $this->validate_input_color( sanitize_text_field( wp_unslash( $_POST['textcolor'] ) ) ) ) {
				$map_text_color = sanitize_text_field( wp_unslash( $_POST['textcolor'] ) );
			}

			if ( isset( $_POST['textcolors'] ) && $this->validate_input_color( sanitize_text_field( wp_unslash( $_POST['textcolors'] ) ) ) ) {
				$map_text_shadow_color = sanitize_text_field( wp_unslash( $_POST['textcolors'] ) );
			}

			if ( isset( $_POST['map'] ) && is_numeric( $_POST['map'] ) ) {
				$map_selected = floor( sanitize_text_field( wp_unslash( $_POST['map'] ) ) );
			}

			?>
			<div class="wo-mp">
				<form method="post" name="time_select" action="">
					<h3 class="vm-h3"><?php echo esc_html__( 'Visitor Maps', 'visitor-maps' ); ?></h3>
					<p>
						<?php // translators: %d = Days to store results. ?>
						<?php printf( esc_html__( 'Select a time period up to %d days ago', 'visitor-maps' ), intval( Visitor_Maps::$core->get_option( 'store_days' ) ) ); ?>
						<br/>
						<label for="time"><?php echo esc_html__( 'Time:', 'visitor-maps' ); ?></label>
						<input type="text" id="time" name="time" value="<?php echo esc_attr( $map_time ); ?>" size="3"/>
						<label for="units"><?php echo esc_html__( 'Units:', 'visitor-maps' ); ?></label>
						<select id="units" name="units">
							<?php
							$map_units_array = array(
								'minutes' => esc_html__( 'minutes', 'visitor-maps' ),
								'hours'   => esc_html__( 'hours', 'visitor-maps' ),
								'days'    => esc_html__( 'days', 'visitor-maps' ),
							);

							$selected = '';

							foreach ( $map_units_array as $k => $v ) {
								if ( "$k" === $map_units ) {
									$selected = 'selected="selected"';
								}

								echo '<option value="' . esc_attr( $k ) . '" ' . esc_html( $selected ) . '>' . esc_html( $v ) . '</option>' . "\n";

								$selected = '';
							}
							?>
						</select>
						<?php // phpcs:disable ?>
						<!--<br />
						<label for="textcolor">Text Color:</label>
						<input type="text" id="textcolor" name="textcolor" value="<?php /*echo esc_attr( $map_text_color ); */?>" size="8"/>
						<label for="textcolors">Text Shadow Color:</label>
						<input type="text" id="textcolors" name="textcolors" value="<?php /*echo esc_attr( $map_text_shadow_color ); */?>" size="8"/>
						<br/>-->
						<?php // phpcs:enable ?>
						<label for="map">
							<?php echo esc_html__( 'Map:', 'visitor-maps' ); ?>
						</label>
						<select id="map" name="map">
							<?php
							$map_select_array = array(
								'1'  => esc_html__( 'World (smallest)', 'visitor-maps' ),
								'2'  => esc_html__( 'World (small)', 'visitor-maps' ),
								'3'  => esc_html__( 'World (medium)', 'visitor-maps' ),
								'4'  => esc_html__( 'World (large)', 'visitor-maps' ),
								'5'  => esc_html__( 'US', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'6'  => esc_html__( 'US', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'7'  => esc_html__( 'Canada and US', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'8'  => esc_html__( 'Canada and US', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'9'  => esc_html__( 'Asia', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'10' => esc_html__( 'Asia', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'11' => esc_html__( 'Australia and NZ', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'12' => esc_html__( 'Australia and NZ', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'13' => esc_html__( 'Europe Central', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'14' => esc_html__( 'Europe Central', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'15' => esc_html__( 'Europe', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'16' => esc_html__( 'Europe', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'17' => esc_html__( 'Scandinavia', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'18' => esc_html__( 'Scandinavia', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'19' => esc_html__( 'Great Britain', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'20' => esc_html__( 'Great Britain', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'21' => esc_html__( 'US Midwest', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'22' => esc_html__( 'US Midwest', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'23' => esc_html__( 'US Upper Midwest', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'24' => esc_html__( 'US Upper Midwest', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'25' => esc_html__( 'US Northeast', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'26' => esc_html__( 'US Northeast', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'27' => esc_html__( 'US Northwest', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'28' => esc_html__( 'US Northwest', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'29' => esc_html__( 'US Rocky Mountain', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'30' => esc_html__( 'US Rocky Mountain', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'31' => esc_html__( 'US South', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'32' => esc_html__( 'US South', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'33' => esc_html__( 'US Southeast', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'34' => esc_html__( 'US Southeast', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'35' => esc_html__( 'US Southwest', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'36' => esc_html__( 'US Southwest', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'37' => esc_html__( 'Spain/Portugal', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'38' => esc_html__( 'Spain/Portugal', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'39' => esc_html__( 'Finland', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'40' => esc_html__( 'Finland', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'41' => esc_html__( 'Finland', 'visitor-maps' ) . ' ' . esc_html__( '(yellow)', 'visitor-maps' ),
								'42' => esc_html__( 'Japan', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'43' => esc_html__( 'Japan', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'44' => esc_html__( 'Netherlands', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'45' => esc_html__( 'Netherlands', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
								'46' => esc_html__( 'Brazil', 'visitor-maps' ) . ' ' . esc_html__( '(black)', 'visitor-maps' ),
								'47' => esc_html__( 'Brazil', 'visitor-maps' ) . ' ' . esc_html__( '(brown)', 'visitor-maps' ),
							);

							$selected = '';

							foreach ( $map_select_array as $k => $v ) {
								if ( "$k" === $map_selected ) {
									$selected = 'selected="selected"';
								}

								echo '<option value="' . esc_attr( $k ) . '" ' . esc_html( $selected ) . '>' . esc_html( $v ) . '</option>' . "\n";

								$selected = '';
							}
							?>
						</select>
						<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'wo_map_console' ) ); ?>"/>
						<input type="submit" name="<?php echo esc_attr__( 'Go', 'visitor-maps' ); ?>"
							value="<?php echo esc_attr__( 'Go', 'visitor-maps' ); ?>"/>
					</p>
				</form>
				<?php

				$map_selected = intval( $map_selected );

				if ( 1 === $map_selected ) {

					echo '<!-- World (smallest) -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						// digits of time.
						'units'      => $map_units,
						// minutes, hours, or days (with or without the "s").
						'map'        => '1',
						// 1,2 (you can add more map images in settings)
						'pin'        => '1',
						// 1,2,3 (you can add more pin images in settings)
						'pins'       => 'off',
						// off (off is required for html map).
						'text'       => 'on',
						// on or off.
						'textcolor'  => '000000',
						// any hex color code.
						'textshadow' => 'FFFFFF',
						// any hex color code.
						'textalign'  => 'cb',
						// ll , ul, lr, ur, c, ct, cb  (these codes mean lower left, upper left, upper right, center, center top, center bottom).
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
						'type'       => 'jpg',
						// jpg or png (map output type).
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 2 === $map_selected ) {
					echo '<!-- World (small) -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => '2',
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => '000000',
						'textshadow' => 'FFFFFF',
						'textalign'  => 'cb',
						'ul_lat'     => '0',
						'ul_lon'     => '0',
						'lr_lat'     => '360',
						'lr_lon'     => '180',
						'offset_x'   => '0',
						'offset_y'   => '0',
						'type'       => 'jpg',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 3 === $map_selected ) {
					echo '<!-- World (medium) -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => '3',
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => '000000',
						'textshadow' => 'FFFFFF',
						'textalign'  => 'cb',
						'ul_lat'     => '0',
						'ul_lon'     => '0',
						'lr_lat'     => '360',
						'lr_lon'     => '180',
						'offset_x'   => '0',
						'offset_y'   => '0',
						'type'       => 'jpg',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 4 === $map_selected ) {
					echo '<!-- World (large) --> ' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => '4',
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => '000000',
						'textshadow' => 'FFFFFF',
						'textalign'  => 'cb',
						'ul_lat'     => '0',
						'ul_lon'     => '0',
						'lr_lat'     => '360',
						'lr_lon'     => '180',
						'offset_x'   => '0',
						'offset_y'   => '0',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 5 === $map_selected || 6 === $map_selected ) {
					echo '<!-- US Map --> ' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '58.30',
						'ul_lon'     => '-125.26',
						'lr_lat'     => '12.76',
						'lr_lon'     => '-65.98',
						'offset_x'   => '0',
						'offset_y'   => '37',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 7 === $map_selected || 8 === $map_selected ) {
					echo '<!-- Canada and US Map --> ' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '65.30',
						'ul_lon'     => '-167.83',
						'lr_lat'     => '-27.52',
						'lr_lon'     => '-52.17',
						'offset_x'   => '0',
						'offset_y'   => '52',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 9 === $map_selected || 10 === $map_selected ) {
					echo '<!-- Asia Map -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '55.84',
						'ul_lon'     => '63.86',
						'lr_lat'     => '-4.67',
						'lr_lon'     => '136.14',
						'offset_x'   => '0',
						'offset_y'   => '25',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 11 === $map_selected || 12 === $map_selected ) {
					echo '<!-- Australia and NZ Map --> ' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '9.56',
						'ul_lon'     => '112.75',
						'lr_lat'     => '-49.35',
						'lr_lon'     => '179.25',
						'offset_x'   => '0',
						'offset_y'   => '-30',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 13 === $map_selected || 14 === $map_selected ) {
					echo '<!-- Europe Central Map  -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '58.42',
						'ul_lon'     => '-4.46',
						'lr_lat'     => '39.80',
						'lr_lon'     => '24.46',
						'offset_x'   => '0',
						'offset_y'   => '25',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 15 === $map_selected || 16 === $map_selected ) {
					echo '<!-- Europe Map  -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '63.26',
						'ul_lon'     => '-2.47',
						'lr_lat'     => '26.40',
						'lr_lon'     => '52.47',
						'offset_x'   => '0',
						'offset_y'   => '44',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 17 === $map_selected || 18 === $map_selected ) {
					echo '<!-- Scandinavia Map --> ' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '64.88',
						'ul_lon'     => '-4.46',
						'lr_lat'     => '49.49',
						'lr_lon'     => '24.46',
						'offset_x'   => '0',
						'offset_y'   => '10',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 19 === $map_selected || 20 === $map_selected ) {
					echo '<!-- Great Britain Map  -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '62.47',
						'ul_lon'     => '-12.46',
						'lr_lat'     => '45.83',
						'lr_lon'     => '16.46',
						'offset_x'   => '0',
						'offset_y'   => '21',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 21 === $map_selected || 22 === $map_selected ) {
					echo '<!-- US Midwest Map -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '51.29',
						'ul_lon'     => '-100.84',
						'lr_lat'     => '35.69',
						'lr_lon'     => '-79.16',
						'offset_x'   => '0',
						'offset_y'   => '17',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 23 === $map_selected || 24 === $map_selected ) {
					echo '<!-- US Upper Midwest Map -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '53.49',
						'ul_lon'     => '-115.46',
						'lr_lat'     => '32.70',
						'lr_lon'     => '-86.54',
						'offset_x'   => '0',
						'offset_y'   => '20',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 25 === $map_selected || 26 === $map_selected ) {
					echo '<!-- US Northeast Map -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '51.84',
						'ul_lon'     => '-92.46',
						'lr_lat'     => '30.37',
						'lr_lon'     => '-63.54',
						'offset_x'   => '0',
						'offset_y'   => '20',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 27 === $map_selected || 28 === $map_selected ) {
					echo '<!-- US Northwest Map -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'ct',
						'ul_lat'     => '53.49',
						'ul_lon'     => '-126.46',
						'lr_lat'     => '32.70',
						'lr_lon'     => '-97.54',
						'offset_x'   => '0',
						'offset_y'   => '25',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 29 === $map_selected || 30 === $map_selected ) {
					echo '<!-- US Rocky Mountain Map -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '50.17',
						'ul_lon'     => '-124.46',
						'lr_lat'     => '28.06',
						'lr_lon'     => '-95.54',
						'offset_x'   => '0',
						'offset_y'   => '15',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 31 === $map_selected || 32 === $map_selected ) {
					echo '<!-- US South Map -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '45.11',
						'ul_lon'     => '-112.46',
						'lr_lat'     => '21.23',
						'lr_lon'     => '-83.54',
						'offset_x'   => '0',
						'offset_y'   => '9',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 33 === $map_selected || 34 === $map_selected ) {
					echo '<!-- US Southeast Map -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '43.40',
						'ul_lon'     => '-100.46',
						'lr_lat'     => '18.99',
						'lr_lon'     => '-71.54',
						'offset_x'   => '0',
						'offset_y'   => '5',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 35 === $map_selected || 36 === $map_selected ) {
					echo '<!-- US Southwest Map -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => $map_text_color,
						'textshadow' => $map_text_shadow_color,
						'textalign'  => 'cb',
						'ul_lat'     => '46.80',
						'ul_lon'     => '-126.46',
						'lr_lat'     => '23.49',
						'lr_lon'     => '-97.54',
						'offset_x'   => '0',
						'offset_y'   => '10',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 37 === $map_selected || 38 === $map_selected ) {
					echo '<!-- Spain/Portugal Map -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '1',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => 'FBFB00',
						'textshadow' => '3F3F3F',
						'textalign'  => 'cb',
						'ul_lat'     => '45.01',
						'ul_lon'     => '-10.69',
						'lr_lat'     => '34.56',
						'lr_lon'     => '3.13',
						'offset_x'   => '0',
						'offset_y'   => '0',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 39 === $map_selected || 40 === $map_selected || 41 === $map_selected ) {
					echo '<!-- Finland -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '2',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => 'FBFB00',
						'textshadow' => '3F3F3F',
						'textalign'  => 'cb',
						'ul_lat'     => '70.06',
						'ul_lon'     => '19.11',
						'lr_lat'     => '59.57',
						'lr_lon'     => '31.90',
						'offset_x'   => '0',
						'offset_y'   => '0',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 42 === $map_selected || 43 === $map_selected ) {
					echo '<!-- Japan -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '2',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => 'FBFB00',
						'textshadow' => '3F3F3F',
						'textalign'  => 'cb',
						'ul_lat'     => '47.09',
						'ul_lon'     => '123.15',
						'lr_lat'     => '29.81',
						'lr_lon'     => '146.05',
						'offset_x'   => '0',
						'offset_y'   => '0',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 44 === $map_selected || 45 === $map_selected ) {
					echo '<!-- Netherlands -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '2',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => 'FBFB00',
						'textshadow' => '3F3F3F',
						'textalign'  => 'cb',
						'ul_lat'     => '53.57',
						'ul_lon'     => '3.07',
						'lr_lat'     => '50.68',
						'lr_lon'     => '7.78',
						'offset_x'   => '0',
						'offset_y'   => '0',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}

				if ( 46 === $map_selected || 47 === $map_selected ) {
					echo '<!-- Brazil -->' . "\n";

					$map_settings = array(
						'time'       => $map_time,
						'units'      => $map_units,
						'map'        => $map_selected,
						'pin'        => '2',
						'pins'       => 'off',
						'text'       => 'on',
						'textcolor'  => 'FBFB00',
						'textshadow' => '3F3F3F',
						'textalign'  => 'cb',
						'ul_lat'     => '9.44',
						'ul_lon'     => '-77.47',
						'lr_lat'     => '-33.30',
						'lr_lon'     => '-29.74',
						'offset_x'   => '0',
						'offset_y'   => '0',
						'type'       => 'png',
					);

					// phpcs:ignore WordPress.Security.EscapeOutput
					echo Visitor_Maps::$core->get_visitor_maps_worldmap( $map_settings );
				}
				?>
			</div>
			<?php
		}

		/**
		 * Set map units.
		 *
		 * @param string $string Unit to verify.
		 *
		 * @return bool
		 */
		private function validate_map_units( $string ) {
			// only allow proper text align codes.
			$allowed = array( 'minutes', 'hours', 'days' );

			if ( in_array( $string, $allowed, true ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Validate input color.
		 *
		 * @param string $string Color to validate.
		 *
		 * @return bool
		 */
		private function validate_input_color( $string ) {
			// protect form input color fields from hackers and check for valid css color code hex
			// only allow simple 6 char hex codes with or without # like this 336699 or #336699.

			if ( is_string( $string ) && preg_match( '/^#[a-f0-9]{6}$/i', trim( $string ) ) ) {
				return true;
			}

			if ( is_string( $string ) && preg_match( '/^[a-f0-9]{6}$/i', trim( $string ) ) ) {
				return true;
			}

			return false;
		}
	}
}
