<?php
/**
 * Visitor Maps Enqueue Class
 *
 * @class   Visitor_Maps_Enqueue
 * @version 2.0.0
 * @package VisitorMaps
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Visitor_Maps_Enqueue' ) ) {

	/**
	 * Class Visitor_Maps_Enqueue
	 */
	class Visitor_Maps_Enqueue {

		/**
		 * Visitor_Maps_Enqueue constructor.
		 */
		public function __construct() {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		}

		/**
		 * Enqueue necessary files.
		 */
		public function enqueue() {
			$redux = ReduxFrameworkInstances::get_instance( Visitor_Maps::OPT_NAME );
			$page  = $redux->args['page_slug'];

			$min = Redux_Functions::isMin();

			wp_enqueue_script( 'visitor-maps', Visitor_Maps::$url . 'js/visitor-maps' . $min . '.js', array( 'jquery' ), Visitor_Maps::VERSION, true );

			wp_localize_script(
				'visitor-maps',
				'visitorMaps',
				array(
					'update_notice' => array(
						'enable'  => esc_html__( 'The Maxmind GeoLiteCity database is installed and enabled.', 'visitor-maps' ) . '&nbsp;&nbsp;',
						'lookup'  => '<a href="#" class="geolitecity-lookup">' . esc_html__( 'Run lookup test?', 'visitor-maps' ) . '</a>',
						'disable' => esc_html__( 'The Maxmind GeoLiteCity database is installed but not enabled (click the switch below).', 'visitor-maps' ),
						'update'  => esc_html__( 'An update is available.', 'visitor-maps' ) . '&nbsp;&nbsp;<a href="#" class="update-geolitecity">' . esc_html__( 'Install Update', 'visitor-maps' ) . '</a>.',
					),
				)
			);

			wp_enqueue_style( 'visitor-maps', Visitor_Maps::$url . 'css/visitor-maps.css', array(), Visitor_Maps::VERSION, 'all' );
		}
	}

	new Visitor_Maps_Enqueue();
}
