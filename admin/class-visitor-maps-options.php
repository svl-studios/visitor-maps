<?php
/**
 * Options Class
 *
 * @class Visitor_Maps_Options
 * @version 2.0.0
 * @package VisitorMaps
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Visitor_Maps_Options' ) ) {

	/**
	 * Class Visitor_Maps_Options
	 */
	class Visitor_Maps_Options {

		/**
		 * Visitor_Maps_Options constructor.
		 */
		public function __construct() {
			if ( class_exists( 'Redux' ) ) {
				$this->set_options();
			}

			add_filter( 'wp_kses_allowed_html', array( $this, 'allowed_html' ), 10, 2 );
		}

		/**
		 * Callback for allowed HTML.
		 *
		 * @param array|string $tags   Tags for consideration.
		 * @param string       $unused Tags not to use.
		 *
		 * @return array|string
		 */
		public function allowed_html( array $tags, string $unused ) {
			$tags['div']['data-nonce']  = true;
			$tags['div']['data-update'] = true;
			$tags['a']['aria-label']    = true;

			return $tags;
		}

		/**
		 * Check for GeoLiteCity update based on time passed.
		 *
		 * @return bool
		 */
		private function is_db_update(): bool {
			$has_update = get_option( 'visitor_maps_geolitecity_has_update', false );

			if ( $has_update ) {
				return true;
			}

			$redux   = get_option( Visitor_Maps::OPT_NAME );
			$lic_key = $redux['maxmind_lic_key'] ?? '';

			$local_file_time  = filemtime( Visitor_Maps::$upload_dir . Visitor_Maps::DATABASE_NAME . Visitor_Maps::DATABASE_EXT );
			$remote_file_time = Visitor_Maps::$core->http_last_mod( Visitor_Maps::REMOTE_DATABASE . '?edition_id=' . Visitor_Maps::DATABASE_NAME . '&license_key=' . $lic_key . '&suffix=tar.gz', 1 );

			if ( '' !== $remote_file_time ) {
				$check_date = true;

				if ( $remote_file_time < ( time() - ( 7 * 24 * 60 * 60 ) ) ) {
					$check_date = false;
				}

				if ( $remote_file_time > $local_file_time || ! $check_date ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Set plugin options.
		 */
		private function set_options() {
			$opt_name = Visitor_Maps::OPT_NAME;

			$args = array(
				'opt_name'             => $opt_name,
				'display_name'         => 'Visitor Maps',
				'display_version'      => Visitor_Maps::VERSION,
				'menu_type'            => 'submenu',
				'allow_sub_menu'       => true,
				'menu_title'           => 'Settings',
				'page_title'           => 'Visitor Maps',
				'google_update_weekly' => false,
				'async_typography'     => false,
				'admin_bar'            => true,
				'admin_bar_icon'       => 'dashicons-portfolio',
				'admin_bar_priority'   => 50,
				'global_variable'      => '',
				'dev_mode'             => false,
				'update_notice'        => false,
				'customizer'           => false,
				'page_priority'        => 2,
				'page_parent'          => 'visitor-maps', // 'themes.php',
				'page_permissions'     => 'manage_options',
				'menu_icon'            => '',
				'last_tab'             => '',
				'page_icon'            => 'icon-themes',
				'page_slug'            => Visitor_Maps::PAGE_SLUG,
				'save_defaults'        => true,
				'default_show'         => false,
				'default_mark'         => '',
				'show_import_export'   => false,
				'hide_reset'           => false,
				'transient_time'       => 60 * MINUTE_IN_SECONDS,
				'output'               => true,
				'output_tag'           => true,
				'footer_credit'        => '',
				'database'             => '',
				'show_options_object'  => false,
				'hints'                => array(
					'icon'          => 'el el-question-sign',
					'icon_position' => 'right',
					'icon_color'    => 'lightgray',
					'icon_size'     => 'normal',
					'tip_style'     => array(
						'color'   => 'light',
						'shadow'  => true,
						'rounded' => false,
						'style'   => '',
					),
					'tip_position'  => array(
						'my' => 'top left',
						'at' => 'bottom right',
					),
					'tip_effect'    => array(
						'show' => array(
							'effect'   => 'slide',
							'duration' => '500',
							'event'    => 'mouseover',
						),
						'hide' => array(
							'effect'   => 'slide',
							'duration' => '500',
							'event'    => 'click mouseleave',
						),
					),
				),
			);

			$redux      = get_option( $opt_name );
			$enable_opt = $redux['enable_location_plugin'] ?? true;

			$usage  = '';
			$update = '';
			$warn   = '';

			$show_top = true;
			$hidden   = false;

			$usage .= esc_html__( 'Add the shortcode [visitor-maps] in a Page (not a Post). That page will then become your Visitor Maps page.', 'visitor-maps' ) . '&nbsp;&nbsp;<a href="' . Visitor_Maps::$url . 'img/help/screenshot-6.gif" target="_new">' . esc_html__( 'Help', 'visitor-maps' ) . '</a><br>';
			$usage .= esc_html__( "Add the Who's Online sidebar. Click on Appearance,", 'visitor-maps' ) . ' <a href=' . admin_url( 'widgets.php' ) . '>' . esc_html__( 'Widgets', 'visitor-maps' ) . '</a>, ' . esc_html__( 'then drag the Who\'s Online widget to the sidebar column on the right.', 'visitor-maps' ) . '&nbsp;&nbsp;<a href="' . Visitor_Maps::$url . 'img/help/screenshot-7.gif" target="_new">' . esc_html__( 'Help', 'visitor-maps' ) . '</a>';

			include_once Visitor_Maps::$dir . 'admin/class-visitor-maps-ajax.php';

			require_once ABSPATH . 'wp-includes/pluggable.php';
			$nonce = wp_create_nonce( 'visitor_maps_geolitecity' );

			if ( ! is_file( Visitor_Maps::$upload_dir . Visitor_Maps::DATABASE_NAME . Visitor_Maps::DATABASE_EXT ) ) {
				$hidden   = true;
				$show_top = true;

				$install = esc_html__( 'Install Now', 'visitor-maps' );

				$update .= '<div data-nonce="' . $nonce . '" class="visitor-maps-geolitecity update-message notice inline notice-warning notice-alt">';
				$update .= '<p>' . esc_html__( 'The Maxmind GeoLiteCity database is not yet installed.', 'visitor-maps' ) . '&nbsp;&nbsp;';
				$update .= '<a href="#" class="update-geolitecity">';
				$update .= $install;
				$update .= '</a>.';
			} elseif ( ! $enable_opt ) {
				$show_top = false;

				$update .= '<div data-nonce="' . $nonce . '" class="visitor-maps-geolitecity update-message notice inline notice-error notice-alt">';
				$update .= '<p>' . esc_html__( 'The Maxmind GeoLiteCity database is installed but not enabled (click the switch below).', 'visitor-maps' );
			} else {
				$show_top = false;

				if ( $this->is_db_update() ) {
					$link        = 'An update is available.&nbsp;&nbsp;<a href="#" class="update-geolitecity">' . esc_html__( 'Install Update', 'visitor-maps' ) . '</a>.';
					$data_update = '1';

					update_option( 'visitor_maps_geolitecity_has_update', true );
				} else {
					$link        = '<a href="#" class="geolitecity-lookup">' . esc_html__( 'Run lookup test?', 'visitor-maps' ) . '</a>';
					$data_update = '0';

					update_option( 'visitor_maps_geolitecity_has_update', false );
				}

				$update .= '<div data-update="' . $data_update . '" data-nonce="' . $nonce . '" class="visitor-maps-geolitecity updated-message notice inline notice-success notice-alt">';
				$update .= '<p>' . esc_html__( 'The Maxmind GeoLiteCity database is installed and enabled.', 'visitor-maps' ) . '&nbsp;&nbsp;';
				$update .= $link;
			}

			$update .= '<span class="lookup-data"></span>';
			$update .= '</p>';
			$update .= '</div>';

			$set_update = $update;
			if ( ! $show_top ) {
				$set_update = '';
			}

			if ( $hidden ) {
				$warn = '<br><strong class="geolocation-warn">' . esc_html__( 'GeoLocation options will not be available until the GeoLiteCity database is installed.', 'visitor-maps' ) . '</strong>';
			}

			$args['intro_text'] = '<p><strong>' . esc_html__( 'Visitor Map Usage', 'visitor-maps' ) . '</strong><p><br>' . $usage . $set_update;

			Redux::setArgs( $opt_name, $args );

			Redux::setSection(
				$opt_name,
				array(
					'title'  => esc_html__( 'GeoLocation', 'visitor-maps' ),
					'desc'   => $update . $warn,
					'icon'   => 'dashicons dashicons-admin-site',
					'fields' => array(
						array(
							'id'    => 'maxmind_lic_key',
							'type'  => 'text',
							'title' => esc_html__( 'MaxMind License Key', 'visitor-maps' ),
							'desc'  => 'The key that will be used when dealing with MaxMind GeoLocation services.  You can read how to generate one here.',
						),
						array(
							'id'      => 'enable_location_plugin',
							'type'    => 'switch',
							'title'   => esc_html__( 'Enable GeoLocation', 'visitor-maps' ),
							'default' => true,
							'class'   => 'location-enable',
							'hidden'  => $hidden,
						),
						array(
							'id'       => 'hide_text_on_worldmap',
							'type'     => 'switch',
							'title'    => esc_html__( 'GeoLocation Map Text', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Missing map background fix.', 'visitor-maps' ),
							'default'  => false,
							'hint'     => array(
								'content' => esc_html__( 'Some PHP servers do not have full support for printing text on the Visitor Map image. Only if the Visitor Map just displays pins and no image for the world or countries, select this setting. After selecting this setting, check your visitor maps page to see if the map is now working.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
						),
						array(
							'id'       => 'enable_state_display',
							'type'     => 'switch',
							'title'    => esc_html__( 'Display City & State', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Enable display of city, state next to country flag.', 'visitor-maps' ),
							'default'  => true,
							'hint'     => array(
								'content' => esc_html__( 'When enabled, the specific city and state information will appear next to the country flag.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
						),
						array(
							'id'       => 'enable_dash_map',
							'type'     => 'switch',
							'title'    => esc_html__( 'Visitor Map on Dashboard', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Enable visitor map on Who\'s Online dashboard.', 'visitor-maps' ),
							'default'  => true,
							'hint'     => array(
								'content' => esc_html__( 'Changes display options on Who\'s Online dashboard pages.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
						),
						array(
							'id'       => 'enable_page_map',
							'type'     => 'switch',
							'title'    => esc_html__( 'Visitor Map on Shortcode Page', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Enable visitor map on Visitor Map shortcode page.', 'visitor-maps' ),
							'default'  => true,
							'hint'     => array(
								'content' => esc_html__( 'Changes map display options on the page where the visitor map shortcode was inserted.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
						),
						array(
							'id'       => 'enable_widget_link',
							'type'     => 'switch',
							'title'    => esc_html__( 'Visitor Map link in Widget', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Enable visitor map link on Who\'s Online widget.', 'visitor-maps' ),
							'default'  => true,
							'hint'     => array(
								'content' => esc_html__( 'Changes display options on Who\'s Online widget.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
						),
						array(
							'id'       => 'enable_visitor_map_hover',
							'type'     => 'switch',
							'title'    => esc_html__( 'Hover Labels for Location Pins', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Enable hover labels for location pins on visitor map page.', 'visitor-maps' ),
							'default'  => false,
							'hint'     => array(
								'content' => esc_html__( 'Some themes interfere with the proper display of the location pins on the Visitor Maps page. After enabling this setting, check your visitor maps page to make sure the pins are placed correctly. If the pins are about 10 pixels too low on the map, undo this setting.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
						),
						array(
							'id'       => 'enable_users_map_hover',
							'type'     => 'switch',
							'title'    => esc_html__( 'User Names on Hover Labels for Location Pins', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Enable user names on hover labels for location pins on visitor map page.', 'visitor-maps' ),
							'default'  => false,
							'hint'     => array(
								'content' => esc_html__( 'When enabled, registered users will have green location pins on the Visitor Maps page. Also the hover tag will include the user name.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
						),
						array(
							'id'       => 'hide_console',
							'type'     => 'switch',
							'title'    => esc_html__( 'Map Viewing', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Hide map viewing by non administrators.', 'visitor-maps' ),
							'default'  => false,
							'hint'     => array(
								'content' => esc_html__( 'This setting restricts viewing the Visitor Map Viewer page to administrators only.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
						),
						array(
							'id'       => 'pins_limit',
							'type'     => 'text',
							'title'    => esc_html__( 'Location Pin Limit', 'visitor-maps' ),
							'default'  => '2000',
							'validate' => 'numeric',
							'hint'     => array(
								'content' => esc_html__( 'This limit protects server resources by limiting pins when displaying maps. Default is 2000. The human eye will not be able to see more than 2000 pins anyway.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
						),
						array(
							'id'       => 'default_map_time',
							'type'     => 'text',
							'title'    => esc_html__( 'Visitor Map Time', 'visitor-maps' ),
							'subtitle' => esc_html__( 'How long should users remain on the current visitor map.  Duration unit is set below in the next option.', 'visitor-maps' ),
							'default'  => '30',
							'validate' => 'numeric',
							'required' => array( 'enable_location_plugin', '=', true ),
						),
						array(
							'id'       => 'default_map_units',
							'type'     => 'select',
							'title'    => esc_html__( 'Visitor Map Time Duration', 'visitor-maps' ),
							'subtitle' => esc_html__( 'The duration unit of time users should remain on the visitor map.', 'visitor-maps' ),
							'options'  => array(
								'minutes' => esc_html__( 'Minutes', 'visitor-maps' ),
								'hours'   => esc_html__( 'Hours', 'visitor-maps' ),
								'days'    => esc_html__( 'Days', 'visitor-maps' ),
							),
							'default'  => 'days',
							'required' => array( 'enable_location_plugin', '=', true ),
							'select2'  => array( 'allowClear' => false ),
						),
						array(
							'id'       => 'default_map',
							'type'     => 'select',
							'title'    => esc_html__( 'Default Visitor Map', 'visitor-maps' ),
							'options'  => array(
								'1' => esc_html__( 'World (smallest)', 'visitor-maps' ),
								'2' => esc_html__( 'World (small)', 'visitor-maps' ),
								'3' => esc_html__( 'World (medium)', 'visitor-maps' ),
								'4' => esc_html__( 'World (large)', 'visitor-maps' ),
							),
							'default'  => '1',
							'hint'     => array(
								'content' => esc_html__( 'Default map to display on the Visitor Maps page. After setting this, check your visitor maps page to make sure it fits correctly. If the map is too wide, select the next smaller one.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
							'select2'  => array( 'allowClear' => false ),
						),
						array(
							'id'       => 'dashboard_permissions',
							'type'     => 'select',
							'title'    => esc_html__( 'Dashboard Permissions', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Users who can view the dashboard pages.', 'visitor-maps' ),
							'options'  => array(
								'manage_options'     => esc_html__( 'Administrators', 'visitor-maps' ),
								'moderate_comments'  => esc_html__( 'Editors', 'visitor-maps' ),
								'edit_publish_posts' => esc_html__( 'Authors', 'visitor-maps' ),
								'edit_posts'         => esc_html__( 'Contributors', 'visitor-maps' ),
							),
							'default'  => 'manage_options',
							'hint'     => array(
								'content' => esc_html__( 'By default, only Administrators can view the dashboard pages. Change this setting to also allow Editors, Authors, or Contributors to view the dashboard pages. When set to Authors, you are also allowing Administrator and Editors.', 'visitor-maps' ),
							),
							'required' => array( 'enable_location_plugin', '=', true ),
							'select2'  => array( 'allowClear' => false ),
						),
					),
				)
			);

			Redux::setSection(
				$opt_name,
				array(
					'title'  => esc_html__( 'Visitors', 'visitor-maps' ),
					'icon'   => 'dashicons dashicons-groups',
					'fields' => array(
						array(
							'id'       => 'active_time',
							'type'     => 'text',
							'title'    => esc_html__( 'Active Time', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Minutes that a visitor is considered "active".', 'visitor-maps' ),
							'default'  => '5',
							'validate' => 'numeric',
						),
						array(
							'id'       => 'track_time',
							'type'     => 'text',
							'title'    => esc_html__( 'Inactive Time', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Minutes until an inactive visitor is removed from display.', 'visitor-maps' ),
							'default'  => '15',
							'validate' => 'numeric',
						),
						array(
							'id'       => 'store_days',
							'type'     => 'text',
							'title'    => esc_html__( 'Data Store', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Days to store visitor data in database table. This data is used for the geolocation maps.', 'visitor-maps' ),
							'default'  => '30',
							'validate' => 'numeric',
						),
						array(
							'id'       => 'hide_administrators',
							'type'     => 'switch',
							'title'    => esc_html__( 'Administrators', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Do not include administrators count or location on the maps.', 'visitor-maps' ),
							'default'  => false,
						),
						array(
							'id'       => 'hide_bots',
							'type'     => 'switch',
							'title'    => esc_html__( 'Bots', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Do not include search bots in the visitors online count.', 'visitor-maps' ),
							'default'  => false,
						),
						array(
							'id'       => 'combine_members',
							'type'     => 'switch',
							'title'    => esc_html__( 'Combine Guests & Members', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Combine guests and members on widgets so they are only shown as visitors.  Use this setting when your site has registration turned off.', 'visitor-maps' ),
							'default'  => false,
						),
						array(
							'id'       => 'ips_to_ignore',
							'type'     => 'textarea',
							'title'    => esc_html__( 'IP Addresses to Ignore', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Start each entry on a new line.  Use * for wildcards.  Examples:', 'visitor-maps' ) . '<br><br>192.168.1.100<br>192.168.1.*<br>192.168.*.*',
							'default'  => '',
						),
						array(
							'id'       => 'urls_to_ignore',
							'type'     => 'textarea',
							'title'    => esc_html__( 'URLs to Ignore', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Optional list of URLs on your site you do not want in any Who\'s Online data.  This feature can be used to block any URLs such as /wp-admin/, or for compatibility with other plugins.  Use partial URL or full URL.  Start each entry on a new line.  Examples:', 'visitor-maps' ) . '<br><br>wp-slimstat-js.php<br>http://www.mysite.com/wp-content/plugins/wp-slimstat-js.php<br>/wp-admin/',
							'default'  => 'wp-slimstat-js.php',
						),
					),
				)
			);

			Redux::setSection(
				$opt_name,
				array(
					'title'  => esc_html__( 'Lookups', 'visitor-maps' ),
					'icon'   => 'dashicons dashicons-search',
					'fields' => array(
						array(
							'id'       => 'whois_url',
							'type'     => 'text',
							'title'    => esc_html__( 'WHOIS Lookup URL', 'visitor-maps' ),
							'subtitle' => esc_html__( 'URL to open when an IP address is clicked on.', 'visitor-maps' ),
							'default'  => 'http://www.ip-adress.com/ip_tracer/',
							'validate' => 'url',
						),
						array(
							'id'      => 'whois_url_popup',
							'type'    => 'switch',
							'title'   => esc_html__( 'Open WHOIS URL is a Popup Window', 'visitor-maps' ),
							'default' => true,
						),
						array(
							'id'      => 'enable_host_lookups',
							'type'    => 'switch',
							'title'   => esc_html__( 'Host Lookup for IP Addresses', 'visitor-maps' ),
							'default' => true,
						),
					),
				)
			);

			Redux::setSection(
				$opt_name,
				array(
					'title'  => esc_html__( 'Stats', 'visitor-maps' ),
					'icon'   => 'dashicons dashicons-chart-line',
					'fields' => array(
						array(
							'id'       => 'enable_blog_footer',
							'type'     => 'switch',
							'title'    => esc_html__( 'Display in Blog Footer', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Enable stats display in blog footer.', 'visitor-maps' ),
							'default'  => false,
						),
						array(
							'id'       => 'enable_admin_footer',
							'type'     => 'switch',
							'title'    => esc_html__( 'Display in Admin Footer', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Enable stats display in admin footer.', 'visitor-maps' ),
							'default'  => true,
						),
						array(
							'id'       => 'enable_records_page',
							'type'     => 'switch',
							'title'    => esc_html__( 'Display on Map Page', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Enable stats display on map page.', 'visitor-maps' ),
							'default'  => true,
						),
						array(
							'id'       => 'enable_credit_link',
							'type'     => 'switch',
							'title'    => esc_html__( 'Credit Link', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Enable plugin credit link:', 'visitor-maps' ) . '<small>Powered by <a href="http://requitedesigns.com/visitor-maps/" target="_new">Visitor Maps</a></small>',
							'default'  => true,
						),
					),
				)
			);

			Redux::setSection(
				$opt_name,
				array(
					'title'  => esc_html__( 'Time Format', 'visitor-maps' ),
					'desc'   => esc_html__( 'Available formatting:', 'visitor-maps' ) . '&nbsp;&nbsp;<a href="http://php.net/date" target="_blank">' . esc_html__( 'Table of date format characters', 'visitor-maps' ) . '</a>',
					'icon'   => 'dashicons dashicons-clock',
					'fields' => array(
						array(
							'id'       => 'time_format',
							'type'     => 'text',
							'title'    => esc_html__( 'Time Format (Max Users)', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Time format for "Max users today" and "Last refresh time" display. Default: h:i a T (02:25 pm PST)', 'visitor-maps' ),
							'default'  => 'h:i a T',
						),
						array(
							'id'       => 'time_format_hms',
							'type'     => 'text',
							'title'    => esc_html__( 'Time Format (Last Click)', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Time format for "Entry" and "Last Click" display. Default" h:i:sa (02:25:25pm)', 'visitor-maps' ),
							'default'  => 'h:i:sa',
						),
						array(
							'id'       => 'date_time_format',
							'type'     => 'text',
							'title'    => esc_html__( 'Date/Time format (All Time Records)', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Date/Time format for month, year, an all time records. Default: m-d-Y h:i a T (12-14-2008 02:25 pm PST)', 'visitor-maps' ),
							'default'  => 'm-d-Y h:i a T',
						),
						array(
							'id'       => 'geoip_date_format',
							'type'     => 'text',
							'title'    => esc_html__( 'Date/Time format (GeoLite data)', 'visitor-maps' ),
							'subtitle' => esc_html__( 'Date/Time format for "The GeoLite data was last updated on...". Default: m-d-Y h:i a T (12-14-2008 02:25 pm PST)', 'visitor-maps' ),
							'default'  => 'm-d-Y h:i a T',
						),
					),
				)
			);

			// Redux::init( Visitor_Maps::OPT_NAME );
		}
	}

	new Visitor_Maps_Options();
}
