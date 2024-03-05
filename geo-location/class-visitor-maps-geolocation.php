<?php
/**
 * Geo Location Class
 *
 * @class Visitor_Maps_Geolocation
 * @version 2.0.0
 * @package VisitorMaps
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Visitor_Maps_Geolocation' ) ) {

	/**
	 * Class Visitor_Maps_Geolocation
	 */
	class Visitor_Maps_Geolocation {

		/**
		 * Visitor_Maps_Geolocation constructor.
		 */
		public function __construct() {
			add_action( 'visitor_maps_geoip_view', array( $this, 'view_display_form' ), 10, 2 );
		}

		/**
		 * Display update message.
		 *
		 * @param string $geoip_old      Age.
		 * @param string $geoip_days_ago Age in days.
		 */
		public function view_display_form( string $geoip_old, string $geoip_days_ago ): void {
			//$geoip_old = true;
			if ( $geoip_old ) {
				/* translators: Number of days past */
				echo '<span style="color:red" xmlns="http://www.w3.org/1999/html">' . sprintf( esc_html__( 'The GeoLiteCity data was last updated %s days ago.', 'visitor-maps' ), '<strong>' . intval( $geoip_days_ago ) ) . '</strong> ' . esc_html__( 'an update is available', 'visitor-maps' ) . ',
                      <a href="' . esc_url( wp_nonce_url( admin_url( 'plugins.php?page=visitor-maps' ), 'visitor-maps-geo_update' ) ) . '&do_geo=1">' . esc_html__( 'click here to update', 'visitor-maps' ) . '</a></span>';
			} else {
				/* translators: Number of days past */
				printf( esc_html__( 'The GeoLiteCity data was last updated %s days ago', 'visitor-maps' ), '<strong>' . intval( $geoip_days_ago ) . '</strong>' );
			}

			/* translators: maxmind URL */
			printf( '<br /><p>' . esc_html__( 'Uses GeoLiteCity data created by MaxMind, available from %s', 'visitor-maps' ) . '</p>', '<a target="_blank" href="http://www.maxmind.com">http://www.maxmind.com</a>' );
		}
	}
}
