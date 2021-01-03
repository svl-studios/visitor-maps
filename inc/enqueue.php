<?php

if ( !defined ( 'ABSPATH' ) ) {
    exit;
}

if (!class_exists('VisitorMapEnqueue')) {
    
    class VisitorMapEnqueue {
        public function __construct () {
            add_action('admin_enqueue_scripts', array($this,'enqueue'));
        }
        
        public function enqueue() {
            $redux = ReduxFrameworkInstances::get_instance(Visitor_Maps::OPT_NAME);
            $page = $redux->args['page_slug'];

            if (isset($_GET) && isset($_GET['page']) && ($_GET['page'] == $page || $_GET['page'] == 'visitor-maps')) {
                $min = ''; // Redux_Functions::isMin();

                wp_enqueue_script(
                    'visitor-maps',
                    Visitor_Maps::$url . 'js/visitor-maps' . $min . '.js',
                    array('jquery'),
                    Visitor_Maps::VERSION,
                    true
                );

                wp_localize_script(
                    'visitor-maps',
                    'visitorMaps',
                    array(
                        'update_notice' => array(
                            'enable'    => esc_html__( 'The Maxmind GeoLiteCity database is installed and enabled.', 'visitor-maps' ) . '&nbsp;&nbsp;',
                            'lookup'    => '<a href="#" class="geolitecity-lookup">' . esc_html__( 'Run lookup test?', 'visitor-maps' ) . '</a>',
                            'disable'   => esc_html__( 'The Maxmind GeoLiteCity database is installed but not enabled (click the switch below).', 'visitor-maps' ),
                            'update'    => esc_html__( 'An update is available.', 'visitor-maps' ) . '&nbsp;&nbsp;<a href="#" class="update-geolitecity">' .  esc_html__( 'Install Update', 'visitor-maps' ) . '</a>.'
                        )
                    )
                );

                wp_enqueue_style(
                    'visitor-maps',
                    Visitor_Maps::$url . 'css/visitor-maps.css',
                    array(),
                    Visitor_Maps::VERSION,
                    'all'
                );            
            }
        }
    }
    
    new VisitorMapEnqueue();
}