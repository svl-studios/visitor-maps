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
		public static $dir = '';

		/**
		 * Plugin URL.
		 *
		 * @var string
		 */
		public static $url = '';

		/**
		 * WP Upload Directory.
		 *
		 * @var string
		 */
		public static $upload_dir = '';

		/**
		 * WP Upload URL.
		 *
		 * @var string
		 */
		public static $upload_url = '';

		/**
		 * Core object pointer.
		 *
		 * @var null
		 */
		public static $core = null;

		/**
		 * Geolocation object pointer.
		 *
		 * @var null
		 */
		public static $geolocation = null;

		/**
		 * Instance pointer.
		 *
		 * @var null
		 */
		private static $instance = null;

		/**
		 * Initiate instance.
		 *
		 * @return Visitor_Maps
		 */
		public static function instance() {
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
		public static function init() {
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
		}

		/**
		 * Core includes.
		 */
		public static function includes() {
			require_once self::$dir . 'inc/class-visitor-maps-core.php';
			self::$core = new Visitor_Maps_Core();

			require_once self::$dir . 'admin/class-visitor-maps-options.php';
			require_once self::$dir . 'inc/enqueue.php';

			require_once self::$dir . 'geo-location/class-visitor-maps-geolocation.php';
			self::$geolocation = new Visitor_Maps_Geolocation();
		}

		/**
		 * Core hooks.
		 */
		private static function hooks() {

		}

		/**
		 * Plugin activate.
		 *
		 * @param bool $network_wide Is Network wide.
		 */
		public static function activate( $network_wide ) {
			self::install_visitor_maps();
		}

		/**
		 * Plugin deactivate.
		 *
		 * @param bool $network_wide Is Network wide.
		 */
		public static function deactivate( $network_wide ) {
			self::uninstall_visitor_maps();
		}

		/**
		 * Install Visitor Maps database table.
		 */
		private static function install_visitor_maps() {
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
		}

		/**
		 * Uninstall Visitor Maps database table.
		 */
		private static function uninstall_visitor_maps() {
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
		}
	}
}