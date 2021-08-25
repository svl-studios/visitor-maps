<?php
/**
 * Visitor Maps Extended Class
 *
 * @class   Visitor_Maps_Extended
 * @version 2.0.0
 * @package VisitorMaps
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Visitor_Maps_Extended' ) ) {

	/**
	 * Class Visitor_Maps_Extended
	 */
	class Visitor_Maps_Extended {
		/**
		 * Visitor_Maps_Extended constructor.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'vm_init' ) );
		}

		/**
		 * Init.
		 */
		public function vm_init() {
			if ( Visitor_Maps::VERSION !== get_option( 'vm_version' ) ) {
				Visitor_Maps::$instance->vm_install();
			}

			$vm_output = "<script id='visitor-maps' type='text/javascript'>\n";

			$htaccess = get_option( 'vm_htaccess' );

			if ( $htaccess ) {
				$vm_output .= "var htaccess = true, htaccessWarning = false, vmMessage = ''";
			} else {
				if ( get_option( 'htaccess_warning' ) ) {
					$vm_output .= 'var htaccess = false, htaccessWarning = true, vmMessage = "<h3>"' . esc_html__( 'Visitor Maps Notice', 'visitor-maps' ) . '"</h3>" ';

					if ( ! file_exists( ABSPATH . '.htaccess' ) ) {

						// translators: %s = path to .htaccess.
						$vm_output .= '<p>' . sprintf( esc_html__( '%s could not be found. IP and referer banning have been disabled.', 'visitor-maps' ), '<strong><em>' . ABSPATH . '.htaccess</em></strong>' );

						// translators: %s = path to .htaccess.
						$vm_output .= '</p><p>' . sprintf( esc_html__( 'To enable IP and referer banning features, be sure %s exists, then reactivate the plugin.', 'visitor-maps' ), '<strong><em>' . ABSPATH . '.htaccess</em></strong>' ) . '</p>';
						$vm_output .= '<form action="#" method="post"><input type="hidden" name="vm_mode_nonce" value="' . wp_create_nonce( 'vm_mode' ) . '">';
						$vm_output .= '<button title="' . esc_attr__( 'Press to generate a new file', 'visitor-maps' ) . ' - ' . ABSPATH . '.htaccess" type="submit">' . esc_html__( 'Create A New .htaccess File Now', 'visitor-maps' ) . '</button>';
						$vm_output .= '<input type="hidden" name="vm_mode" value="new htaccess" /></form>';
						$vm_output .= '<form action="#" method="post"><button title="' . esc_attr__( 'Press to dismiss this warning. Banning features will not be enabled.', 'visitor-maps' ) . '" type="submit">' . esc_html__( 'Dismiss Message', 'visiot-maps' ) . '</button>';
						$vm_output .= '<input type="hidden" name="vm_mode" value="dismiss htaccess warning" /></form>';
					} else {

						// translators: %1$s = path to .htaccess, %2$s = path to .htaccess.
						$vm_output .= sprintf( esc_html__( '%1$s could not be copied. IP and referer banning have been disabled. To enable those features, be sure %2$s exists, then reactivate the plugin.', 'visitor-maps' ), '<strong><em>' . ABSPATH . '.htaccess</em></strong>', '<strong><em>' . ABSPATH . '.htaccess</em></strong>' );
					}
				} else {
					$vm_output .= "var htaccess = false, htaccessWarning = false, vmMessage = ''";
				}
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$vm_output .= ", vmIp = '', adminIp = '" . sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) . "';\n";

			if ( isset( $_POST['vm_mode_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vm_mode_nonce'] ) ), 'vm_mode' ) ) {
				if ( isset( $_POST['vm_mode'] ) ) {
					switch ( $_POST['vm_mode'] ) {
						case 'ban referer':
							$vm_output .= $this->vm_ban_referer();
							break;

						case 'unban referer':
							$vm_output .= $this->vm_unban_referer();
							break;

						case 'purge referers':
							$vm_output .= $this->vm_purge_referers();
							break;

						case 'ban':
							$vm_output .= $this->vm_ban_ip();
							break;

						case 'unban':
							$vm_output .= $this->vm_unban_ip();
							break;

						case 'purge':
							$vm_output .= $this->vm_purge_ips();
							break;

						case 'new htaccess':
							$vm_output .= $this->vm_new_htaccess();
							break;

						case 'dismiss htaccess warning':
							update_option( 'htaccess_warning', false );
							$vm_output .= "htaccess = false;\nhtaccessWarning = false;\n";
							break;

						case 'set auto update':
							$vm_output .= $this->vm_auto_update();
							break;
					}
				}
			}

			$banned_ips          = get_option( 'vm_banned_ips', array() );
			$banned_referers     = get_option( 'vm_banned_referers', array() );
			$vm_auto_update      = ( ! get_option( 'vm_auto_update' ) ) ? 'false' : get_option( 'vm_auto_update' );
			$vm_auto_update_time = ( ! get_option( 'vm_auto_update_time' ) ) ? 5 : get_option( 'vm_auto_update_time' );

			$vm_output .= "var bannedIps = '" . implode( ', ', $banned_ips ) . "';bannedIps = bannedIps.split(', ').sort();\n";
			$vm_output .= "var bannedReferers = '" . implode( ', ', $banned_referers ) . "';bannedReferers = bannedReferers.split(', ').sort();\n";
			$vm_output .= "var adminHost = '" . sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) . "', vmAutoUpdate = " . $vm_auto_update . ', vmAutoUpdateTime = ' . $vm_auto_update_time . ", vmTableSelectorText = '" . __( 'FIRST', 'visitor-maps' ) . "', vmPoweredByText = '" . __( 'Powered by Visitor Maps', 'visitor-maps' ) . "', refererText = '" . __( 'Referer', 'visitor-maps' ) . "';\n"; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			$vm_output .= "</script>\n";

			echo $vm_output; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		/**
		 * Ban referer.
		 *
		 * @return string
		 */
		private function vm_ban_referer(): string {
			if ( isset( $_POST['vm_mode_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vm_mode_nonce'] ) ), 'vm_mode' ) ) {
				if ( isset( $_POST['vm_referer'] ) ) {
					$vm_referer      = str_replace( array( 'http://', 'https://' ), '', str_replace( 'www.', '', strtolower( trim( sanitize_text_field( wp_unslash( $_POST['vm_referer'] ) ) ) ) ) );
					$banned_referers = get_option( 'vm_banned_referers', array() );
					$vm_output       = '';

					if ( in_array( $vm_referer, $banned_referers, true ) ) {
						$vm_output .= "vmMessage += '" . $vm_referer . ' ' . esc_html__( 'is already banned!', 'visitor-maps' ) . "';\n";
					} else {
						array_push( $banned_referers, $vm_referer );
						update_option( 'vm_banned_referers', $banned_referers );
						$this->vm_rebuild_htaccess();
						$vm_output .= "vmMessage += '" . $vm_referer . ' ' . esc_html__( 'successfully banned!', 'visitor-maps' ) . "';\n";
					}
					$vm_output .= "var mode = 'ban referer';\n";
				}
			}

			return $vm_output;
		}

		/**
		 * Unbar referer.
		 *
		 * @return string
		 */
		private function vm_unban_referer(): string {
			if ( isset( $_POST['vm_mode_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vm_mode_nonce'] ) ), 'vm_mode' ) ) {
				if ( isset( $_POST['vm_referer'] ) ) {
					$vm_referer      = trim( sanitize_text_field( wp_unslash( $_POST['vm_referer'] ) ) );
					$banned_referers = get_option( 'vm_banned_referers' );
					$vm_output       = '';
					$new_list        = array();

					foreach ( $banned_referers as $referer ) {
						if ( $vm_referer !== $referer ) {
							array_push( $new_list, $referer );
						}
					}

					update_option( 'vm_banned_referers', $new_list );
					$this->vm_rebuild_htaccess();
					$vm_output .= "vmMessage += '" . $vm_referer . ' ' . esc_html__( 'successfully unbanned!', 'visitor-maps' ) . "';\n";
					$vm_output .= "var mode = 'unban referer';\n";
				}
			}

			return $vm_output;
		}

		/**
		 * Purge referers.
		 *
		 * @return string
		 */
		private function vm_purge_referers(): string {
			update_option( 'vm_banned_referers', array() );
			$this->vm_rebuild_htaccess();
			$vm_output .= "vmMessage += '" . esc_html__( 'All referers successfully unbanned!', 'visitor-maps' ) . "';\n";
			$vm_output .= "var mode = 'purge referers';\n";

			return $vm_output;
		}

		/**
		 * Ban IP.
		 *
		 * @return string
		 */
		private function vm_ban_ip(): string {
			$banned_ips = get_option( 'vm_banned_ips' );

			if ( isset( $_POST['vm_mode_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vm_mode_nonce'] ) ), 'vm_mode' ) ) {
				if ( isset( $_POST['vm_ip'] ) ) {
					$vm_ip     = trim( sanitize_text_field( wp_unslash( $_POST['vm_ip'] ) ) );
					$vm_output = '';

					if ( in_array( $vm_ip, $banned_ips, true ) ) {
						$vm_output .= "vmMessage += '" . $vm_ip . ' ' . esc_html__( 'is already banned!', 'visitor-maps' ) . "';\n";
					} else {
						array_push( $banned_ips, $vm_ip );
						update_option( 'vm_banned_ips', $banned_ips );
						$this->vm_rebuild_htaccess();
						$vm_output .= "vmMessage += '" . $vm_ip . ' ' . esc_html__( 'successfully banned!', 'visitor-maps' ) . "';\n";
					}

					$vm_output .= "vmIp += '" . $vm_ip . "';\n";
				}
			}

			return $vm_output;
		}

		/**
		 * Unban IP.
		 *
		 * @return string
		 */
		private function vm_unban_ip(): string {
			$banned_ips = get_option( 'vm_banned_ips' );
			$vm_output  = '';

			if ( isset( $_POST['vm_mode_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vm_mode_nonce'] ) ), 'vm_mode' ) ) {
				if ( isset( $_POST['vm_ip'] ) ) {
					$vm_ip      = trim( sanitize_text_field( wp_unslash( $_POST['vm_ip'] ) ) );
					$vm_output .= "var mode = 'unban';\n";
					$new_list   = array();

					foreach ( $banned_ips as $ip ) {
						if ( trim( $ip ) !== $vm_ip ) {
							array_push( $new_list, trim( $ip ) );
						}
					}

					update_option( 'vm_banned_ips', $new_list );
					$this->vm_rebuild_htaccess();

					$vm_output .= "vmMessage += '" . $vm_ip . ' ' . esc_html__( 'successfully unbanned!', 'visitor-maps' ) . "';\n";
					$vm_output .= "vmIp += '" . $vm_ip . "';\n";
				}
			}

			return $vm_output;
		}

		/**
		 * Purge IPs.
		 *
		 * @return string
		 */
		private function vm_purge_ips(): string {
			update_option( 'vm_banned_ips', array() );
			$this->vm_rebuild_htaccess();
			$vm_output .= "vmMessage += '" . esc_html__( 'All IP addresses successfully unbanned!', 'visitor-maps' ) . "';\n";

			return $vm_output;
		}

		/**
		 * Init filesystem.
		 *
		 * @return bool
		 */
		private function filesystem_init(): bool {
			$url         = 'admin.php?page=whos-been-online';
			$method      = '';
			$form_fields = '';

			$creds = request_filesystem_credentials( $url, $method, false, false, $form_fields );
			if ( false === $creds ) {
				return false; // stop the normal page form from displaying.
			}

			// now we have some credentials, try to get the wp_filesystem running.
			if ( ! WP_Filesystem( $creds ) ) {
				// our credentials were no good, ask the user for them again.
				request_filesystem_credentials( $url, $method, true, false, $form_fields );
				return false;
			}

			return true;
		}

		/**
		 * Filesystem read.
		 *
		 * @param string $filename     Name of file.
		 * @param bool   $return_array Return array.
		 *
		 * @return string|array
		 */
		private function filesystem_read( string $filename, bool $return_array = false ) {
			global $wp_filesystem;

			if ( $this->filesystem_init() ) {
				if ( $return_array ) {
					$content = $wp_filesystem->get_contents_array( $filename );
				} else {
					$content = $wp_filesystem->get_contents( $filename );
				}

				if ( false === $content ) {
					echo 'error reading file!';

					return '';
				}
			}

			return $content;
		}

		/**
		 * Filesystem write.
		 *
		 * @param string $filename Name of file.
		 * @param string $content  Data to write.
		 *
		 * @return bool
		 */
		private function filesystem_write( string $filename, string $content ): bool {
			global $wp_filesystem;

			if ( $this->filesystem_init() ) {
				if ( ! $wp_filesystem->put_contents( $filename, $content, FS_CHMOD_FILE ) ) {
					echo 'error saving file!';
					return false;
				}

				return true;
			}

			return false;
		}

		/**
		 * Write .htaccess
		 *
		 * @param string $content Data to write.
		 */
		private function vm_write( string $content ) {
			$file = ABSPATH . '.htaccess';

			$this->filesystem_write( $file, $content );

			chmod( ABSPATH . '.htaccess', 0755 );
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
		 * Append .htaccess with ban rules.
		 *
		 * @return string
		 */
		private function vm_new_htaccess(): string {
			$vm_output = '';

			if ( ! file_exists( ABSPATH . '.htaccess' ) ) {
				if ( $this->filesystem_write( ABSPATH . '.htaccess', '' ) ) {
					update_option( 'htaccess_warning', false );

					// translators: %s = path to .htaccess.
					$vm_output .= "vmMessage = '" . sprrintf( esc_html__( '%s successfully created!', 'visitor-maps' ), '<strong><em>' . ABSPATH . '.htaccess</em></strong> ' ) . ' <strong>' . esc_html__( 'Deactivate and reactivate the plugin now', 'visitor-maps' ) . ".</strong>';\n";
				} else {

					// translators: %s = path to .htaccess.
					$vm_output .= "vmMessage = '" . sprintf( esc_html__( '%s could not be created!', 'visitor-maps' ), '<strong><em>' . ABSPATH . '.htaccess</em></strong>' ) . ";\n";
				}
			}

			return $vm_output;
		}

		/**
		 * Auto update page.
		 *
		 * @return string
		 */
		private function vm_auto_update(): string {
			if ( isset( $_POST['vm_mode_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['vm_mode_nonce'] ) ), 'vm_mode' ) ) {
				if ( isset( $_POST['vm_auto_update'] ) && isset( $_POST['vm_auto_update_time'] ) ) {
					$vm_auto_update      = trim( sanitize_text_field( wp_unslash( $_POST['vm_auto_update'] ) ) );
					$vm_auto_update_time = ( '' === trim( sanitize_text_field( wp_unslash( $_POST['vm_auto_update_time'] ) ) ) ) ? 5 : trim( sanitize_text_field( wp_unslash( $_POST['vm_auto_update_time'] ) ) );
					settype( $vm_auto_update_time, 'integer' );
					$vm_auto_update_time = ( $vm_auto_update_time < 1 ) ? 1 : $vm_auto_update_time;

					update_option( 'vm_auto_update', $vm_auto_update );
					update_option( 'vm_auto_update_time', $vm_auto_update_time );

					$vm_output = "vmMessage += '" . esc_html__( 'Auto update settings successfully updated.', 'visitor-maps' ) . "';\n";
				}
			}

			return $vm_output;
		}

		/**
		 * Rebuild .htaccess.
		 *
		 * @param bool $backup DO backup flag.
		 */
		private function vm_rebuild_htaccess( bool $backup = true ) {
			if ( $backup ) {
				$htbackup = get_option( 'vm_htbackup' );

				$this->vm_backup_htaccess( $htbackup );
			}

			$htcontent = $this->filesystem_read( ABSPATH . '.htaccess' );

			if ( stristr( $htcontent, 'Visitor Maps' ) !== false ) {
				$part1   = explode( '# BEGIN Visitor Maps', $htcontent );
				$part2   = explode( '# END Visitor Maps', $htcontent );
				$htpart1 = trim( $part1[0] );
				$htpart2 = trim( $part2[1] );
			} else {
				$htpart1 = trim( $htcontent );
				$htpart2 = '';
			}

			$banned_referers = get_option( 'vm_banned_referers', array() );
			$banned_ips      = get_option( 'vm_banned_ips', array() );
			$new_htcontent   = "\n# BEGIN Visitor Maps";

			if ( false === $banned_referers ) {
				$banned_referers = array();
			}

			if ( false === $banned_ips ) {
				$banned_ips = array();
			}

			if ( isset( $banned_referers[0] ) && ! empty( $banned_referers[0] ) ) {
				$new_htcontent .= "\n# BEGIN Referers
<IfModule mod_rewrite.c>
# Uncomment 'Options +FollowSymlinks' if your server returns a '500 Internal Server' error.
# This means your server is not configured with FollowSymLinks in the '' section of the 'httpd.conf'.
# Contact your system administrator for advice with this issue.
# Options +FollowSymlinks
# Cond start ";

				foreach ( $banned_referers as $referer ) {
					$referer = str_replace( array( 'http://', 'https://' ), '', str_replace( 'www.', '', strtolower( trim( $referer ) ) ) );

					if ( '' !== $referer ) {
						if ( count( $banned_referers ) < 2 ) {
							$new_htcontent .= "\nRewriteCond %{HTTP_REFERER} " . str_replace( '.', '\.', $referer ) . ' [NC]';
						} else {
							$new_htcontent .= "\nRewriteCond %{HTTP_REFERER} " . str_replace( '.', '\.', $referer ) . ' [NC,OR]';
						}
					}
				}

				if ( 1 !== count( $banned_referers ) ) {
					$new_htcontent = trim( $new_htcontent, '[NC,OR]' );
				}

				$new_htcontent .= "\n# Cond end
RewriteRule .* - [F]
</IfModule>
# END Referers";
			}

			if ( '' !== $banned_ips[0] ) {
				$new_htcontent .= "\n# BEGIN banned ips
order allow,deny";
				foreach ( $banned_ips as $ip ) {
					if ( '' !== trim( $ip ) ) {
						$new_htcontent .= "\ndeny from " . $ip;
					}
				}

				$new_htcontent .= "\nallow from all
# END banned ips";
			}

			$new_htcontent .= "\n# END Visitor Maps\n";

			if ( 0 === count( $banned_referers ) && 0 === count( $banned_ips ) ) {
				$new_htcontent = '';
			}

			$this->vm_write( $htpart1 . $new_htcontent . $htpart2 );
		}
	}

	new Visitor_Maps_Extended();
}
