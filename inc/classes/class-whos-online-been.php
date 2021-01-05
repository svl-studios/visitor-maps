<?php
/**
 * Who's Been Online View Class
 *
 * @class   Whos_Online_View
 * @version 2.0.0
 * @package VisitorMaps
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Whos_Online_Been' ) ) {
	/**
	 * Class Whos_Online_Been
	 */
	class Whos_Online_Been {

		/**
		 * Visitor's IP Address.
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
		 * View Who's Been ONline page.
		 */
		public function view_whos_been_online() {
			global $wpdb;

			$wo_table_wo = $wpdb->prefix . 'visitor_maps_wo';

			// defaults.
			$wo_prefs_arr = array(
				'bots'    => '0',
				'sort_by' => 'time',
				'order'   => 'desc',
				'show'    => 'none',
			);

			$wo_prefs_arr = get_option( 'visitor_maps_wobp' );
			if ( ( ! $wo_prefs_arr ) || ! is_array( $wo_prefs_arr ) ) {
				// install the option defaults.
				update_option( 'visitor_maps_wobp', $wo_prefs_arr );
			}
			$wo_prefs_arr = get_option( 'visitor_maps_wobp', $wo_prefs_arr );

			$show_arr   = array();
			$show_arr[] = array(
				'id'   => 'none',
				'text' => esc_attr__( 'None', 'visitor-maps' ),
			);

			$show_arr[] = array(
				'id'   => 'all',
				'text' => esc_attr__( 'All', 'visitor-maps' ),
			);

			$show_arr[] = array(
				'id'   => 'bots',
				'text' => esc_attr__( 'Bots', 'visitor-maps' ),
			);

			$show_arr[] = array(
				'id'   => 'guests',
				'text' => esc_attr__( 'Guests', 'visitor-maps' ),
			);

			if ( isset( $_GET['wo_been_nonce'] ) ) {
				if ( ! wp_verify_nonce( sanitize_key( $_GET['wo_been_nonce'] ), 'wo_been' ) ) {
					return;
				}
			}

			$show = ( isset( $wo_prefs_arr['show'] ) ) ? $wo_prefs_arr['show'] : 'none';
			if ( isset( $_GET['show'] ) ) {
				$get_show = sanitize_key( $_GET['show'] );
				if ( in_array( $get_show, array( 'none', 'all', 'bots', 'guests' ), true ) ) {
					$wo_prefs_arr['show'] = $get_show;
					$show                 = $get_show;
				}
			}

			$sort_by_arr   = array();
			$sort_by_arr[] = array(
				'id'   => 'who',
				'text' => esc_attr__( 'Who', 'visitor-maps' ),
			);

			$sort_by_arr[] = array(
				'id'   => 'visits',
				'text' => esc_attr__( 'Visits', 'visitor-maps' ),
			);

			$sort_by_arr[] = array(
				'id'   => 'time',
				'text' => esc_attr__( 'Last Visit', 'visitor-maps' ),
			);

			$sort_by_arr[] = array(
				'id'   => 'ip',
				'text' => esc_attr__( 'IP Address', 'visitor-maps' ),
			);

			$sort_by_arr[] = array(
				'id'   => 'location',
				'text' => esc_attr__( 'Location', 'visitor-maps' ),
			);

			$sort_by_arr[] = array(
				'id'   => 'url',
				'text' => esc_attr__( 'Last URL', 'visitor-maps' ),
			);

			$sort_by_ar             = array();
			$sort_by_ar['who']      = 'name';
			$sort_by_ar['visits']   = 'num_visits';
			$sort_by_ar['time']     = 'time_last_click';
			$sort_by_ar['ip']       = 'ip_address';
			$sort_by_ar['location'] = 'country_name, city_name';
			$sort_by_ar['url']      = 'last_page_url';

			$sort_by = ( isset( $wo_prefs_arr['sort_by'] ) ) ? $wo_prefs_arr['sort_by'] : 'time';

			if ( isset( $_GET['sort_by'] ) ) {
				$get_sort_by = sanitize_key( $_GET['sort_by'] );
				if ( in_array( $get_sort_by, array( 'who', 'visits', 'time', 'ip', 'location', 'url' ), true ) ) {
					$wo_prefs_arr['sort_by'] = $get_sort_by;
					$sort_by                 = $get_sort_by;
				}
			}

			$order_arr   = array();
			$order_arr[] = array(
				'id'   => 'desc',
				'text' => esc_attr__( 'Descending', 'visitor-maps' ),
			);

			$order_arr[] = array(
				'id'   => 'asc',
				'text' => esc_attr__( 'Ascending', 'visitor-maps' ),
			);

			$order_ar         = array();
			$order_ar['desc'] = 'DESC';
			$order_ar['asc']  = 'ASC';

			$order = ( isset( $wo_prefs_arr['order'] ) ) ? $wo_prefs_arr['order'] : 'desc';
			if ( isset( $_GET['order'] ) ) {
				$get_order = sanitize_key( $_GET['order'] );
				if ( in_array( $get_order, array( 'desc', 'asc' ), true ) ) {
					$wo_prefs_arr['order'] = $get_order;
					$order                 = $get_order;
				}
			}

			if ( 'asc' === $order && 'location' === $sort_by ) {
				$order_ar['asc']        = '';
				$sort_by_ar['location'] = 'country_name ASC, city_name ASC';
			}

			if ( 'desc' === $order && 'location' === $sort_by ) {
				$order_ar['desc']       = '';
				$sort_by_ar['location'] = 'country_name DESC, city_name DESC';
			}

			$bots_type   = array();
			$bots_type[] = array(
				'id'   => '0',
				'text' => esc_attr__( 'No', 'visitor-maps' ),
			);

			$bots_type[] = array(
				'id'   => '1',
				'text' => esc_attr__( 'Yes', 'visitor-maps' ),
			);

			$bots = ( isset( $wo_prefs_arr['bots'] ) ) ? $wo_prefs_arr['bots'] : '0';

			if ( isset( $_GET['bots'] ) ) {
				$get_bots = sanitize_key( $_GET['bots'] );
				if ( in_array( $get_bots, array( '0', '1' ), true ) ) {
					$wo_prefs_arr['bots'] = $get_bots;
					$bots                 = $get_bots;
				}
			}

			// save settings.
			update_option( 'visitor_maps_wobp', $wo_prefs_arr );

			$this->set                           = array();
			$this->set['allow_refresh']          = 1;
			$this->set['allow_profile_display']  = 1;
			$this->set['allow_ip_display']       = 1;
			$this->set['allow_last_url_display'] = 1;
			$this->set['allow_referer_display']  = 1;

			// three of the strings can be auto wordwrapped.
			$this->set['lasturl_wordwrap_chars']   = 100; // <= set to number of characters to wrap to
			$this->set['useragent_wordwrap_chars'] = 100; // <= set to number of characters to wrap to
			$this->set['referer_wordwrap_chars']   = 100; // <= set to number of characters to wrap to
			// Text colors used for table entries - different colored text for different users
			// Named colors and #Hex values should work fine.
			$this->set['color_bot']   = 'maroon';
			$this->set['color_admin'] = 'darkblue';
			$this->set['color_guest'] = 'green';
			$this->set['color_user']  = 'blue';

			// status image names
			// just image names only, do not add any paths.
			$this->set['image_active_guest']   = 'active_user.gif'; // active user.
			$this->set['image_inactive_guest'] = 'inactive_user.gif'; // inactive user.
			$this->set['image_active_bot']     = 'active_bot.gif'; // active bot.
			$this->set['image_inactive_bot']   = 'inactive_bot.gif'; // inactive bot.
			$this->set['geolite_path']         = dirname( __FILE__ ) . '/';
			$this->wo_visitor_ip               = Visitor_Maps::$core->get_ip_address();

			// http://www.tonymarston.net/php-mysql/pagination.html.
			if ( isset( $_GET['pageno'] ) && is_numeric( $_GET['pageno'] ) ) {
				$pageno = sanitize_text_field( wp_unslash( $_GET['pageno'] ) );
			} else {
				$pageno = 1;
			}

			// phpcs:disable
			$numrows       = $wpdb->get_var( 'SELECT count(*) FROM ' . $wo_table_wo );
			$since         = $wpdb->get_var( 'SELECT time_last_click FROM ' . $wo_table_wo . ' ORDER BY time_last_click ASC LIMIT 1' );
			// phpcs:enable

			$rows_per_page = 25;
			$lastpage      = ceil( $numrows / $rows_per_page );
			$pageno        = (int) $pageno;

			if ( $pageno > $lastpage ) {
				$pageno = $lastpage;
			}
			if ( $pageno < 1 ) {
				$pageno = 1;
			}

			$limit     = 'LIMIT ' . ( $pageno - 1 ) * $rows_per_page . ',' . $rows_per_page;
			$getstring = '&amp;show=' . esc_attr( $show ) . '&amp;order=' . esc_attr( $order ) . '&amp;sort_by=' . esc_attr( $sort_by ) . '&amp;bots=' . esc_attr( $bots );
			?>
			<table class="visitor-map-actions">
				<tr>
					<td>
						<?php
						echo '<form name="wo_been" action="' . esc_url( admin_url( 'admin.php?page=whos-been-online' ) ) . '" method="get">';
						echo '<input type="hidden" name="wo_been_nonce" value="' . esc_attr( wp_create_nonce( 'wo_been' ) ) . '">';

						if ( $this->set['allow_profile_display'] ) {
							// phpcs:ignore WordPress.Security.EscapeOutput
							echo esc_html__( 'Profile Display:', 'visitor-maps' ) . ' ' . $this->draw_pull_down_menu( 'show', $show_arr, $show, 'onchange="this.form.submit();"' ) . ' ';
						}

						// phpcs:ignore WordPress.Security.EscapeOutput
						echo esc_html__( 'Sort:', 'visitor-maps' ) . ' ' . $this->draw_pull_down_menu( 'sort_by', $sort_by_arr, $sort_by, 'onchange="this.form.submit();"' ) . ' ';

						// phpcs:ignore WordPress.Security.EscapeOutput
						echo $this->draw_pull_down_menu( 'order', $order_arr, $order, 'onchange="this.form.submit();"' ) . ' ';

						// phpcs:ignore WordPress.Security.EscapeOutput
						echo esc_html__( 'Show Bots:', 'visitor-maps' ) . ' ' . $this->draw_pull_down_menu( 'bots', $bots_type, $bots, 'onchange="this.form.submit();"' ) . '<br />';

						echo '<input type="hidden" name="page" value="whos-been-online" />';
						echo '</form>';
						echo '<a href="' . esc_url( admin_url( 'admin.php?page=visitor-maps' ) ) . '">' . esc_html__( 'Who\'s Online', 'visitor-maps' ) . "</a>\n";

						if ( current_user_can( 'manage_options' ) ) {
							echo '<br /> <a href="' . esc_url( admin_url( 'options-general.php?page=visitor_maps_opt' ) ) . '">' . esc_html__( 'Visitor Maps Options', 'visitor-maps' ) . '</a>';
						}
						if ( Visitor_Maps::$core->get_option( 'enable_location_plugin', true ) ) {
							echo '<br /><a onclick="wo_map_console(this.href); return false;" href="' . esc_url( get_bloginfo( 'url' ) ) . '?wo_map_console=1">' . esc_html__( 'Visitor Map Viewer', 'visitor-maps' ) . '</a>';
						}
						?>
					</td>

					<td>
						<table class="visitor-map-key">
							<tr>
								<td><?php echo '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/maps/' . esc_attr( $this->set['image_active_guest'] ) . '" border="0" alt="' . esc_attr__( 'Active Guest', 'visitor-maps' ) . '" title="' . esc_attr__( 'Active Guest', 'visitor-maps' ) . '" /> ' . esc_html__( 'Active Guest', 'visitor-maps' ); ?>
								</td>
								<td><?php echo '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/maps/' . esc_attr( $this->set['image_inactive_guest'] ) . '" border="0" alt="' . esc_attr__( 'Inactive Guest', 'visitor-maps' ) . '" title="' . esc_attr__( 'Inactive Guest', 'visitor-maps' ) . '" /> ' . esc_html__( 'Inactive Guest', 'visitor-maps' ); ?>
								</td>
							</tr>
							<tr>
								<td><?php echo '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/maps/' . esc_attr( $this->set['image_active_bot'] ) . '" border="0" alt="' . esc_attr__( 'Active Bot', 'visitor-maps' ) . '" title="' . esc_attr__( 'Active Bot', 'visitor-maps' ) . '" /> ' . esc_html__( 'Active Bot', 'visitor-maps' ); ?>
								</td>
								<td><?php echo '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/maps/' . esc_attr( $this->set['image_inactive_bot'] ) . '" border="0" alt="' . esc_attr__( 'Inactive Bot', 'visitor-maps' ) . '" title="' . esc_attr__( 'Inactive Bot', 'visitor-maps' ) . '" /> ' . esc_html__( 'Inactive Bot', 'visitor-maps' ); ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>

			<table class="visitor-maps-data">
				<tr>
					<td class="visitors-since">
						<?php // translators: %1$d = visitor count, %2$s = date. ?>
						<b><?php echo sprintf( esc_html__( '%1$d visitors since %2$s', 'visitor-maps' ), (int) $numrows, ( intval( $numrows ) > 0 ) ? gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), (int) $since ) : esc_html__( 'installation', 'visitor-maps' ) ); ?></b>
					</td>
				</tr>
				<tr>
					<td class="visitors-pagination">
						<?php
						if ( 1 === $pageno ) {
							echo ' &laquo;' . esc_html__( 'FIRST', 'visitor-maps' ) . ' &lsaquo;' . esc_html__( 'PREV', 'visitor-maps' );
						} else {
							// phpcs:ignore WordPress.Security.EscapeOutput
							echo ' <a href=' . esc_url( admin_url( 'admin.php?page=whos-been-online' ) ) . '&amp;pageno=1' . $getstring . '>&laquo;' . esc_html__( 'FIRST', 'visitor-maps' ) . '</a> ';
							$prevpage = $pageno - 1;

							// phpcs:ignore WordPress.Security.EscapeOutput
							echo ' <a href=' . esc_url( admin_url( 'admin.php?page=whos-been-online' ) ) . '&amp;pageno=$prevpage' . $getstring . '>&lsaquo;' . esc_html__( 'PREV', 'visitor-maps' ) . '</a> ';
						}

						// translators: %1$d = page num, %2$d = last page.
						echo ' (' . sprintf( esc_html__( 'Page %1$d of %2$d', 'visitor-maps' ), intval( $pageno ), intval( $lastpage ) ) . ') ';

						if ( $pageno === $lastpage ) {
							echo ' ' . esc_html__( 'NEXT', 'visitor-maps' ) . '&rsaquo; ' . esc_html__( 'LAST', 'visitor-maps' ) . '&raquo; ';
						} else {
							$nextpage = $pageno + 1;

							// phpcs:ignore WordPress.Security.EscapeOutput
							echo ' <a href=' . esc_url( admin_url( 'admin.php?page=whos-been-online' ) ) . '&amp;pageno=' . $nextpage . $getstring . '>' . esc_html__( 'NEXT', 'visitor-maps' ) . '&rsaquo;</a> ';

							// phpcs:ignore WordPress.Security.EscapeOutput
							echo ' <a href=' . esc_url( admin_url( 'admin.php?page=whos-been-online' ) ) . '&amp;pageno=' . $lastpage . $getstring . '>' . esc_html__( 'LAST', 'visitor-maps' ) . '&raquo;</a> ';
						}
						?>
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
											<td>&nbsp;<?php echo esc_html__( 'Who', 'visitor-maps' ); ?></td>
											<td>&nbsp;<?php echo esc_html__( 'Visits', 'visitor-maps' ); ?></td>
											<td>&nbsp;<?php echo esc_html__( 'Last Visit', 'visitor-maps' ); ?></td>
											<?php
											if ( $this->set['allow_ip_display'] ) {
												echo '<td>&nbsp;' . esc_html__( 'IP Address', 'visitor-maps' ) . '</td> ';
											}

											if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
												echo '<td>&nbsp;' . esc_html__( 'Location', 'visitor-maps' ) . '</td> ';
											}

											if ( ( $this->set['allow_last_url_display'] ) && ( ! isset( $_GET['nlurl'] ) ) && ( $this->set['allow_profile_display'] && 'none' === $show ) ) {
												echo '<td>&nbsp;' . esc_html__( 'Last URL', 'visitor-maps' ) . '</td> ';
											}

											if ( $this->set['allow_referer_display'] ) {
												echo '<td>&nbsp;' . esc_html__( 'Referer', 'visitor-maps' ) . '</td> ';
											}
											?>
										</tr>

										<?php
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
		                                                ORDER BY %1$s %2$s ' . $limit,
												$sort_by_ar[ $sort_by ],
												$order_ar[ $order ]
											),
											ARRAY_A
										);
										// phpcs:enable

										$total_sess = 0;
										if ( $whos_online_arr ) { // check of there are any visitors.
											foreach ( $whos_online_arr as $whos_online ) {
												if ( '' === $whos_online['name'] || '' === $whos_online['session_id'] || '' === $whos_online['ip_address'] ) {
													continue;
												}

												$total_sess ++;
												$time_online = ( $whos_online['time_last_click'] - $whos_online['time_entry'] );

												if ( in_array( $whos_online['ip_address'], $ip_addrs, true ) ) {
													$total_dupes ++;
												};
												$ip_addrs[] = $whos_online['ip_address'];

												$is_bot   = false;
												$is_admin = false;
												$is_guest = false;
												$is_user  = false;

												if ( 'Guest' !== $whos_online['name'] && 0 === $whos_online['user_id'] ) {
													$total_bots ++;
													$fg_color = $this->set['color_bot'];
													$is_bot   = true;
												} elseif ( 'Guest' !== $whos_online['name'] && $whos_online['user_id'] > 0 && $whos_online['ip_address'] !== $this->wo_visitor_ip ) {
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
													<?php // phpcs:ignore WordPress.Security.EscapeOutput ?>
													<td class="status"><?php echo $this->check_status( $whos_online ); ?></td>

													<!-- Name -->
													<?php
													echo '<td class="name" style="color:' . esc_attr( $fg_color ) . ';">&nbsp;';

													if ( $is_guest ) {
														echo esc_html__( 'Guest', 'visitor-maps' ) . '&nbsp;';
													} elseif ( $is_user ) {
														echo '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . (int) $whos_online['user_id'] ) ) . '">' . esc_html( $whos_online['name'] ) . '</a>&nbsp;';
													} elseif ( $is_admin ) {
														echo '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . (int) $whos_online['user_id'] ) ) . '">' . esc_html__( 'You', 'visitor-maps' ) . '</a>&nbsp;';
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
													echo '</td>';

													if ( $this->set['allow_ip_display'] ) {
														?>
														<!-- Visits -->
														<td class="visits" style="color:<?php echo esc_attr( $fg_color ); ?>;">&nbsp;
															<?php echo esc_html( $whos_online['num_visits'] ); ?>
														</td>

														<!-- Last Visit -->
														<td class="last-visit" style="color:<?php echo esc_attr( $fg_color ); ?>;">&nbsp;<?php echo esc_html( gmdate( Visitor_Maps::$core->get_option( 'date_time_format' ), $whos_online['time_last_click'] ) ); ?>
														</td>

														<!-- IP Address -->
														<td class="ip-address" style="color:<?php echo esc_attr( $fg_color ); ?>;">&nbsp;
														<?php
														if ( 'unknown' === $whos_online['ip_address'] ) {
															echo esc_html( $whos_online['ip_address'] );
														} else {
															$this_nick = '';

															if ( null !== $whos_online['nickname'] ) {
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
														echo '</td>';
													}

													if ( Visitor_Maps::$core->get_option( 'enable_location_plugin' ) ) {
														?>
														<!-- Country Flag -->
														<td class="flag" style="color:<?php echo esc_attr( $fg_color ); ?>;">&nbsp;
														<?php
														if ( '' !== $whos_online['country_code'] ) {
															$country_code                = sanitize_key( $whos_online['country_code'] );
															$country_code                = strtolower( $country_code );
															$whos_online['country_code'] = strtolower( $whos_online['country_code'] );

															if ( '-' === $country_code || '--' === $country_code ) { // unknown.
																echo '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/flags/unknown.png" alt="' . esc_attr( esc_html__( 'unknown', 'visitor-maps' ) ) . '" title="' . esc_attr__( 'unknown', 'visitor-maps' ) . '" />';
															} else {
																echo '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/flags/' . esc_attr( $country_code ) . '.png" alt="' . esc_attr( $whos_online['country_name'] ) . '" title="' . esc_attr( $whos_online['country_name'] ) . '" />';
															}
														}

														if ( Visitor_Maps::$core->get_option( 'enable_state_display' ) ) {
															$newguy = false;
															if ( isset( $_GET['refresh'] ) && is_numeric( $_GET['refresh'] ) && $whos_online['time_entry'] > ( time() - absint( $_GET['refresh'] ) ) ) {
																$newguy = true; // Holds the italicized "new lookup" indication for 1 refresh cycle.
															}

															if ( '-' !== $whos_online['city_name'] ) {
																if ( 'us' === $whos_online['country_code'] ) {
																	$whos_online['print'] = $whos_online['city_name'];

																	if ( '' !== $whos_online['state_code'] ) {
																		$whos_online['print'] = $whos_online['city_name'] . ', ' . strtoupper( $whos_online['state_code'] );
																	}
																} else {      // all non us countries.
																	$whos_online['print'] = $whos_online['city_name'] . ', ' . strtoupper( $whos_online['country_code'] );
																}
															} else {
																$whos_online['print'] = '~ ' . ( '-' !== $whos_online['country_name'] ? $whos_online['country_name'] : '' );
															}

															if ( $newguy ) {
																echo '<em>';
															}

															echo esc_html( ' ' . $whos_online['print'] );

															if ( $newguy ) {
																echo '</em>';
															}
														}

														echo '</td>';
													}

													if ( ( $this->set['allow_last_url_display'] ) && ( ! isset( $_GET['nlurl'] ) ) && ( $this->set['allow_profile_display'] && 'none' === $show ) ) {
														?>
														<!-- Last URL -->
														<td class="last-url">&nbsp;
														<?php
														$display_link = $whos_online['last_page_url'];

														$temp_url_link = $display_link;
														$uri           = wp_parse_url( get_option( 'siteurl' ) );

														if ( isset( $uri['path'] ) ) {
															$display_link = str_replace( $uri['path'], '', $display_link );
														}

														echo '<a href="' . esc_url( $temp_url_link ) . '" target="_blank">' . esc_html( $display_link ) . '</a>';
														echo '</td>';
													}

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
													}
													?>
													</tr>
													<?php
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
															<?php echo esc_html__( 'Last URL:', 'visitor-maps' ) . ' <a href="' . esc_url( $whos_online['last_page_url'] ) . '" target="_blank">' . esc_html( $display_link ) . '</a>'; ?></td>
														</tr>
														<?php
													}

													if ( $this->set['allow_profile_display'] ) {
														if ( ( 'all' === $show ) || ( ( 'bots' === $show ) && $is_bot ) || ( ( 'guests' === $show ) && ( $is_guest || $is_admin || $is_user ) ) ) {
															?>
															<tr <?php echo esc_html( $row_class ); ?>>
															<td colspan="8"><?php $this->display_details( $whos_online ); ?></td>
															</tr>
															<?php
														}
													}
												}
											}
										}
										?>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="visitors-pagination">
						<?php
						if ( 1 === $pageno ) {
							echo ' &laquo;' . esc_html__( 'FIRST', 'visitor-maps' ) . ' &lsaquo;' . esc_html__( 'PREV', 'visitor-maps' );
						} else {
							// phpcs:ignore WordPress.Security.EscapeOutput
							echo ' <a href=' . esc_url( admin_url( 'admin.php?page=whos-been-online' ) ) . '&amp;pageno=1' . $getstring . '>&laquo;' . esc_html__( 'FIRST', 'visitor-maps' ) . '</a> ';
							$prevpage = $pageno - 1;

							// phpcs:ignore WordPress.Security.EscapeOutput
							echo ' <a href=' . esc_url( admin_url( 'admin.php?page=whos-been-online' ) ) . '&amp;pageno=' . $prevpage . $getstring . '>&lsaquo;' . esc_html__( 'PREV', 'visitor-maps' ) . '</a> ';
						}

						// translators: %1$d = page number, %2$d = last page.
						echo ' (' . sprintf( esc_html__( 'Page %1$d of %2$d', 'visitor-maps' ), intval( $pageno ), intval( $lastpage ) ) . ') ';

						if ( $lastpage === $pageno ) {
							echo ' ' . esc_html__( 'NEXT', 'visitor-maps' ) . '&rsaquo; ' . esc_html__( 'LAST', 'visitor-maps' ) . '&raquo; ';
						} else {
							$nextpage = $pageno + 1;

							// phpcs:ignore WordPress.Security.EscapeOutput
							echo ' <a href=' . esc_url( admin_url( 'admin.php?page=whos-been-online' ) ) . '&amp;pageno=' . $nextpage . $getstring . '>' . esc_html__( 'NEXT', 'visitor-maps' ) . '&rsaquo;</a> ';

							// phpcs:ignore WordPress.Security.EscapeOutput
							echo ' <a href=' . esc_url( admin_url( 'admin.php?page=whos-been-online' ) ) . '&amp;pageno=' . $lastpage . $getstring . '>' . esc_html__( 'LAST', 'visitor-maps' ) . '&raquo;</a> ';
						}
						?>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Check user status.
		 *
		 * @param array $whos_online Who's Online.
		 *
		 * @return string
		 */
		private function check_status( $whos_online ) {
			global $wpdb;

			// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			$current_time = (int) current_time( 'timestamp' );

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
						if ( $this->wo_visitor_ip !== $whos_online['ip_address'] ) {
							$this->ip_addrs_active[] = $whos_online['ip_address'];
						}
					}

					return '<img src="' . esc_url( Visitor_Maps::$url ) . 'img/maps/' . $this->set['image_active_guest'] . '" border="0" alt="' . esc_attr__( 'Active Guest', 'visitor-maps' ) . '" title="' . esc_attr__( 'Active Guest', 'visitor-maps' ) . '" />';
				}
			}
		}

		/**
		 * Displat user details.
		 *
		 * @param array $whos_online Who's online.
		 */
		private function display_details( $whos_online ) {
			echo esc_html__( 'User Agent:', 'visitor-maps' ) . ' ' . esc_html( wordwrap( esc_html( $whos_online['user_agent'] ), $this->set['useragent_wordwrap_chars'], '<br />', true ) );
			echo '<br />';

			if ( Visitor_Maps::$core->get_option( 'enable_host_lookups' ) ) {
				$this_host = ( '' !== $whos_online['hostname'] ) ? Visitor_Maps::$core->host_to_domain( $whos_online['hostname'] ) : 'n/a';

				echo esc_html__( 'Host:', 'visitor-maps' ) . ' (' . esc_html( $this_host ) . ') ' . esc_html( $whos_online['hostname'] );
				echo '<br />';
			}

			// Display Referer if available.
			if ( '' !== $whos_online['http_referer'] ) {
				echo esc_html__( 'Referer:', 'visitor-maps' ) . ' <a href="' . esc_url( $whos_online['http_referer'] ) . '" target="_blank">' . esc_html( wordwrap( $whos_online['http_referer'], $this->set['referer_wordwrap_chars'], '<br />', true ) ) . '</a>';
				echo '<br />';
			}

			echo '<br class="clear" />';
		}

		/**
		 * Render combo boxes.
		 *
		 * @param string $name Select name.
		 * @param array  $values Select values.
		 * @param string $default Default value.
		 * @param string $parameters Params.
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

			if ( empty( $default ) && isset( $_GET['wo_been_nonce'] ) && wp_verify_nonce( sanitize_key( $_GET['wo_been_nonce'] ), 'wo_been' ) && ( ( isset( $_GET[ $name ] ) && is_string( $_GET[ $name ] ) ) || ( isset( $_POST[ $name ] ) && is_string( $_POST[ $name ] ) ) ) ) {
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

				$field .= '>' . esc_attr( $values[ $i ]['text'] ) . '</option>' . "\n";
			}

			$field .= '</select>' . "\n";

			if ( true === $required ) {
				$field .= 'Required';
			}

			return $field;
		}

		/**
		 * Calc time online.
		 *
		 * @param int $time_online Time online.
		 *
		 * @return string
		 */
		private function time_online( $time_online ) {
			// takes a time diff in secs and formats to 01:48:08  (hrs:min:secs).
			$hrs         = (int) intval( $time_online / 3600 );
			$time_online = (int) intval( $time_online - ( 3600 * $hrs ) );
			$mns         = (int) intval( $time_online / 60 );
			$time_online = (int) intval( $time_online - ( 60 * $mns ) );
			$secs        = (int) intval( $time_online / 1 );

			return sprintf( '%02d:%02d:%02d', $hrs, $mns, $secs );
		}
	}
}
