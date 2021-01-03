<?php

use GeoIp2\Database\Reader;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WoProGeoLocUpdater' ) ) {
	class WoProGeoLocUpdater {

		var $setting;

		function get_wo_update_settings() {
			error_reporting( E_ALL ^ E_NOTICE );
			ini_set( 'display_errors', 1 );

			//ini_set('log_errors', 1);
			//ini_set('error_log', dirname(__FILE__) . '/error_log.txt');

			// begin settings --------------------------------------------------------------
			// maxmind url to download from
			// note: if you hit this url too often(refresh, refresh), maxmind will ban you for a short time!
			$this->setting['url'] = 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz';

			// optional setting to set a directory where your GeoLiteCity.dat file is
			// for some users who want the GeoLiteCity.dat file in a different folder
			// use server path, NOT URL!!
			$this->setting['geolite_path'] = Visitor_Maps::$upload_dir;
			if ( ! is_dir( $this->setting['geolite_path'] ) ) {
				mkdir( $this->setting['geolite_path'] );
			}


			$this->setting['geolite_path_upload'] = Visitor_Maps::$upload_dir;
			if ( ! is_dir( $this->setting['geolite_path_upload'] ) ) {
				mkdir( $this->setting['geolite_path_upload'] );
			}

			// do not download if the Maxmind GeoLiteCity database is already up to date
			// can be disabled if you want to force the download to test the script.
			// note: if you repeatedly test the script over and over again, maxmind may ban your IP for a time.
			$this->setting['check_dates'] = 1; // 0 or 1, 1 is recommended

			// perform a test lookup after update
			// (requires this script be installed in the same directory as your geoip* files).
			$this->setting['test_lookup'] = 1; // 0 or 1, 1 is recommended

			// Set permissions of the downladed file 0644 (www readable)
			// disable if you get a chmod error.
			$this->setting['chmod'] = 1; // 0 or 1, 1 is recommended

			// wo-update.php can use non-buffered output (recommend)
			// (prints status messages to screen while database update is happening)
			// can be disabled if your PHP does not support it, but you will have a white screen
			// while the data is loading.
			$this->setting['non_buffer'] = 1; // 0 or 1, 1 is recommended

			// use curl to check the remote file last modified time.
			$this->setting['use_curl'] = 1; // 0 or 1, 1 is recommended if your server has CURL

			// date displayed for last maxmind update
			// 'm-d-Y' would be 12-14-2008.
			$this->setting['date_format'] = 'm-d-Y'; // see http://us3.php.net/date.

			$this->setting['user_agent'] = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.1.3) Gecko/20090824 Firefox/3.5.3 (.NET CLR 3.5.30729)';
		}

		function update_now() {
			$this->get_wo_update_settings();

			$this->setting['safe_mode'] = ( (boolean) @ini_get( 'safe_mode' ) === false ) ? 0 : 1;
			if ( $this->setting['safe_mode'] ) {
				$this->setting['safe_mode'] = 1;
				$this->setting['chmod']     = 0;
			}

			if ( $this->setting['non_buffer'] && ob_get_level() == 0 ) {
				ob_start();
			}
			?>
			<h2><?php _e( 'Visitor Maps', 'visitor-maps' ) ?></h2>

			<?php
			$updating = 0;
			clearstatcache();

			if ( ! is_file( $this->setting['geolite_path'] . '/GeoLiteCity.dat' ) ) {
				echo '<b>' . __( 'Preparing a first time install of the Maxmind GeoLiteCity database', 'visitor-maps' ) . '</b><br />';

				if ( $this->setting['non_buffer'] ) {
					$this->non_buffer();
				}
			} else {
				echo '<b>' . __( 'Checking for updates for the Maxmind GeoLiteCity database', 'visitor-maps' ) . '</b><br />';

				$updating = 1;

				if ( $this->setting['non_buffer'] ) {
					$this->non_buffer();
				}
			}

			$vm_mem_limit    = 'unknown';
			$vm_mem_increase = '64M';
			$vm_mem_limit    = @ini_get( 'memory_limit' );

			if ( $vm_mem_limit == 'unknown' ) {
				echo __( 'PHP Memory Limit is unknown, increase is not available.', 'visitor-maps' ) . '<br />';
			} elseif ( function_exists( 'memory_get_usage' ) && ( (int) @ini_get( 'memory_limit' ) < abs( intval( $vm_mem_increase ) ) ) ) {
				echo sprintf( __( 'PHP Memory Limit is %1$s, temporarily increasing to %2$s', 'visitor-maps' ), $vm_mem_limit, $vm_mem_increase );

				@ini_set( 'memory_limit', $vm_mem_increase );
				$vm_mem_limit = @ini_get( 'memory_limit' );
				echo ' ';

				if ( $vm_mem_limit == $vm_mem_increase ) {
					echo __( 'completed.', 'visitor-maps' ) . '<br />';
				} else {
					echo __( 'failed.', 'visitor-maps' ) . '<br />';
				}

				if ( $this->setting['non_buffer'] ) {
					$this->non_buffer();
				}
			} else {
				echo sprintf( __( 'PHP Memory Limit is %s, increasing is not needed.', 'visitor-maps' ), $vm_mem_limit ) . '<br />';
			}

			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer();
			}

			echo __( 'Connecting to this URL:', 'visitor-maps' ) . ' ' . $this->setting['url'] . '<br />';
			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer();
			}

			if ( $this->setting['safe_mode'] ) {
				echo '<br /><span style="color:red;">' . __( 'Warning: Your web host has PHP safe_mode turned on.', 'visitor-maps' );
				echo '</span> ';
				echo __( 'PHP safe_mode can cause problems with this updater such as file permission errors.', 'visitor-maps' ) . "<br />\n";
				echo __( 'PHP safe_mode is better turned off, relying on this feature might work, but is highly discouraged. Contact your web host for support.', 'visitor-maps' ) . "<br />\n";
			}

			$allow_url_fopen = ( (boolean) @ini_get( 'allow_url_fopen' ) === false ) ? 0 : 1;

			if ( ! $allow_url_fopen ) {
				echo '<br /><span style="color:red;">' . __( 'Warning: Your web host has the PHP setting allow_url_fopen turned off.', 'visitor-maps' ) . " \n";
				echo '</span> ';
				echo __( 'PHP allow_url_fopen setting is required for this automatic update feature. Contact your web host for support.', 'visitor-maps' ) . " \n";
				echo __( 'The geolite database is really just a file. As a workaround, you can manually download the GeoLiteCity.dat.gz file from this URL: http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz', 'visitor-maps' ) . "<br />\n";
				echo __( 'Unzip the file and FTP upload GeoLiteCity.dat to the /wp-content/visitor-maps-geoip/ folder. When the file is not installed, the blog still works, but the location information for a user is skipped. ', 'visitor-maps' ) . "<br />\n";
			}

			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer();
			}

			if ( $updating ) {
				$local_file_time   = filemtime( $this->setting['geolite_path'] . '/GeoLiteCity.dat' );
				$local_gzfile_time = $local_file_time;

				// get time of remote file
				if ( $this->setting['use_curl'] ) {
					$remote_file_time = $this->curl_last_mod( $this->setting['url'] );
				} else {
					$remote_file_time = $this->http_last_mod( $this->setting['url'], 1 );
				}

				// sanity check the remote date
				if ( $remote_file_time < ( time() - ( 365 * 24 * 60 * 60 ) ) ) { // $remote_file_time less than 1 year ago
					//echo "Warning: The last modified date of the Maxmind GeoLiteCity database ($remote_file_time) is out of expected range, continuing anyway<br />";

					if ( $this->setting['non_buffer'] ) {
						$this->non_buffer();
					}

					$this->setting['check_dates'] = 0;
					$remote_file_time             = 0;
				}

				if ( $remote_file_time > $local_gzfile_time || ! $this->setting['check_dates'] ) {
					$this->pre_download_file();
					$this->download_file( $this->setting['url'], $this->setting['geolite_path_upload'] . '/GeoLiteCity.dat.gz' );
				} else {
					// how many calendar days ago?
					$maxmind_days_ago = floor( ( strtotime( date( 'Y-m-d' ) . ' 00:00:00' ) - strtotime( date( 'Y-m-d', $remote_file_time ) . ' 00:00:00' ) ) / ( 60 * 60 * 24 ) );
					$yours_days_ago   = floor( ( strtotime( date( 'Y-m-d' ) . ' 00:00:00' ) - strtotime( date( 'Y-m-d', $local_gzfile_time ) . ' 00:00:00' ) ) / ( 60 * 60 * 24 ) );

					// newer file was not found
					echo '<br /><br /><b>' . __( 'You have the latest available Maxmind GeoLiteCity database', 'visitor-maps' ) . '</b><br />';
					echo __( 'Note: Maxmind usually updates GeoLiteCity once monthly on the 1st, but sometimes they update on the 2nd or 3rd or even later dates!', 'visitor-maps' ) . '<br />';
					echo sprintf( __( 'Maxmind last updated it %1$s (%2$d days ago)', 'visitor-maps' ), date( $this->setting['date_format'], $remote_file_time ), $maxmind_days_ago ) . '<b>' . sprintf( __( 'this is the <a href="%s" target="_new">newest file available</a>', 'visitor-maps' ), 'http://www.maxmind.com/app/geolitecity' ) . '</b>.<br />';

					echo sprintf( __( 'You updated to the current GeoLiteCity database on %s (%d days ago)', 'visitor-maps' ), date( $this->setting['date_format'], $local_file_time ), $yours_days_ago ) . '<br /><b>' . __( 'No new updates are available today.', 'visitor-maps' ) . '</b><br />';
					$this->setting['test_lookup'] and $this->test_lookup();
					$this->done();

					return;
				}
			} else {
				$this->pre_download_file();
				$this->download_file( $this->setting['url'], $this->setting['geolite_path_upload'] . '/GeoLiteCity.dat.gz' );
			}

			clearstatcache();

			if ( ! file_exists( $this->setting['geolite_path_upload'] . '/GeoLiteCity.dat.gz' ) ) {
				$this->error_exit( __( 'Error: Download failed, the GeoLiteCity.dat.gz download file was not found', 'visitor-maps' ) );
			}

			echo __( 'Download success, uncompressing file, please wait', 'visitor-maps' ) . '<br />';

			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer();
			}

			$fp = fopen( $this->setting['geolite_path_upload'] . "/GeoLiteCity.dat", "w" ) or $this->error_exit( __( 'unzip error: opening GeoLiteCity.dat file', 'visitor-maps' ) );

			$zp = gzopen( $this->setting['geolite_path_upload'] . '/GeoLiteCity.dat.gz', "r" ) or $this->error_exit( __( 'unzip error: gzopen opening GeoLiteCity.dat.gz file', 'visitor-maps' ) );

			if ( $zp ) {
				while ( ! gzeof( $zp ) ) {
					$buff1 = gzgets( $zp, 4096 );
					fputs( $fp, $buff1 );
				}
			}

			gzclose( $zp );
			fclose( $fp );

			if ( $this->setting['chmod'] ) {
				chmod( $this->setting['geolite_path_upload'] . '/GeoLiteCity.dat', 0644 ) or $this->error_exit( __( 'unzip error: Chmod 0644 GeoLiteCity.dat file failed', 'visitor-maps' ) );
			}

			unlink( $this->setting['geolite_path_upload'] . '/GeoLiteCity.dat.gz' );

			clearstatcache();

			if ( ! file_exists( $this->setting['geolite_path_upload'] . '/GeoLiteCity.dat' ) ) {
				$this->error_exit( __( 'Error: the GeoLiteCity.dat file was not found', 'visitor-maps' ) );
			}

			$from = $this->setting['geolite_path_upload'] . '/GeoLiteCity.dat';

			if ( is_file( $from ) ) {
				if ( is_dir( $this->setting['geolite_path_upload'] ) ) {
					rename( $from, $this->setting['geolite_path'] . '/GeoLiteCity.dat' );
				}
			}

			if ( ! file_exists( $this->setting['geolite_path'] . '/GeoLiteCity.dat' ) ) {
				$this->error_exit( __( 'Error: the GeoLiteCity.dat file was not found', 'visitor-maps' ) );
			}

			echo '<b>' . __( 'Install/Update completed', 'visitor-maps' ) . '</b><br />';

			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer();
			}

			$this->setting['test_lookup'] and $this->test_lookup();
			$this->done();

			return;
		}

		function non_buffer() {
			ob_flush();

			usleep( 700000 );
		}

		function non_buffer_fast() {
			ob_flush();
		}

		function test_lookup() {
			echo '<br /><b>' . __( 'Testing a lookup with your IP address:', 'visitor-maps' ) . '</b><br />';

			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer();
			}

			clearstatcache();

			if ( ! is_file( $this->setting['geolite_path'] . '/GeoLiteCity.dat' ) ) {
				$this->error_exit( __( 'test_lookup error: the GeoLiteCity.dat file was not found', 'visitor-maps' ) );
			}

			$ip_address = Visitor_Maps::$core->get_ip_address();

			$array = $this->get_location_info( "$ip_address" );

			echo '
                ' . __( 'IP:', 'visitor-maps' ) . '           ' . $ip_address . '<br />
                ' . __( 'City:', 'visitor-maps' ) . '         ' . $array['city_name'] . '<br />
                ' . __( 'State Name:', 'visitor-maps' ) . '   ' . $array['state_name'] . '<br />
                ' . __( 'State Code:', 'visitor-maps' ) . '   ' . $array['state_code'] . '<br />
                ' . __( 'Country Name:', 'visitor-maps' ) . ' ' . $array['country_name'] . '<br />
                ' . __( 'Country Code:', 'visitor-maps' ) . ' ' . $array['country_code'] . '<br />
                ' . __( 'Lat:', 'visitor-maps' ) . '          ' . $array['latitude'] . '<br />
                ' . __( 'Lon: ', 'visitor-maps' ) . '         ' . $array['longitude'] . '<br />
                ';

			if ( $array['longitude'] == 0 && $array['latitude'] == 0 ) {
				echo '(' . __( 'Note: location information was not available, this is normal if on a private network.', 'visitor-maps' ) . ')';
			}
		}

		function done() {
			echo '<p>';
			echo '<strong><a href="' . admin_url( 'index.php?page=visitor-maps' ) . '">' . esc_html( __( 'View Who\'s Online', 'visitor-maps' ) ) . '</a></strong><br />';
			echo '<strong><a href="' . admin_url( 'options-general.php?page=visitor_maps_opt' ) . '">' . esc_html( __( 'Visitor Maps Options', 'visitor-maps' ) ) . '</a></strong>';
			echo '</p>';

			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer();
				ob_end_flush();
			}
		}

		function curl_last_mod( $remote_file ) {
			if ( ! function_exists( 'curl_init' ) ) {
				return $this->http_last_mod( $remote_file, 1 );
			}

			$last_modified = $ch = $resultString = $headers = '';
			$ch            = curl_init();

			curl_setopt( $ch, CURLOPT_USERAGENT, $this->setting['user_agent'] );
			curl_setopt( $ch, CURLOPT_URL, $remote_file );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 15 ); // 5 sec timeout
			curl_setopt( $ch, CURLOPT_HEADER, 1 );  // make sure we get the header
			curl_setopt( $ch, CURLOPT_NOBODY, 1 );  // make it a http HEAD request
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 ); // write the response to a variable
			curl_setopt( $ch, CURLOPT_FILETIME, 1 );

			$i = 1;
			while ( $i ++ <= 2 ) {
				if ( curl_exec( $ch ) === false ) {
					$this->error_exit( 'curl_last_mod ' . __( 'error: could not connect to remote file', 'visitor-maps' ) ); // could not connect
					//   echo 'Curl error: ' . curl_error($ch);
					//   exit;
				}
				$headers = curl_getinfo( $ch );
				if ( $headers['http_code'] != 200 ) {
					sleep( 3 );  // Let's wait 3 seconds to see if its a temporary network issue.
				} elseif ( $headers['http_code'] == 200 ) {
					// we got a good response, drop out of loop.
					break;
				}
			}

			$last_modified = $headers['filetime'];
			if ( $headers['http_code'] != 200 ) {
				$this->error_exit( 'curl_last_mod ' . __( 'error: fetching timestamp failed for URL, 404 not found?', 'visitor-maps' ) ); // remote file not found
			}

			curl_close( $ch );

			// sanity check the remote_file date
			// sometimes CURL returns -1 instead of the timestamp on some peoples servers
			// use http to check the date instead.
			if ( $last_modified < ( time() - ( 365 * 24 * 60 * 60 ) ) ) { // $remote_file_time less than 1 year ago
				return $this->http_last_mod( $remote_file, 1 );
			}

			return $last_modified;
		}

		function http_last_mod( $url, $format = 0 ) {
			$url_info = parse_url( $url );
			$port     = isset ( $url_info['port'] ) ? $url_info['port'] : 80;
			$fp       = fsockopen( $url_info['host'], $port, $errno, $errstr, 15 );
			if ( $fp ) {
				$head = "HEAD " . @$url_info['path'] . "?" . @$url_info['query'];
				$head .= " HTTP/1.0\r\n";
				$head .= "Host: " . @$url_info['host'] . "\r\n";
				$head .= "User-Agent: " . $this->setting['user_agent'] . "\r\n\r\n";
				fputs( $fp, $head );
				while ( ! feof( $fp ) ) {
					if ( $header = trim( fgets( $fp, 1024 ) ) ) {
						if ( $format == 1 ) {
							$h2 = explode( ': ', $header );
							// the first element is the http header type, such as HTTP/1.1 200 OK,
							// it doesn't have a separate name, so we have to check for it.
							if ( $h2[0] == $header ) {
								$headers['status'] = $header;
								if ( ! preg_match( '|HTTP/1.* 200 OK|i', $header ) ) {
									$this->error_exit( 'http_last_mod ' . __( 'error: fetching timestamp failed for URL 404 not found?', 'visitor-maps' ) );
								}
							} else {
								$headers[ strtolower( $h2[0] ) ] = trim( $h2[1] );
							}
						} else {
							$headers[] = $header;
						}
					}
				}
				fclose( $fp );

				return strtotime( $headers['last-modified'] );
			} else {
				$this->error_exit( 'http_last_mod ' . __( 'error: could not connect to remote URL', 'visitor-maps' ) );
			}
		}

		function pre_download_file() {
			echo '<br /><br /><b>' . __( 'Please allow plenty of time for the approximately 19meg file to download.', 'visitor-maps' ) . '</b><br />';
			echo __( 'The time needed depends on your server download speed.', 'visitor-maps' ) . '<br />';
			echo __( 'A timeout can happen if the connection is really slow causing this to take more than 3 minutes.', 'visitor-maps' ) . '<br />';

			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer();
			}

			@set_time_limit( 180 );
		}

		function download_file( $file_source, $file_target ) {
			$rh = fopen( $file_source, 'rb' ) or $this->error_exit( 'download_file ' . __( 'error: reading or opening file', 'visitor-maps' ) );
			$wh = fopen( $file_target, 'wb' ) or $this->error_exit( 'download_file ' . __( 'error: cannot write to file, check server permission settings', 'visitor-maps' ) );

			echo __( 'Download started, (counting megs)', 'visitor-maps' );
			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer_fast();
			}

			$counter = 0;
			$megs    = 1;
			$every   = 1024;

			while ( ! feof( $rh ) ) {
				if ( ( $counter % $every ) == 0 ) {
					echo '(' . $megs . ')';
					$megs ++;
					if ( $this->setting['non_buffer'] ) {
						$this->non_buffer_fast();
					}
				}
				if ( fwrite( $wh, fread( $rh, 1024 ) ) === false ) {
					$this->error_exit( 'download_file ' . __( 'error: cannot write to file, check server permission settings', 'visitor-maps' ) );

					return true;
				}

				$counter ++;
			}

			fclose( $rh );
			fclose( $wh );

			if ( $this->setting['chmod'] ) {
				chmod( $file_target, 0644 ) or $this->error_exit( 'download_file ' . __( 'error: CHMOD 0644 download file failed', 'visitor-maps' ) );
			}

			@set_time_limit( 30 );

			echo '.<br />';

			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer_fast();
			}

			return false;
		}

		function error_exit( $error ) {
			echo $error;

			if ( $this->setting['non_buffer'] ) {
				$this->non_buffer();
				ob_end_flush();

				exit;
			}
		}

		function get_location_info( $user_ip ) {
			// this function looks up location info from the maxmind geoip database and returns country_info array lookup country info for this ip
			// geoip lookup

			require_once Visitor_Maps::$dir . 'vendor/autoload.php';

			$reader = new Reader( Visitor_Maps::$upload_dir . Visitor_Maps::DATABASE_NAME . Visitor_Maps::DATABASE_EXT );

			if ( '127.0.0.1' !== $user_ip ) {
				$record = $reader->city( $user_ip );
			}

			$location_info = array();    // Create Result Array

			$location_info['provider']     = '';
			$location_info['city_name']    = ( isset ( $record->city->name ) ) ? $record->city->name : '-';
			$location_info['state_name']   = ( isset ( $record->mostSpecificSubdivision->name ) ) ? $record->mostSpecificSubdivision->name : '-';
			$location_info['state_code']   = ( isset ( $record->mostSpecificSubdivision->isoCode ) ) ? strtoupper( $record->mostSpecificSubdivision->isoCode ) : '-';
			$location_info['country_name'] = ( isset ( $record->country->name ) ) ? $record->country->name : '-';
			$location_info['country_code'] = ( isset ( $record->country->isoCode ) ) ? strtoupper( $record->country->isoCode ) : '-';
			$location_info['latitude']     = ( isset ( $record->location->latitude ) ) ? $record->location->latitude : '-';
			$location_info['longitude']    = ( isset ( $record->location->longitude ) ) ? $record->location->longitude : '-';

			return $location_info;
		}
	}
}
