<?php
if (! class_exists('SLP_BaseClass_UI')) {

    /**
     * A base class that helps add-on packs separate UI functionality.
     *
     * Add on packs should include and extend this class.
     *
     * This allows the main plugin to only include this file when NOT in admin mode.
     *
     * @package StoreLocatorPlus\BaseClass\UI
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2013 - 2016 Charleston Software Associates, LLC
     *
     * @property    SLP_BaseClass_Addon     $addon
     * @property    SLPlus                  $slplus
     * @property    string[]                $js_requirements    An array of the JavaScript hooks that are needed by the userinterface.js script.
     *              userinterface.js is only loaded if the file exists in the include directory.
     * @property    string[]                $js_settings        JavaScript settings that are to be localized as a <slug>_settings JS variable.
     */
    class SLP_BaseClass_UI extends SLPlus_BaseClass_Object {
        protected $addon;
        protected $js_requirements = array();
        protected $js_settings = array();
        protected $slplus;

        /**
         * Instantiate the admin panel object.
         */
        function initialize() {
            $this->do_ui_startup();
            $this->add_hooks_and_filters();
        }

        /**
         * Add the plugin specific hooks and filter configurations here.
         *
         * Should include WordPress and SLP specific hooks and filters.
         */
        function add_hooks_and_filters() {
            add_action( 'slp_after_render_shortcode' , array( $this , 'enqueue_ui_javascript'   ) );
            add_action( 'slp_after_render_shortcode' , array( $this , 'enqueue_ui_css'          ) );

            // Add your hooks and filters in the class that extends this base class.
        }

        /**
         * Insert add-on options into the [slp_option <js|nojs|name>="option_value"] shortcode.
         *
         * @param $attributes
         */
        function augment_slp_option_shortcode( $attributes ) {

            foreach ($attributes as $name=>$value) {
                switch (strtolower($name)) {
                    case 'js':
                        if ( isset( $this->addon->options[$value] ) ) {
                            $this->slplus->options[$value] = $this->addon->options[$value];
                        }
                        return $attributes;

                    case 'nojs':
                        if ( isset( $this->addon->options[$value] ) ) {
                            $this->slplus->options_nojs[$value] = $this->addon->options[$value];
                        }
                        return $attributes;

                    case 'name':
                        if ( isset( $this->addon->options[ $value ] ) ) {
                            $attributes['value'] = $this->addon->options[$value];
                            $attributes['key'] = $value;
                            $attributes['type'] = $this->addon->short_slug;
                        }
                        return $attributes;

                    default:
                        break;
                }
            }
            return $attributes;
        }

        /**
         * Things we want our add on packs to do when they start.
         */
        function do_ui_startup() {
            // Add your startup methods you want the add on to run here.
        }

        /**
         * If the file userinterface.css exists, enqueue it.
         */
        function enqueue_ui_css() {
            if ( file_exists( $this->addon->dir . 'css/userinterface.css' ) ) {
                wp_enqueue_style( $this->addon->slug . '_userinterface_css' , $this->addon->url . '/css/userinterface.css' );
            }
        }

        /**
         * If the file userinterface.js , enqueue it.
         */
        function enqueue_ui_javascript() {
            $this->js_requirements = array_merge( $this->js_requirements , array( 'jquery' ) );
	        $enq = false;

            if ( file_exists( $this->addon->dir . 'include/userinterface.js' ) ) {
	            wp_enqueue_script( $this->addon->slug . '_userinterface', $this->addon->url . '/include/userinterface.js', $this->js_requirements );
	            $enq = true;
            }
	        if ( file_exists( $this->addon->dir . 'js/userinterface.js' ) ) {
		        wp_enqueue_script( $this->addon->slug . '_userinterface', $this->addon->url . '/js/userinterface.js', $this->js_requirements );
		        $enq = true;
	        }

            if ( $enq ) {
	            $this->js_settings[ 'locations' ] = array(
		            'get_option' => site_url( 'wp-json/store-locator-plus/v2/options/' ),
	            );
                wp_localize_script( $this->addon->slug . '_userinterface' ,
                    preg_replace('/\W/' , '' , $this->addon->get_meta('TextDomain') ) . '_settings' ,
                    $this->js_settings
                    );
            }
        }
    }
}