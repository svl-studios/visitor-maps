<?php
/**
 * Visitor Maps Class
 *
 * @class Visitor_Maps
 * @version 2.0.0
 * @package Visitor Maps
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Visitor_Maps' ) ) {

	/**
	 * Class Visitor_Maps
	 */
	class Visitor_Maps {
		/**
		 * Plugin version.
		 */
		const VERSION = '2.0.0';

		/**
		 * Plugin slug.
		 */
		const PLUGIN_SLUG = 'visitor-maps';

		/**
		 * Plugin opt name.
		 */
		const OPT_NAME = 'visitor_maps_opt';

		/**
		 * Option page slug.
		 */
		const PAGE_SLUG = 'visitor_maps_opt';

		/**
		 * Remote database URL
		 */
		const REMOTE_DATABASE = 'https://download.maxmind.com/app/geoip_download';

		/**
		 * GeoLocation database name.
		 */
		const DATABASE_NAME = 'GeoLite2-City';

		/**
		 * GeoLocation database extension.
		 */
		const DATABASE_EXT = '.mmdb';

		/**
		 * Plugin directory.
		 *
		 * @var string
		 */
		public static string $dir = '';

		/**
		 * Plugin URL.
		 *
		 * @var string
		 */
		public static string $url = '';

		/**
		 * WP Upload Directory.
		 *
		 * @var string
		 */
		public static string $upload_dir = '';

		/**
		 * WP Upload URL.
		 *
		 * @var string
		 */
		public static string $upload_url = '';

		/**
		 * Core object pointer.
		 *
		 * @var Visitor_Maps_Core|null
		 */
		public static ?Visitor_Maps_Core $core = null;

		/**
		 * Geolocation object pointer.
		 *
		 * @var Visitor_Maps_Geolocation|null
		 */
		public static ?Visitor_Maps_Geolocation $geolocation = null;

		/**
		 * Instance pointer.
		 *
		 * @var Visitor_Maps|null
		 */
		public static ?Visitor_Maps $instance = null;

		/**
		 * Initiate instance.
		 *
		 * @return Visitor_Maps
		 */
		public static function instance(): ?Visitor_Maps {
			if ( ! self::$instance ) {
				self::$instance = new self();

				self::$instance->init();
				self::$instance->includes();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Core init.
		 */
		public static function init(): void {
			self::$dir = trailingslashit( wp_normalize_path( dirname( realpath( __FILE__ ) ) ) );
			self::$url = trailingslashit( plugin_dir_url( __FILE__ ) );

			self::$url = apply_filters( 'visitor_maps/url', self::$url ); // phpcs:ignore WordPress.NamingConventions.ValidHookName
			self::$dir = apply_filters( 'visitor_maps/dir', self::$dir ); // phpcs:ignore WordPress.NamingConventions.ValidHookName

			$upload_dir = wp_upload_dir();

			$protocols = array(
				'https://',
				'http://',
			);

			self::$upload_dir = $upload_dir['basedir'] . '/visitor-maps/';
			self::$upload_url = str_replace( $protocols, '//', $upload_dir['baseurl'] . '/visitor-maps/' );

			self::$upload_dir = apply_filters( 'visitor_maps/upload_dir', self::$upload_dir ); // phpcs:ignore WordPress.NamingConventions.ValidHookName
			self::$upload_url = apply_filters( 'visitor_maps/upload_url', self::$upload_url ); // phpcs:ignore WordPress.NamingConventions.ValidHookName

			if ( ! is_dir( self::$upload_dir ) ) {
				wp_mkdir_p( self::$upload_dir );
			}

			add_action( 'init', array( get_called_class(), 'load_panel' ) );
		}

		/**
		 * Load option panel.
		 */
		public static function load_panel(): void {
			require_once self::$dir . 'inc/class-visitor-maps-core.php';
			self::$core = new Visitor_Maps_Core();

			require_once self::$dir . 'admin/class-visitor-maps-options.php';
		}

		/**
		 * Core includes.
		 */
		public static function includes(): void {
			require_once self::$dir . 'inc/class-visitor-maps-enqueue.php';
			require_once self::$dir . 'inc/class-visitor-maps-extended.php';

			require_once self::$dir . 'geo-location/class-visitor-maps-geolocation.php';
			self::$geolocation = new Visitor_Maps_Geolocation();
		}

		/**
		 * Core hooks.
		 */
		private static function hooks() {}

		/**
		 * Plugin activate.
		 */
		public static function activate(): void {
			self::install_visitor_maps();
		}

		/**
		 * Plugin deactivate.
		 */
		public static function deactivate(): void {
			self::uninstall_visitor_maps();
		}

		/**
		 * Install Visitor Maps database table.
		 */
		private static function install_visitor_maps(): void {
			global $wpdb, $wp_version;

			$wo_table_wo = $wpdb->prefix . 'visitor_maps_wo';
			$wo_table_st = $wpdb->prefix . 'visitor_maps_st';
			$wo_table_ge = $wpdb->prefix . 'visitor_maps_ge';

			// phpcs:disable
			if ( $wpdb->get_var ( "show tables like '" . $wo_table_wo . "'" ) !== $wo_table_wo ) {
				$wpdb->query ( "CREATE TABLE IF NOT EXISTS `" . $wo_table_wo . "` (
                    `session_id`      varchar(128) NOT NULL default '',
                    `ip_address`      varchar(20) NOT NULL default '',
                    `user_id`         bigint(20) unsigned NOT NULL default '0',
                    `name`            varchar(64) NOT NULL default '',
                    `nickname`        varchar(20) default NULL,
                    `country_name`    varchar(50) default NULL,
                    `country_code`    char(2) default NULL,
                    `city_name`       varchar(50) default NULL,
                    `state_name`      varchar(50) default NULL,
                    `state_code`      char(2) default NULL,
                    `latitude`        decimal(10,4) default '0.0000',
                    `longitude`       decimal(10,4) default '0.0000',
                    `last_page_url`   text NOT NULL,
                    `http_referer`    varchar(255) default NULL,
                    `user_agent`      varchar(255) NOT NULL default '',
                    `hostname`        varchar(255) default NULL,
                    `provider`        varchar(255) default NULL,
                    `time_entry`      int(10) unsigned NOT NULL default '0',
                    `time_last_click` int(10) unsigned NOT NULL default '0',
                    `num_visits`      int(10) unsigned NOT NULL default '0',
                     PRIMARY KEY  (`session_id`),
                     KEY `nickname_time_last_click` (`nickname`,`time_last_click`))"
				);
			}

			$now = current_time( 'mysql' );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( $wpdb->get_var ( "show tables like '" . $wo_table_st . "'" ) !== $wo_table_st ) {
				$wpdb->query ( "CREATE TABLE IF NOT EXISTS `" . $wo_table_st . "` (
                    `type`  varchar(14) NOT NULL default '',
                    `count` mediumint(8) NOT NULL default '0',
                    `time`  datetime NOT NULL default '0000-00-00 00:00:00',
                     PRIMARY KEY  (`type`))"
				);

				$wpdb->query ( "INSERT INTO `" . $wo_table_st . "` (`type` ,`count` ,`time`) VALUES ('day', '1', '" . $now . "')" );
				$wpdb->query ( "INSERT INTO `" . $wo_table_st . "` (`type` ,`count` ,`time`) VALUES ('month', '1', '" . $now . "')" );
				$wpdb->query ( "INSERT INTO `" . $wo_table_st . "` (`type` ,`count` ,`time`) VALUES ('year', '1', '" . $now . "')" );
				$wpdb->query ( "INSERT INTO `" . $wo_table_st . "` (`type` ,`count` ,`time`) VALUES ('all', '1', '" . $now . "')" );
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ( $wpdb->get_var ( "show tables like '" . $wo_table_ge . "'" ) !== $wo_table_ge ) {
				$wpdb->query ( "CREATE TABLE IF NOT EXISTS `" . $wo_table_ge . "` (
                    `time_last_check` int(10) unsigned NOT NULL default '0',
                    `needs_update` tinyint(1) unsigned NOT NULL default '0')"
				);
			}
			// phpcs:enable

			// Extended fields.
			self::vm_install();
		}

		/**
		 * Backup .htaccess.
		 *
		 * @param string $htbackup Backup file name.
		 *
		 * @return bool
		 */
		private function vm_backup_htaccess( string $htbackup ): bool {
			if ( ! copy( ABSPATH . '.htaccess', $htbackup ) ) {
				return false;
			} else {
				return true;
			}
		}

		/**
		 * Install extended options.
		 */
		public static function vm_install(): void {
			global $wp_version;

			$vm_htbackup         = get_option( 'vm_htbackup', false );
			$vm_banned_ips       = get_option( 'vm_banned_ips', array() );
			$vm_banned_referers  = get_option( 'vm_banned_referers', array() );
			$vm_auto_update      = get_option( 'vm_auto_update', false );
			$vm_auto_update_time = get_option( 'vm_auto_update_time', 5 );

			if ( ! $vm_htbackup ) {
				$htbackup = ABSPATH . '.htaccess.backup.' . wp_generate_password( 6, false );

				update_option( 'vm_htbackup', $htbackup );
				if ( ! self::vm_backup_htaccess( $htbackup ) ) {
					update_option( 'htaccess_warning', true );
					update_option( 'vm_htaccess', false );
				} else {
					update_option( 'htaccess_warning', false );
					update_option( 'vm_htaccess', true );
				}
			} else {
				update_option( 'htaccess_warning', false );
				update_option( 'vm_htaccess', true );
			}

			/* Backwards Compatibility */
			if ( ! is_array( $vm_banned_ips ) && strlen( $vm_banned_ips ) > 0 ) {
				$ips = explode( ', ', $vm_banned_ips );
				update_option( 'vm_banned_ips', $ips );
			} else {
				update_option( 'vm_banned_ips', $vm_banned_ips );
			}

			if ( ! is_array( $vm_banned_referers ) && strlen( $vm_banned_referers ) > 0 ) {
				$referers = explode( ', ', $vm_banned_referers );
				update_option( 'vm_banned_referers', $referers );
			} else {
				update_option( 'vm_banned_referers', $vm_banned_referers );
			}

			update_option( 'vm_wp_version', $wp_version );
			update_option( 'vm_version', self::VERSION );
			update_option( 'vm_auto_update', $vm_auto_update );
			update_option( 'vm_auto_update_time', $vm_auto_update_time );
		}

		/**
		 * Uninstall Visitor Maps database table.
		 */
		private static function uninstall_visitor_maps(): void {
			global $wpdb;

			$wo_table_wo = $wpdb->prefix . 'visitor_maps_wo';
			$wo_table_st = $wpdb->prefix . 'visitor_maps_st';
			$wo_table_ge = $wpdb->prefix . 'visitor_maps_ge';

			// phpcs:disable
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS `%1$s`', $wo_table_wo ) );
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS `%1$s`', $wo_table_st ) );
			$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS `%1$s`', $wo_table_ge ) );
			// phpcs:enable

			delete_option( 'visitor_maps' );
			delete_option( 'visitor_maps_upgrade_1' );
			delete_option( 'visitor_maps_upgrade_2' );
			delete_option( 'visitor_maps_dismiss' );

			// Extended fields.
			$vm_settings = get_option( 'vm_settings' );

			if ( ! is_array( $vm_settings ) ) {
				$vm_settings = array( 'preserve_data' => true );
			}

			$preserve_data = intval( $vm_settings['preserve_data'] );

			if ( ! $preserve_data ) {
				$vm_htbackup = get_option( 'vm_htbackup' );
				copy( ABSPATH . '.htaccess', $vm_htbackup );
				update_option( 'vm_banned_ips', array() );
				update_option( 'vm_banned_referers', array() );
				vm_rebuild_htaccess();
				delete_option( 'vm_wp_version' );
				delete_option( 'vm_banned_ips' );
				delete_option( 'vm_banned_referers' );
				delete_option( 'vm_htbackup' );
				delete_option( 'vm_auto_update' );
				delete_option( 'vm_auto_update_time' );
				delete_option( 'vm_settings' );
			}
		}
	}
}
