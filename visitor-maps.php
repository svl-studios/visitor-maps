<?php
/**
 * Plugin Name: Who's Online with Visitor Maps
 * Plugin URI:  http://www.svlstudios.com
 * Description: Displays Visitor Maps with location pins, city, and country. Includes a Who's Online Sidebar to show how many users are online. Includes a Who's Online admin dashboard to view visitor details. The visitor details include: what page the visitor is on, IP address, host lookup, online time, city, state, country, geolocation maps and more. No API key needed.  <a href="admin.php?page=visitor-maps_opt">Settings</a> | <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=V3BPEZ9WGYEYG">Donate</a>
 * Version:     2.0.1
 * Text Domain: visitor-maps
 * Author:      Kevin Provance d/b/a SVL Studios
 * Author URI:  http://www.svlstudios.com
 *
 * @package     VisitorMaps
 * @author      SVL Studios by Kevin Provance <support@svlstudios.com>
 * @copyright   2021 Kevin Provance d/b/a SVL Studios
 */

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'class-visitor-maps.php';

register_activation_hook( __FILE__, array( 'Visitor_Maps', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Visitor_Maps', 'deactivate' ) );

Visitor_Maps::instance();
