<?php
/**
 * Who's Online View Class
 *
 * @class   Whos_Online_View
 * @version 2.0.0
 * @package VisitorMaps
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Whos_Online_View' ) ) {

	/**
	 * Class Whos_Online_View
	 */
	class Whos_Online_View {

		/**
		 * Visitor IP address.
		 *
		 * @var string
		 */
		private $wo_visitor_ip = '';

		/**
		 * Active IP address array.
		 *
		 * @var array
		 */
		private $ip_addrs_active = array();

		/**
		 * Settings array.
		 *
		 * @var array
		 */
		private $set = array();

		/**
		 * Render Who's Online page.
		 */
		public function view_whos_online() {
			global $wpdb;

			$wo_table_wo = $wpdb->prefix . 'visitor_maps_wo';

			$refresh_time    = array( 30, 60, 120, 300, 600 );
			$refresh_display = array( '0:30', '1:00', '2:00', '5:00', '10:00' );
			$refresh_values  = array();

			$refresh_values[] = array(
				'id'   => 'none',
				'text' => esc_attr__( 'None', 'visitor-maps' ),
			);

			$refresh_values[] = array(
				'id'   => '30',
				'text' => '0:30',
			);

			$refresh_values[] = array(
				'id'   => '60',
				'text' => '1:00',
			);

			$refresh_values[] = array(
				'id'   => '120',
				'text' => '2:00',
			);

			$refresh_values[] = array(
				'id'   => '300',
				'text' => '5:00',
			);

			$refresh_values[] = array(
				'id'   => '600',
				'text' => '10:00',
			);

			$show_type   = array();
			$show_type[] = array(
				'id'   => 'none',
				'text' => esc_attr__( 'None', 'visitor-maps' ),
			);

			$show_type[] = array(
				'id'   => 'all',
				'text' => esc_attr__( 'All', 'visitor-maps' ),
			);

			$show_type[] = array(
				'id'   => 'bots',
				'text' => esc_attr__( 'Bots', 'visitor-maps' ),
			);

			$show_type[] = array(
				'id'   => 'guests',
				'text' => esc_attr__( 'Guests', 'visitor-maps' ),
			);

			$bots_type   = array();
			$bots_type[] = array(
				'id'   => '0',
				'text' => esc_attr__( 'No', 'visitor-maps' ),
			);

			$bots_type[] = array(
				'id'   => '1',
				'text' => esc_attr__( 'Yes', 'visitor-maps' ),
			);

			$this->set['allow_refresh']          = 1;
			$this->set['allow_profile_display']  = 1;
			$this->set['allow_ip_display']       = 1;
			$this->set['allow_last_url_display'] = 1;
			$this->set['allow_referer_display']  = 1;

			$this->set['lasturl_wordwrap_chars']   = 100; // <= set to number of characters to wrap to
			$this->set['useragent_wordwrap_chars'] = 100; // <= set to number of characters to wrap to
			$this->set['referer_wordwrap_chars']   = 100; // <= set to number of characters to wrap to

			$this->set['color_bot']   = 'maroon';
			$this->set['color_admin'] = 'darkblue';
			$this->set['color_guest'] = 'green';
			$this->set['color_user']  = 'blue';

			$this->set['image_active_guest']   = 'active_user.gif'; // active user.
			$this->set['image_inactive_guest'] = 'inactive_user.gif'; // inactive user.
			$this->set['image_active_bot']     = 'active_bot.gif'; // active bot.
			$this->set['image_inactive_bot']   = 'inactive_bot.gif'; // inactive bot.

			$this->wo_visitor_ip = Visitor_Maps::$core->get_ip_address();

			// phpcs:disable
			$numrows = $wpdb->get_var( "SELECT count(*) FROM " . $wo_table_wo );
			$since   = $wpdb->get_var( "SELECT time_last_click FROM " . $wo_table_wo . " ORDER BY time_last_click ASC LIMIT 1" );
			// phpcs:enable

			// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			$current_time = (int) current_time( 'timestamp' );
			$xx_mins_ago  = ( $current_time - absint( ( Visitor_Maps::$core->get_option( 'track_time' ) * 60 ) ) );

			// phpcs:disable
			if ( Visitor_Maps::$core->get_option( 'store_days' ) > 0 ) {
				$xx_days_ago_time = ( $current_time - ( absint( Visitor_Maps::$core->get_option( 'store_days' ) ) * 60 * 60 * 24 ) );
				$wpdb->query(
					'DELETE from ' . $wo_table_wo . "
	                WHERE (time_last_click < '" . absint( $xx_days_ago_time ) . "' and nickname = '')
	                OR   (time_last_click < '" . absint( $xx_days_ago_time ) . "' and nickname IS NULL)"
				);
			} else {
				// remove visitor entries that have expired after $visitor_maps_opt['track_time'], save nickname friends.
				$wpdb->query(
					'DELETE from ' . $wo_table_wo . "
	                WHERE (time_last_click < '" . absint( $xx_mins_ago ) . "' and nickname = '')
	                OR   (time_last_click < '" . absint( $xx_mins_ago ) . "' and nickname IS NULL)"
				);
			}
			// phpcs:enable

			$wo_prefs_arr_def = array(
				'bots'    => '0',
				'refresh' => 'none',
				'show'    => 'none',
			);

			$wo_prefs_arr = get_option( 'visitor_maps_wop' );
			if ( ( ! $wo_prefs_arr ) || ! is_array( $wo_prefs_arr ) ) {
				// install the option defaults.
				update_option( 'visitor_maps_wop', $wo_prefs_arr_def );
				$wo_prefs_arr = $wo_prefs_arr_def;
			}

			$bots    = ( isset( $wo_prefs_arr['bots'] ) ) ? $wo_prefs_arr['bots'] : '0';
			$refresh = ( isset( $wo_prefs_arr['refresh'] ) ) ? $wo_prefs_arr['refresh'] : 'none';
			$show    = ( isset( $wo_prefs_arr['show'] ) ) ? $wo_prefs_arr['show'] : 'none';
			?>
			<table class="visitor-map-actions">
				<tr>
					<td>
						<form name="wo_view" action="<?php echo esc_url( admin_url( 'index.php?page=visitor-maps' ) ); ?>" method="get">
							<input type="hidden" name="wo_view_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wo_view' ) ); ?>"/>
							<?php
							if ( $this->set['allow_profile_display'] ) {
								// phpcs:ignore WordPress.Security.EscapeOutput
								echo esc_html__( 'Profile Display:', 'visitor-maps' ) . ' ' . $this->draw_pull_down_menu( 'show', $show_type, $show, 'onchange="this.form.submit();"' ) . ' ';
							}
							if ( $this->set['allow_refresh'] ) {
								// phpcs:ignore WordPress.Security.EscapeOutput
								echo esc_html__( 'Refresh Rate:', 'visitor-maps' ) . ' ' . $this->draw_pull_down_menu( 'refresh', $refresh_values, $refresh, 'onchange="this.form.submit();"' ) . ' ';
							}
							// phpcs:ignore WordPress.Security.EscapeOutput
							echo esc_html__( 'Show Bots:', 'visitor-maps' ) . ' ' . $this->draw_pull_down_menu( 'bots', $bots_type, $bots, 'onchange="this.form.submit();"' ) . ' ';
							?>
							<input type="hidden" name="page" value="visitor-maps"/>
						</form>
						<a href="<?php echo esc_url( admin_url( 'index.php?page=whos-been-online' ) ); ?>"><?php echo esc_html__( "Who's Been Online", 'visitor-maps' ); ?></a>
						<?php
						if ( current_user_can( 'manage_options' ) ) {
							echo '<br /> <a href="' . esc_url( admin_url( 'options-general.php?page=visitor_maps_opt' ) ) . '">' . esc_html__( 'Visitor Maps Options', 'visitor-maps' ) . "</a>\n";
						}

						if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
							echo '<br /><a onclick="wo_map_console(this.href); return false;" href="' . esc_url( get_bloginfo( 'url' ) ) . '?wo_map_console=1>' . esc_html__( 'Visitor Map Viewer', 'visitor-maps' ) . '</a>';
						}
						?>
					</td>
					<td>
						<table class="visitor-map-key">
							<tr>
								<td><?php echo '<img src="' . esc_url( Visitor_Maps::$url . 'img/maps/' . $this->set['image_active_guest'] ) . '" border="0" alt="' . esc_attr__( 'Active Guest', 'visitor-maps' ) . '" title="' . esc_attr__( 'Active Guest', 'visitor-maps' ) . '" /> ' . esc_html__( 'Active Guest', 'visitor-maps' ); ?>
								</td>
								<td><?php echo '<img src="' . esc_url( Visitor_Maps::$url . 'img/maps/' . $this->set['image_inactive_guest'] ) . '" border="0" alt="' . esc_attr__( 'Inactive Guest', 'visitor-maps' ) . '" title="' . esc_attr__( 'Inactive Guest', 'visitor-maps' ) . '" /> ' . esc_html__( 'Inactive Guest', 'visitor-maps' ); ?>
								</td>
							</tr>
							<tr>
								<td><?php echo '<img src="' . esc_url( Visitor_Maps::$url . 'img/maps/' . $this->set['image_active_bot'] ) . '" border="0" alt="' . esc_attr__( 'Active Bot', 'visitor-maps' ) . '" title="' . esc_attr__( 'Active Bot', 'visitor-maps' ) . '" /> ' . esc_html__( 'Active Bot', 'visitor-maps' ); ?>
								</td>
								<td><?php echo '<img src="' . esc_url( Visitor_Maps::$url . 'img/maps/' . $this->set['image_inactive_bot'] ) . '" border="0" alt="' . esc_attr__( 'Inactive Bot', 'visitor-maps' ) . '" title="' . esc_attr__( 'Inactive Bot', 'visitor-maps' ) . '" /> ' . esc_html__( 'Inactive Bot', 'visitor-maps' ); ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<table class="visitor-maps-data">
				<tr>
					<td class="visitors-since">
					<?php // translators: %1$d = Visitor count, %2$s = Date. ?>
						<b><?php echo sprintf( esc_html__( '%1$d visitors since %2$s', 'visitor-maps' ), intval( (int) $numrows ), ( intval( $numrows ) > 0 ) ? gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), (int) $since ) : esc_html__( 'installation', 'visitor-maps' ) ); ?></b>
					</td>
				</tr>
				<tr>
					<td class="visitors-since">
					<?php // phpcs:ignore WordPress ?>
						<b><?php echo esc_html__( 'Last refresh at', 'visitor-maps' ) . ' ' . esc_html__( gmdate( Visitor_Maps::$core->get_option( 'time_format' ) ), current_time( 'timestamp' ) ); ?></b>
					</td>
				</tr>
				<tr>
					<td class="visitors">
						<table class="outer-table">
							<tr>
								<td>
									<table class="inner-table">
										<tr class="table-top">
											<td>&nbsp;</td>
											<td>&nbsp;<?php echo esc_html__( 'Online', 'visitor-maps' ); ?></td>
											<td>&nbsp;<?php echo esc_html__( 'Who', 'visitor-maps' ); ?></td>
											<?php
											if ( $this->set['allow_ip_display'] ) {
												echo '<td>&nbsp;' . esc_html__( 'IP Address', 'visitor-maps' ) . '</td> ';
											}
											?>
											<?php
											if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
												echo '<td>&nbsp;' . esc_html__( 'Location', 'visitor-maps' ) . '</td> ';
											}
											?>
											<td>&nbsp;<?php echo esc_html__( 'Entry', 'visitor-maps' ); ?></td>
											<td>&nbsp;<?php echo esc_html__( 'Last Click', 'visitor-maps' ); ?></td>
											<?php
											// phpcs:ignore WordPress.Security.NonceVerification
											if ( ( $this->set['allow_last_url_display'] ) && ( ! isset( $_GET['nlurl'] ) ) && ( ( $this->set['allow_profile_display'] ) && ( 'none' === $show ) ) ) {
												echo '<td>&nbsp;' . esc_html__( 'Last URL', 'visitor-maps' ) . '</td> ';
											}
											?>
											<?php
											if ( $this->set['allow_referer_display'] ) {
												echo '<td>&nbsp;' . esc_html__( 'Referer', 'visitor-maps' ) . '</td> ';
											}
											?>
										</tr>
										<?php
										// Order by is on Last Click.

										$total_bots            = 0;
										$total_admin           = 0;
										$total_guests          = 0;
										$total_users           = 0;
										$total_dupes           = 0;
										$this->ip_addrs_active = array();
										$ip_addrs              = array();
										$whos_online_arr       = array();
										$even_odd              = 0;

										// phpcs:disable
										$whos_online_arr = $wpdb->get_results(
											$wpdb->prepare(
												'SELECT
		                                        session_id,
		                                        ip_address,
		                                        user_id,
		                                        name,
		                                        nickname,
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
		                                        num_visits
		                                            FROM ' . $wo_table_wo . '
		                                            WHERE time_last_click > %d
		                                            ORDER BY time_last_click DESC',
												$xx_mins_ago
											),
											ARRAY_A
										);
										// phpcs:enable

										$total_sess = 0;
										if ( $whos_online_arr ) { // check of there are any visitors.
											foreach ( $whos_online_arr as $whos_online ) {

												// skip empty row just in case.
												if ( '' === $whos_online['name'] || '' === $whos_online['session_id'] || '' === $whos_online['ip_address'] ) {
													continue;
												}
												$total_sess ++;
												$time_online = ( $whos_online['time_last_click'] - $whos_online['time_entry'] );

												// Check for duplicates.
												if ( in_array( $whos_online['ip_address'], $ip_addrs, true ) ) {
													$total_dupes ++;
												};
												$ip_addrs[] = $whos_online['ip_address'];

												// Display Status
												// Check who it is and set values.
												$is_bot   = false;
												$is_admin = false;
												$is_guest = false;
												$is_user  = false;

												if ( 'Guest' !== $whos_online['name'] && 0 === $whos_online['user_id'] ) {
													$total_bots ++;
													$fg_color = $this->set['color_bot'];
													$is_bot   = true;
												} elseif ( 'Guest' !== $whos_online['name'] && $whos_online['user_id'] > 0 && $this->wo_visitor_ip !== $whos_online['ip_address'] ) {
													$total_users ++;
													$fg_color = $this->set['color_user'];
													$is_user  = true;

													// Admin detection.
												} elseif ( $whos_online['ip_address'] === $this->wo_visitor_ip ) {
													$total_admin ++;
													$total_users ++;
													$fg_color              = $this->set['color_admin'];
													$is_admin              = true;
													$this->set['hostname'] = $whos_online['hostname'];

													// Guest detection (may include Bots not detected by spiders.txt).
												} else {
													$fg_color = $this->set['color_guest'];
													$is_guest = true;
													$total_guests ++;
												}

												if ( ! ( $is_bot && ! $bots ) ) {

													// alternate row colors.
													$row_class  = '';
													$even_class = 'class=column-dark';
													$odd_class  = 'class=column-light';
													if ( $even_odd % 2 ) {
														$row_class = $odd_class;
													} else {
														$row_class = $even_class;
													}
													$even_odd ++;
													?>
													<tr <?php echo esc_html( $row_class ); ?>>
														<!-- Status Light -->
														<td class="status"><?php echo $this->check_status( $whos_online ); ?></td> <?php // phpcs:ignore WordPress.Security.EscapeOutput ?>

														<!-- Time Online -->
														<td class="time-online" style="color:<?php echo esc_attr( $fg_color ); ?>;">
															<?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
															<?php echo $this->time_online( $time_online ); ?>
														</td>

														<!-- Name -->
														<td class="name" style="color:<?php echo esc_attr( $fg_color ); ?>;">
																<?php
																if ( $is_guest ) {
																	echo esc_html__( 'Guest', 'visitor-maps' ) . '&nbsp;';
																} elseif ( $is_user ) {
																	echo '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $whos_online['user_id'] ) ) . '">' . esc_html( $whos_online['name'] ) . '</a>&nbsp;';
																} elseif ( $is_admin ) {
																	echo '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . $whos_online['user_id'] ) ) . '">' . esc_html__( 'You', 'visitor-maps' ) . '</a>&nbsp;';
																	// Check for Bot.
																} elseif ( $is_bot ) {
																	// Tokenize UserAgent and try to find Bots name.
																	$tok = strtok( $whos_online['name'], ' ();/' );
																	while ( false !== $tok ) {
																		if ( strlen( strtolower( $tok ) ) > 3 ) {
																			if ( ! strstr( strtolower( $tok ), 'mozilla' ) && ! strstr( strtolower( $tok ), 'compatible' ) && ! strstr( strtolower( $tok ), 'msie' ) && ! strstr( strtolower( $tok ), 'windows' ) ) {
																				echo esc_html( "$tok" );
																				break;
																			}
																		}
																		$tok = strtok( ' ();/' );
																	}
																} else {
																	echo esc_html__( 'Error', 'visitor-maps' );
																}
																?>
																</td>
															<?php

															if ( $this->set['allow_ip_display'] ) {
																?>

															<!-- IP Address -->
																<td class="ip-address" style="color:<?php esc_attr( $fg_color ); ?>;">&nbsp;
																<?php
																if ( 'unknown' === $whos_online['ip_address'] ) {
																	echo esc_html( $whos_online['ip_address'] );
																} else {
																	$this_nick = '';
																	if ( '' !== $whos_online['nickname'] ) {
																		$this_nick = ' (' . $whos_online['nickname'] . ' - ' . $whos_online['num_visits'] . ' ' . esc_html__( 'visits', 'visitor-maps' ) . ')';
																	}
																	if ( Visitor_Maps::$core->get_option( 'enable_host_lookups' ) ) {
																		$this_host = ( '' !== $whos_online['hostname'] ) ? Visitor_Maps::$core->host_to_domain( $whos_online['hostname'] ) : 'n/a';
																	} else {
																		$this_host = esc_html__( 'host lookups not enabled', 'visitor-maps' );
																	}

																	if ( Visitor_Maps::$core->get_option( 'whois_url_popup' ) ) {
																		echo '<a href="' . esc_url( Visitor_Maps::$core->get_option( 'whois_url' ) . $whos_online['ip_address'] ) . '" onclick="who_is(this.href); return false;" title="' . esc_attr( $this_host ) . '">' . esc_html( $whos_online['ip_address'] ) . esc_html( $this_nick ) . '</a>';
																	} else {
																		echo '<a href="' . esc_url( Visitor_Maps::$core->get_option( 'whois_url' ) . $whos_online['ip_address'] ) . '" title="' . esc_attr( $this_host ) . '" target="_blank">' . esc_html( $whos_online['ip_address'] ) . esc_html( $this_nick ) . '</a>';
																	}
																}
																?>
																</td>
																<?php
															} // end if.

															if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
																?>
																<!-- Country Flag -->
																<td class="flag" style="color:<?php esc_attr( $fg_color ); ?>;">&nbsp;
																<?php
																if ( '' !== $whos_online['country_code'] ) {
																	$whos_online['country_code'] = strtolower( $whos_online['country_code'] );
																	if ( '-' === $whos_online['country_code'] || '--' === $country_code ) { // unknown.
																		echo '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/flags/unknown.png" alt="' . esc_attr__( 'unknown', 'visitor-maps' ) . '" title="' . esc_attr__( 'unknown', 'visitor-maps' ) . '" />';
																	} else {
																		echo '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/flags/' . esc_html( $whos_online['country_code'] ) . '.png" alt="' . esc_attr( $whos_online['country_name'] ) . '" title="' . esc_attr( $whos_online['country_name'] ) . '" />';
																	}
																}

																if ( Visitor_Maps::$core->get_option( 'enable_state_display' ) ) {
																	$newguy = false;
																	if ( is_numeric( $refresh ) && $whos_online['time_entry'] > ( $current_time - absint( $refresh ) ) ) {
																		$newguy = true; // Holds the italicized "new lookup" indication for 1 refresh cycle.
																	}
																	if ( '' !== $whos_online['city_name'] ) {
																		if ( 'us' === $whos_online['country_code'] ) {
																			$whos_online['print'] = $whos_online['city_name'];
																			if ( '' !== $whos_online['state_code'] ) {
																				$whos_online['print'] = $whos_online['city_name'] . ', ' . strtoupper( $whos_online['state_code'] );
																			}
																		} else {      // all non us countries.
																			$whos_online['print'] = $whos_online['city_name'] . ', ' . strtoupper( $whos_online['country_code'] );
																		}
																	} else {
																		$whos_online['print'] = '~ ' . $whos_online['country_name'];
																	}
																	if ( $newguy ) {
																		echo '<em>';
																	}
																	echo esc_html( $whos_online['print'] );
																	if ( $newguy ) {
																		echo '</em>';
																	}
																}
																?>
																</td>
																<?php
															}
															?>

															<!-- Time Entry -->
														<td class="time" style="color:<?php echo esc_attr( $fg_color ); ?>;">&nbsp;
															<?php echo esc_html( gmdate( Visitor_Maps::$core->get_option( 'time_format_hms' ), $whos_online['time_entry'] ) ); ?>
														</td>

														<!-- Last Click -->
														<td class="last-click" style="color:<?php echo esc_attr( $fg_color ); ?>;">&nbsp;
															<?php echo esc_html( gmdate( Visitor_Maps::$core->get_option( 'time_format_hms' ), $whos_online['time_last_click'] ) ); ?>
														</td>

														<?php
														// phpcs:ignore WordPress.Security.NonceVerification
														if ( ( $this->set['allow_last_url_display'] ) && ( ! isset( $_GET['nlurl'] ) ) && ( ( $this->set['allow_profile_display'] ) && ( 'none' === $show ) ) ) {
															?>
														<!-- Last URL -->
														<td class="last-url">&nbsp;
															<?php
															$display_link = $whos_online['last_page_url'];
															// escape any special characters to conform to HTML DTD.
															$temp_url_link = $display_link;
															$uri           = wp_parse_url( get_option( 'siteurl' ) );

															if ( isset( $uri['path'] ) ) {
																$display_link = str_replace( $uri['path'], '', $display_link );
															}

															echo '<a href="' . esc_url( $temp_url_link ) . '" target="_blank">' . esc_html( $display_link ) . '</a>';

															?>
														</td>
															<?php
														} // end if.

														if ( $this->set['allow_referer_display'] ) {
															?>
															<!-- Referer -->
														<td class="referer" style="color:<?php echo esc_attr( $fg_color ); ?>;">&nbsp;
															<?php
															if ( '' === $whos_online['http_referer'] ) {
																echo esc_html__( 'No', 'visitor-maps' );
															} else {
																echo '<a href="' . esc_url( $whos_online['http_referer'] ) . '" target="_blank">' . esc_html__( 'Yes', 'visitor-maps' ) . '</a>';
															}
															?>
														</td>
															<?php
														} // end if.
														?>
													</tr>
													<?php
													// phpcs:ignore WordPress.Security.NonceVerification
													if ( ( $this->set['allow_last_url_display'] ) && ( ( isset( $_GET['nlurl'] ) ) || ( $this->set['allow_profile_display'] && 'none' !== $show ) ) ) {
														?>
														<tr <?php echo esc_html( $row_class ); ?>>
															<?php
															$uri          = wp_parse_url( get_option( 'siteurl' ) );
															$display_link = $whos_online['last_page_url'];

															if ( isset( $uri['path'] ) ) {
																$display_link = str_replace( $uri['path'], '', $display_link );
															}
															?>
															<td style="text-align:left" colspan="8">
																<?php
																echo esc_html__( 'Last URL:', 'visitor-maps' ) . ' 
																<a href="' . esc_url( $whos_online['last_page_url'] ) . '" target="_blank">' . esc_html( $display_link ) . '</a>';
																?>
															</td>
														</tr>
														<?php
													}

													if ( $this->set['allow_profile_display'] ) {
														if ( 'all' === $show || ( 'bots' === $show && $is_bot ) || ( 'guests' === $show && ( $is_guest || $is_admin || $is_user ) ) ) {
															?>
															<tr <?php echo esc_html( $row_class ); ?>>;
															?>
															<td colspan="8"><?php $this->display_details( $whos_online ); ?></td>
															</tr>
															<?php
														}
													}
												}
											}
										}
										?>
										<tr>
											<td colspan="9"><br/>
												<table class="visitor-stats">
													<tr>
														<td style="text-align:right;"><?php echo esc_html( "$total_sess" ); ?></td>
														<?php // translators: %1$d = Inactive minutes, %2$d = Removed minutes. ?>
														<td style="text-align:left;"><?php echo sprintf( esc_html__( 'Visitors online (Considered inactive after %1$d minutes. Removed after %2$d minutes)', 'visitor-maps' ), absint( Visitor_Maps::$core->get_option( 'active_time' ) ), absint( Visitor_Maps::$core->get_option( 'track_time' ) ) ); ?></td>
													</tr>
													<?php
													if ( $total_dupes > 0 ) {
														?>
														<tr>
															<td style="text-align:right;"><?php echo esc_html( "$total_dupes" ); ?></td>
															<td style="text-align:left;"><?php echo esc_html__( 'Duplicate IPs', 'visitor-maps' ); ?></td>
														</tr>
														<?php
													}
													?>
													<tr>
														<td style="text-align:right;"><?php echo esc_html( "$total_users" ); ?></td>
														<td><?php echo esc_html__( 'Members (includes you)', 'visitor-maps' ); ?></td>
													</tr>
													<tr>
														<td style="text-align:right;"><?php echo esc_html( "$total_guests" ); ?></td>
														<td style="color:<?php esc_attr( $this->set['color_guest'] ); ?>">
															<?php
															echo esc_html__( 'Guests', 'visitor-maps' );
															if ( count( $this->ip_addrs_active ) > 0 ) {
																echo ', ' . count( $this->ip_addrs_active ) . ' ' . esc_html__( 'are active', 'visitor-maps' );
															}
															?>
														</td>
													</tr>
													<tr>
														<td style="text-align:right;"><?php echo esc_html( "$total_bots" ); ?></td>
														<td><?php echo esc_html__( 'Bots', 'visitor-maps' ); ?></td>
													</tr>
													<tr>
														<td style="text-align:right;"><?php echo esc_html( "$total_admin" ); ?></td>
														<td><?php echo esc_html__( 'You', 'visitor-maps' ); ?></td>
													</tr>
												</table>
												<br/>
												<?php
												if ( $this->set['allow_ip_display'] ) {
													echo esc_html__( 'Your IP Address:', 'visitor-maps' ) . ' ' . esc_html( $this->wo_visitor_ip );
												}
												if ( Visitor_Maps::$core->get_option( 'enable_host_lookups' ) ) {
													$this_host = ( isset( $this->set['hostname'] ) && '' !== $this->set['hostname'] ) ? Visitor_Maps::$core->host_to_domain( $this->set['hostname'] ) : 'n/a';
													// Display Hostname.
													echo '<br />' . esc_html__( 'Your Host:', 'visitor-maps' ) . ' (' . esc_html( $this_host ) . ') ' . esc_html( ( isset( $this->set['hostname'] ) && '' !== $this->set['hostname'] ) ? $this->set['hostname'] : 'n/a' ) . '<br />';
												}

												// ------------------------ geoip lookup -------------------------.
												if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
													if ( has_action( 'visitor_maps_geoip_view' ) ) {
														$geoip_old = 0;

														// TODO:  Fix this, I think it doesn't compare properly.
														$geoip_file_time   = filemtime( Visitor_Maps::$upload_dir . Visitor_Maps::DATABASE_NAME . Visitor_Maps::DATABASE_EXT );
														$geoip_days_ago    = floor( ( strtotime( gmdate( 'Y-m-d' ) . ' 00:00:00' ) - strtotime( gmdate( 'Y-m-d', $geoip_file_time ) . ' 00:00:00' ) ) / ( 60 * 60 * 24 ) );
														$geoip_begin_month = strtotime( '01-' . gmdate( 'm' ) . '-' . gmdate( 'Y' ) );
var_dump('filetime: ' . $geoip_file_time);
var_dump($geoip_begin_month);
														if ( $geoip_begin_month > $geoip_file_time ) {
															$geoip_old = $this->check_geoip_date( $geoip_file_time );
														}

														// hook for geoip feature.
														do_action( 'visitor_maps_geoip_view', $geoip_old, $geoip_days_ago );
													}
												}
												// ------------------------ geoip lookup -------------------------.
												?>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Check user status.
		 *
		 * @param array $whos_online Who's online.
		 *
		 * @return string
		 */
		private function check_status( $whos_online ) {
			global $wpdb;

			// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			$current_time     = (int) current_time( 'timestamp' );
			$xx_mins_ago_long = ( $current_time - ( Visitor_Maps::$core->get_option( 'active_time' ) * 60 ) );

			if ( 'Guest' !== $whos_online['name'] && 0 === $whos_online['user_id'] ) {   // bot.
				if ( $whos_online['time_last_click'] < $xx_mins_ago_long ) {
					return '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/maps/' . $this->set['image_inactive_bot'] . '" border="0" alt="' . esc_attr__( 'Inactive Bot', 'visitor-maps' ) . '" title="' . esc_attr__( 'Inactive Bot', 'visitor-maps' ) . '" />';
				} else {
					return '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/maps/' . $this->set['image_active_bot'] . '" border="0" alt="' . esc_attr__( 'Active Bot', 'visitor-maps' ) . '" title="' . esc_attr__( 'Active Bot', 'visitor-maps' ) . '" />';
				}
			} else {  // guest.
				if ( $whos_online['time_last_click'] < $xx_mins_ago_long ) {
					return '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/maps/' . $this->set['image_inactive_guest'] . '" border="0" alt="' . esc_attr__( 'Inactive Guest', 'visitor-maps' ) . '" title="' . esc_attr__( 'Inactive Guest', 'visitor-maps' ) . '" />';
				} else {
					if ( ! in_array( $whos_online['ip_address'], $this->ip_addrs_active, true ) ) {
						if ( $whos_online['ip_address'] !== $this->wo_visitor_ip ) {
							$this->ip_addrs_active[] = $whos_online['ip_address'];
						}
					}

					return '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/maps/' . $this->set['image_active_guest'] . '" border="0" alt="' . esc_attr__( 'Active Guest', 'visitor-maps' ) . '" title="' . esc_attr__( 'Active Guest', 'visitor-maps' ) . '" />';
				}
			}
		}

		/**
		 * Display details.
		 *
		 * @param array $whos_online Who's online.
		 */
		private function display_details( $whos_online ) {
			// Display User Agent.
			echo esc_html__( 'User Agent:', 'visitor-maps' ) . ' ' . esc_html( wordwrap( $whos_online['user_agent'], $this->set['useragent_wordwrap_chars'], '<br />', true ) );
			echo '<br />';

			if ( Visitor_Maps::$core->get_option( 'enable_host_lookups' ) ) {
				$this_host = ( '' !== $whos_online['hostname'] ) ? Visitor_Maps::$core->host_to_domain( $whos_online['hostname'] ) : 'n/a';

				echo esc_html__( 'Host:', 'visitor-maps' ) . ' (' . esc_html( $this_host ) . ') ' . esc_html( $whos_online['hostname'] );
				echo '<br />';
			}

			if ( '' !== $whos_online['http_referer'] ) {
				echo esc_html__( 'Referer:', 'visitor-maps' ) . ' <a href="' . esc_url( $whos_online['http_referer'] ) . '" target="_blank">' . esc_html( wordwrap( $whos_online['http_referer'], $this->set['referer_wordwrap_chars'], '<br />', true ) ) . '</a>';
				echo '<br />';
			}

			echo '<br class="clear" />';
		}

		/**
		 * Outputs the combo boxes.
		 *
		 * @param string $name Select name.
		 * @param array  $values Select values.
		 * @param string $default Default value.
		 * @param string $parameters Field parameters.
		 * @param bool   $required Is required.
		 *
		 * @return string
		 */
		private function draw_pull_down_menu( $name, $values, $default = '', $parameters = '', $required = false ) {
			$field = '<select name="' . esc_attr( $name ) . '"';

			if ( ! empty( $parameters ) ) {
				$field .= ' ' . $parameters;
			}

			$field .= '>' . "\n";

			if ( empty( $default ) && isset( $_GET['wo_view_nonce'] ) && wp_verify_nonce( sanitize_key( $_GET['wo_view_nonce'] ), 'wo_view' ) && ( ( isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] ) ) || ( isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) ) ) ) {
				if ( isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] ) ) {
					$default = sanitize_text_field( wp_unslash( $_GET[ $name ] ) );
				} elseif ( isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) ) {
					$default = sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
				}
			}

			for ( $i = 0, $n = count( $values ); $i < $n; $i ++ ) {
				$field .= '<option value="' . esc_attr( $values[ $i ]['id'] ) . '"';
				if ( $default === $values[ $i ]['id'] ) {
					$field .= ' selected="selected"';
				}

				$field .= '>' . esc_html( $values[ $i ]['text'] ) . '</option>' . "\n";
			}

			$field .= '</select>' . "\n";

			if ( true === $required ) {
				$field .= 'Required';
			}

			return $field;
		}

		/**
		 * Total time online.
		 *
		 * @param string $time_online Time online.
		 *
		 * @return string
		 */
		private function time_online( $time_online ) {
			$hrs         = (int) intval( $time_online / 3600 );
			$time_online = (int) intval( $time_online - ( 3600 * $hrs ) );
			$mns         = (int) intval( $time_online / 60 );
			$time_online = (int) intval( $time_online - ( 60 * $mns ) );
			$secs        = (int) intval( $time_online / 1 );

			return sprintf( '%02d:%02d:%02d', $hrs, $mns, $secs );
		}

		/**
		 * Check GeoIP date.
		 *
		 * @param string $geoip_file_time Filetime.
		 *
		 * @return int
		 */
		private function check_geoip_date( $geoip_file_time ) {
			global $wpdb;

			// checking for a newer maxmind geo database update file
			// Maxmind usually updates their file on the 1st of the month, but sometimes it is the 2nd, or 3rd of the month.
			// Now it only notifies you when there actually is a new file available.

			$wo_table_ge = $wpdb->prefix . 'visitor_maps_ge';

			// check timestamp.
			// phpcs:disable
			$time_last_check = $wpdb->get_var( 'SELECT time_last_check FROM ' . $wo_table_ge );
			// phpcs:enable

			// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			$current_time = (int) current_time( 'timestamp' );

			// phpcs:disable
			// was a timestamp there?
			if ( ! $time_last_check ) {
				// jump start the timestamp now.
				$time_last_check = $current_time - ( 7 * 60 * 60 );
				$wpdb->query( 'INSERT INTO ' . $wo_table_ge . " (`time_last_check`) VALUES ('" . absint( $time_last_check ) . "');" );
			}

			// have I checked this already in the last 6 hours?
			if ( $time_last_check < $current_time - ( 6 * 60 * 60 ) ) { // $time_last_check more than 6 hours ago.
				// time to check it again, reset the needs_update flag first.
				$wpdb->query( 'UPDATE ' . $wo_table_ge . " SET needs_update = '0'" );

				// get last updated time of the maxmind geo database remote file.
				$remote_file_time = Visitor_Maps::$core->http_last_mod( Visitor_Maps::REMOTE_DATABASE, 1 );
			} else {
				// using the cached results
				// check needs_update flag.
				$update_flag = $wpdb->get_var( 'SELECT needs_update FROM ' . $wo_table_ge );

				return 1 === $update_flag ? 1 : 0;
			}

			// set a new timestamp.
			$wpdb->query( 'UPDATE ' . $wo_table_ge . " SET time_last_check = '" . $current_time . "'" );
			// phpcs:enable

			// sanity check the remote date.
			if ( $remote_file_time < ( $current_time - ( 365 * 24 * 60 * 60 ) ) ) { // $remote_file_time less than 1 year ago
				echo 'Warning: The last modified date of the Maxmind GeoLiteCity database' . esc_html( $remote_file_time ) . 'is out of expected range<br />';

				return 0;
			}

			if ( $remote_file_time > $geoip_file_time ) {
				// set needs_update flag.
				// phpcs:disable
				$wpdb->query( 'UPDATE ' . $wo_table_ge . " SET needs_update = '1'" );
				// phpcs:enable

				return 1;
			}

			return 0;
		}
	}
}
