<?php
/**
 * Visitor Map AJAX Class
 *
 * @class Visitor_Maps_AJAX
 * @version 2.0.0
 * @package VisitorMaps
 */

defined( 'ABSPATH' ) || exit;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use JetBrains\PhpStorm\NoReturn;
use MaxMind\Db\Reader\InvalidDatabaseException;

if ( ! class_exists( 'Visitor_Maps_AJAX' ) ) {

	/**
	 * Class Visitor_Maps_AJAX
	 */
	class Visitor_Maps_AJAX {

		/**
		 * Visitor_Map_AJAX constructor.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
			add_action( 'wp_ajax_visitor_maps_geolitecity', array( $this, 'ajax' ) );
			add_action( 'wp_ajax_visitor_maps_lookup', array( $this, 'lookup' ) );
		}

		/**
		 * Test GeoLocation lookup.
		 */
		#[NoReturn] public function lookup(): void {
			if ( ! isset( $_POST['nonce'] ) || ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'visitor_maps_geolitecity' ) ) ) {
				echo 'Security check failed.';

				die;
			}

			$ip_address = Visitor_Maps::$core->get_ip_address();

			$array = $this->get_location_data( "$ip_address" );

			echo '</br></br>';
			echo '<strong>' . esc_html__( 'IP:', 'visitor-maps' ) . '</strong>&nbsp;&nbsp;' . esc_html( $ip_address ) . '<br />';
			echo '<strong>' . esc_html__( 'City:', 'visitor-maps' ) . '</strong>&nbsp;&nbsp;' . esc_html( $array['city_name'] ) . '<br />';
			echo '<strong>' . esc_html__( 'State Name:', 'visitor-maps' ) . '</strong>&nbsp;&nbsp;' . esc_html( $array['state_name'] ) . '<br />';
			echo '<strong>' . esc_html__( 'State Code:', 'visitor-maps' ) . '</strong>&nbsp;&nbsp;' . esc_html( $array['state_code'] ) . '<br />';
			echo '<strong>' . esc_html__( 'Country Name:', 'visitor-maps' ) . '</strong>&nbsp;&nbsp;' . esc_html( $array['country_name'] ) . '<br />';
			echo '<strong>' . esc_html__( 'Country Code:', 'visitor-maps' ) . '</strong>&nbsp;&nbsp;' . esc_html( $array['country_code'] ) . '<br />';
			echo '<strong>' . esc_html__( 'Lat:', 'visitor-maps' ) . '</strong>&nbsp;&nbsp;' . esc_html( $array['latitude'] ) . '<br />';
			echo '<strong>' . esc_html__( 'Lon: ', 'visitor-maps' ) . '</strong>&nbsp;&nbsp;' . esc_html( $array['longitude'] ) . '<br />';

			if ( 0 === $array['longitude'] && 0 === $array['latitude'] ) {
				echo '</br>' . esc_html__( 'Note: Location information was not available. This is normal on a local or private network.', 'visitor-maps' );
			}

			die;
		}

		/**
		 * Download database.
		 *
		 * @return WP_Error
		 */
		public function ajax(): WP_Error {
			if ( ! isset( $_POST['nonce'] ) || ( ! wp_verify_nonce( sanitize_key( $_POST['nonce'] ), 'visitor_maps_geolitecity' ) ) ) {
				$error = 'Security check failed.';

				echo wp_json_encode(
					array(
						'status' => 'error',
						'error'  => $error,
					)
				);

				die;
			}

			$error = '';

			$is_update = isset( $_POST['update'] ) && sanitize_text_field( wp_unslash( $_POST['update'] ) );

			$database_path = Visitor_Maps::$upload_dir . Visitor_Maps::DATABASE_NAME . Visitor_Maps::DATABASE_EXT;

			$download_uri = add_query_arg(
				array(
					'edition_id'  => Visitor_Maps::DATABASE_NAME,
					'license_key' => rawurlencode( sanitize_text_field( Visitor_Maps::$core->get_option( 'maxmind_lic_key' ) ) ),
					'suffix'      => 'tar.gz',
				),
				'https://download.maxmind.com/app/geoip_download'
			);

			require_once ABSPATH . 'wp-admin/includes/file.php';

			$tmp_archive_path = download_url( esc_url_raw( $download_uri ) );

			if ( is_wp_error( $tmp_archive_path ) ) {

				// Transform the error into something more informative.
				$error_data = $tmp_archive_path->get_error_data();

				if ( isset( $error_data['code'] ) ) {
					if ( 401 === $error_data['code'] ) {
						echo wp_json_encode(
							array(
								'status' => 'error',
								'error'  => esc_html__( 'The MaxMind license key is invalid. If you have recently created this key, you may need to wait for it to become active.', 'visitor-maps' ),
							)
						);

						die;
					}
				}
				echo wp_json_encode(
					array(
						'status' => 'error',
						'error'  => esc_html__( 'Failed to download the MaxMind database.', 'visitor-maps' ),
					)
				);

				die;
			}

			// Extract the database from the archive.
			try {
				$file = new PharData( $tmp_archive_path );

				$tmp_database_path = trailingslashit( dirname( $tmp_archive_path ) ) . trailingslashit( $file->current()->getFilename() ) . Visitor_Maps::DATABASE_NAME . Visitor_Maps::DATABASE_EXT;

				$file->extractTo( dirname( $tmp_archive_path ), trailingslashit( $file->current()->getFilename() ) . Visitor_Maps::DATABASE_NAME . Visitor_Maps::DATABASE_EXT, true );
			} catch ( Exception $exception ) {
				return new WP_Error( 'visitormaps_maxmind_geolocation_database_archive', $exception->getMessage() );
			} finally {
				// Remove the archive since we only care about a single file in it.
				wp_delete_file( $tmp_archive_path );
			}

			copy( $tmp_database_path, $database_path );

			if ( file_exists( $tmp_database_path ) & '' === $error ) {
				if ( $is_update ) {
					update_option( 'visitor_maps_geolitecity_has_update', false );
				}

				echo wp_json_encode( array( 'status' => 'success' ) );
			} else {
				echo wp_json_encode(
					array(
						'status' => 'error',
						'error'  => $error,
					)
				);
			}

			die();
		}

		/**
		 * Get Geolocation data.
		 *
		 * @param string $user_ip IP address to lookup.
		 *
		 * @return array
		 * @throws AddressNotFoundException Address not found.
		 * @throws InvalidDatabaseException Invalid database.
		 */
		private function get_location_data( string $user_ip ): array {
			require_once Visitor_Maps::$dir . 'vendor/autoload.php';

			$reader = new Reader( Visitor_Maps::$upload_dir . Visitor_Maps::DATABASE_NAME . Visitor_Maps::DATABASE_EXT );

			if ( '127.0.0.1' !== $user_ip ) {
				$record = $reader->city( $user_ip ); // '98.25.64.174'
			}

			$location_info = array();

			$location_info['provider']     = '';
			$location_info['city_name']    = ( isset( $record->city->name ) ) ? $record->city->name : '-';
			$location_info['state_name']   = ( isset( $record->mostSpecificSubdivision->name ) ) ? $record->mostSpecificSubdivision->name : '-'; // phpcs:disable WordPress.NamingConventions
			$location_info['state_code']   = ( isset( $record->mostSpecificSubdivision->isoCode ) ) ? strtoupper( $record->mostSpecificSubdivision->isoCode ) : '-';
			$location_info['country_name'] = ( isset( $record->country->name ) ) ? $record->country->name : '-';
			$location_info['country_code'] = ( isset( $record->country->isoCode ) ) ? strtoupper( $record->country->isoCode ) : '-';
			$location_info['latitude']     = ( isset( $record->location->latitude ) ) ? $record->location->latitude : '-';
			$location_info['longitude']    = ( isset( $record->location->longitude ) ) ? $record->location->longitude : '-';

			return $location_info;
		}

		/**
		 * Enqueue AJAX JavaScript.
		 */
		public function enqueue(): void {
			$min = Redux_Functions::isMin();

			wp_enqueue_script(
				'visitor-maps-ajax',
				Visitor_Maps::$url . 'js/ajax' . $min . '.js',
				array(
					'jquery',
					'json2',
				),
				Visitor_Maps::VERSION,
				true
			);

			$retry      = esc_html__( 'Retry', 'visitor-maps' );
			$run_lookup = esc_html__( 'Run lookup test?', 'visitor-maps' );

			wp_localize_script(
				'visitor-maps-ajax',
				'visitorMapsAjax',
				array(
					'ajaxurl'            => esc_url( admin_url( 'admin-ajax.php' ) ),
					'update_geolitecity' => array(
						'updating'    => esc_html__( 'Downloading GeoLiteCity database...', 'visitor-maps' ),
						'error'       => esc_html__( 'Update Failed.', 'visitor-maps' ) . '&nbsp;&nbsp;<a href="#" class="update-geolitecity">' . $retry . '</a>',
						'success'     => esc_html__( 'GeoLiteCity database successfully installed!', 'visitor-maps' ) . '&nbsp;&nbsp;<a href="#" class="geolitecity-lookup">' . $run_lookup . '</a>',
						'lookup_fail' => esc_html__( 'There was an error in the GeoLiteCity database lookup.', 'visitor-maps' ),
					),
				)
			);
		}
	}

	new Visitor_Maps_AJAX();
}
