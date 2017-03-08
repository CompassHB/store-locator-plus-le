<?php
if (! class_exists('SLP_BaseClass_Widget')) {

    /**
     * A base class that helps add-on packs separate admin functionality.
     *
     * Add on packs should include and extend this class.
     *
     * This allows the main plugin to only include this file in admin mode
     * via the admin_menu call.   Reduces the front-end footprint.
     *
     * @package StoreLocatorPlus\BaseClass\Widget
     * @author Lance Cleveland <support@storelocatorplus.com>
     * @copyright 2015 Charleston Software Associates, LLC
     *
     * @property        SLP_BaseClass_Addon     $addon
     *
     */
    class SLP_BaseClass_Widget extends SLPlus_BaseClass_Object {
        protected $addon;

        /**
         * Run these things during invocation. (called from base object in __construct)
         */
        protected function initialize() {
            add_action( 'widgets_init' , array( $this , 'register_widgets' ) );
            $this->add_hooks_and_filters();
        }

        /**
         * Extend this to add your custom WP and SLP hooks and filters.
         */
        protected function add_hooks_and_filters() {

        }

        /**
         * Register our widgets with WordPress
         *
         * The files must be in ./include/widgets/class.<slug>.php.
         * The classes must be named SLPWidget_<slug> and extend WP_Widget.
         */
        function register_widgets() {
            if ( ! is_dir( $this->addon->dir . 'include/widgets/' ) ) { return; }
            $widget_files = scandir( $this->addon->dir . 'include/widgets/' );
            $widget_slug = array();
            $this->slplus->current_addon = $this->addon;

            // @var WP_Widget_Factory   $wp_widget_factory
            global $wp_widget_factory;
            foreach ( $widget_files as $widget_file ) {
                if ( preg_match( '/^class\.([^.]*?)\.php/' , $widget_file , $widget_slug ) === 1 ) {
                    require_once( $this->addon->dir . 'include/widgets/' . $widget_file );
                    register_widget( 'SLPWidget_' . $widget_slug[1] );
                }
            }
        }
    }

}