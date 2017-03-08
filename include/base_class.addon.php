<?php

if (!class_exists('SLP_BaseClass_Addon')) {
    if (!defined('SLPLUS_PLUGINDIR')) {
        require_once( dirname(__FILE__) . '/../store-locator-le.php' );
    }
    require_once( SLPLUS_PLUGINDIR . 'include/base/SLP_Object_WithOptions.php');

    /**
     * A base class that consolidates common add-on pack methods.
     *
     * Add on packs should base based on and extend this class.
     *
     * Setting the following properties will activate hooks to various
     * classes that will be instantiated as objects when needed:
     *
     * o 'admin_class_name' => 'Your_SLP_Admin_Class'
     * o 'user_class_name'  => 'Your_SLP_UI_Class'
     *
     * The admin class definition needs to go in your add-on pack
     * under the ./include directory and be named 'class.admin.php'.
     * The name of the class needs to match the provided string.
     * The admin object will only be instantiated when WordPress is
     * rendering the admin interface.
     *
     * The user class definition needs to go in your add-on pack
     * under the ./include directory and be named 'class.userinterface.php'.
     * The name of the class needs to match the provided string.
     * The user object will only be instantiated when WordPress is
     * rendering the WordPress front end.
     *
     * This methodology provides a standard construct for coding admin-only
     * and user-interface-only elements of a WordPress add-on pack.   This
     * will mean less code is loaded into active ram, avoiding loading UI
     * only code when on the admin panel and vice versa.
     *
     * @property        SLP_BaseClass_Addon             $addon
     * @property        SLP_BaseClass_Admin             $admin                      The admin object.
     * @property        SLP_BaseClass_AJAX              $ajax                       The AJAX object.
     * @property        string                          $activation_class_name      The name of the activation class for this add on.
     * @property        string                          $admin_class_name           The name of the admin class for this add on.
     * @property        array                           $admin_menu_entries         array of menu entries, should be in a key=>value array where key = the menu text and value = the function or PHP file to execute.
     * @property        string                          $ajax_class_name            The name of the AJAX class for this add on.
     * @property        string                          $dir                        The directory the add-on pack resides in.
     * @property        string                          $file                       The add on loader file.
     * @property        SLP_Addon_MetaData   $meta
     * @property        string               $min_slp_version            Minimum version of SLP required to run this add-on pack in x.y.zz format.
     * @property        string               $name                       Text name for this add on pack.
     * @property-read   array                $objects                    A named array of our instantiated objects, the key is the class name the value is the object itself.
     * @property        SLP_WPOption_Manager $option_manager             Our options management interface.
     * @property        string               $option_name                The name of the wp_option to store serialized add-on pack settings.
     * @property        array                $option_defaults            The default values for options.   Set this in init_options for any gettext elements.  $option_defaults['setting'] = __('string to translate', 'textdomain');
     * @property        mixed[]              $options                    Settable options for this plugin. (Does NOT go into main plugin JavaScript)
     * @property        array                           $options_defaults           Default options.
     * @property        string                          $short_slug                 The short slug name.
     * @property        string                          $slug                       The slug for this plugin, usually matches the plugin subdirectory name.
     * @property        SLP_Updates                     $Updates
     * @property        string                          $url                        The url for this plugin admin features.
     * @property        string                          $userinterface_class_name   The name of the user class for this add on.
     * @property        SLP_BaseClass_UserInterface     $userinterface
     * @property        SLP_BaseClass_Widget            $widget                     The Widget object.
     * @property        string                          $widget_class_name          The name of the widget class for this add on.
     * @property        string                          $version                    Current version of this add-on pack in x.y.zz format.
     */
    class SLP_BaseClass_Addon extends SLP_Object_WithOptions {
        protected $addon;
        public $admin;
        public $ajax;
        public $activation_class_name;
        protected $admin_class_name;
        public $admin_menu_entries;
        protected $ajax_class_name;
        public $dir;
        public $file;
        public $meta;
        public $min_slp_version;
        public $name;
	    protected $objects;
        public $option_manager;
        public $option_name;
        public $option_defaults = array();
        public $options = array('installed_version' => '');
        public $options_defaults = array();
        public $short_slug;
        public $slug;
        public $Updates;
        public $url;
        protected $userinterface_class_name;
        public $userinterface;
        public $widget;
        public $widget_class_name;
        public $version;

        //  TODO: SLP 5.0 remove, drops support for legacy add-ons.
        public $metadata;

        /**
         * Run these things during invocation. (called from base object in __construct)
         */
        protected function initialize() {
            $this->create_object_option_manager();

            // Calculate file if not specified
            //
            if (is_null($this->file)) {
                $matches = array();
                preg_match('/^.*?\/(.*?)\.php/', $this->slug, $matches);
                $slug_base = !empty($matches) ? $matches[1] : $this->slug;
                $this->file = str_replace($slug_base . '/', '', $this->dir) . $this->slug;

                // If file was specified, check to set slug, url, dir if necessary
            //
            } else {
                if (!isset($this->dir)) {
                    $this->dir = plugin_dir_path($this->file);
                }
                if (!isset($this->slug)) {
                    $this->slug = plugin_basename($this->file);
                }
                if (!isset($this->url)) {
                    $this->url = plugins_url('', $this->file);
                }
            }
            $this->short_slug = $this->get_short_slug();

            // Object of Objects Init
	        parent::initialize();

            // Widgets
            // This needs to run before slp_init because it requires widgets_init
            // widgets_init runs on WP init at level 1
            // slp_init_complete runs on WP init at level 11
            //
            if (!empty($this->widget_class_name)) {
                $this->create_object_widget();
            }

            // A little earlier than slp_init for options
	        //
	        add_action( 'start_slp_specific_setup' , array( $this , 'load_options' ) );

            // When SLP finished initializing do this
            //
            add_action('slp_init_complete', array($this, 'slp_init'));
        }

        /**
         * Check the current version of this Add On works with the latest version of the SLP base plugin.
         */
        private function check_my_version_compatibility( ) {
            if ( isset(  $this->slplus->min_add_on_versions[ $this->short_slug ] )  &&
                 version_compare( $this->version , $this->slplus->min_add_on_versions[ $this->short_slug ] , '<' )
            ){
                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                deactivate_plugins( $this->file );
	            require_once( SLPLUS_PLUGINDIR . 'include/module/i18n/SLP_Text.php' );
	            $this->slplus->Helper->add_wp_admin_notification(
                    sprintf(
                        __( '%s version %s has been disabled. '  , 'store-locator-le' ) ,
                        $this->name,
                        $this->version
                    ) .
                    '<br/>'.
                    sprintf(
                        __( 'It is not compatible with version %s of Store Locator Plus. ' , 'store-locator-le' ) ,
                        SLPLUS_VERSION
                    ) .
                    '<br/>'.
                    $this->slplus->Text->get_web_link( 'check_website_for_upgrades' ) . '<br/>'.
                    $this->slplus->Text->get_web_link( 'new_addon_versions' )
                );
            }
        }

	    /**
	     * Load the options class to help the SLP smart options if we have that feature.
	     */
        function load_options() {
	        if ( ! empty( $this->objects[ 'Options' ] ) ) {
		        $this->instantiate( 'Options' );
	        }
        }

        /**
         * Things to do once SLP is alive.
         */
        function slp_init() {
	        $this->create_object_Updates( false );

	        $this->check_my_version_compatibility();

            $this->addon = $this;
            $this->register_addon($this->slug, $this);

            // Check the base plugin minimum version requirement.
            //
            $this->VersionCheck(array(
                'addon_name' => $this->name,
                'addon_slug' => $this->slug,
                'min_required_version' => $this->min_slp_version
            ));

            // Initialize The Options
            //
            $this->init_options();

            // Add Hooks and Filters
            //
	        $this->add_hooks_and_filters();

            // Admin Interface?
            //
             if (!empty($this->admin_class_name)) {
                add_action('slp_admin_menu_starting', array($this, 'createobject_Admin'), 5);
                add_filter('slp_menu_items', array($this, 'filter_AddMenuItems'));

                if (method_exists($this, 'admin_menu')) {
                    add_action('slp_admin_menu_starting', array($this, 'admin_menu'), 10);
                }

                if (method_exists($this, 'admin_init')) {
                    add_action('admin_init', array($this, 'admin_init'), 25);
                }
            }

            // User Interface?
            //
             if (!empty($this->userinterface_class_name)) {
                add_action('wp_enqueue_scripts', array($this, 'userinterface_init'));
            }

            // AJAX Processing
            //
            if (defined('DOING_AJAX') && DOING_AJAX && !empty($this->ajax_class_name)) {
                $this->createobject_AJAX();
            }

            $this->slplus->SmartOptions->execute_time_callbacks();
        }

        /**
         * Add the items specified in the menu_entries property to the SLP menu.
         *
         * If you make the 'slug' property of the $admin_menu_entries array = $this->addon->short_slug
         * you won't need to set this->addon->admin->admin_page_slug
         *
         * @param mixed[] $menuItems
         * @return mixed[]
         */
        function filter_AddMenuItems($menuItems) {
            if (!isset($this->admin_menu_entries)) {
                return $menuItems;
            }
            return array_merge((array) $menuItems, $this->admin_menu_entries);
        }

        /**
         * Add the plugin specific hooks and filter configurations here.
         *
         * The hooks & filters that go here are cross-interface element hooks/filters needed in 2+ locations:
         * - AJAX
         * - Admin Interface
         * - User Interface
         *
         * For example, custom taxonomy hooks and filters.
         *
         * Should include WordPress and SLP specific hooks and filters.
         */
        function add_hooks_and_filters() {
            // Add your hooks and filters in the class that extends this base class.
        }

	    /**
	     * Instantiate our objects ONCE and refer to them in an object array.
	     *
	     * @param string $class
	     * @param string $subdir
	     *
	     * @return mixed|void
	     */
	    public function create_object( $class , $subdir = '.' ) {
		    if ( ! isset( $this->objects[ $class ] ) ) {
			    include_once( $this->dir . $subdir . '/' . $class  . '.php' );
			    if ( ! class_exists( $class ) ) {
				    return;
			    }
			    $this->objects[ $class ][ 'subdir' ] = $subdir;
			    $this->objects[ $class ][ 'object' ] = new $class( array( 'addon' => $this ) );
		    }
		    return $this->objects[ $class ][ 'object' ];
	    }

        /**
         * Creates updates object AND checks for updates for this add-on.
         *
         * @param boolean $force
         */
        function create_object_Updates( $force ) {
	        if ( ! current_user_can( 'update_plugins' ) ) {
		         return;
	        }

            // Do not check for updates on any page request that contains slp_ in the name.
            if ( ! $force && ( isset( $_REQUEST['page'] ) && ( strpos( $_REQUEST['page'] , 'slp_' ) !== false ) ) ) {
                return;
            }

            if ( ! isset( $this->Updates ) ) {
                require_once( SLPLUS_PLUGINDIR . 'include/module/internet/SLP_Updates.php' );
                $this->Updates = new SLP_Updates( );
                if ( ! $force ) {
                	$this->Updates->configure( $this );
                }
                $this->Updates->add_hooks_and_filters();
            }
        }

        /**
         * Create the admin interface object and attach to this->admin
         *
         * Called on slp_admin_menu_starting.  If that menu is rendering, we are on an admin page.
         */
        function createobject_Admin() {
            if (!isset($this->admin)) {
                require_once( $this->dir . 'include/class.admin.php' );
                $this->admin = new $this->admin_class_name(array('addon' => $this));
            }
        }

        /**
         * Create the AJAX procssing object and attach to this->ajax
         */
        function createobject_AJAX() {
            if (!isset($this->ajax)) {
                require_once($this->dir . 'include/class.ajax.php');
                $this->ajax = new $this->ajax_class_name(array('addon' => $this));
            }
        }

        /**
         * Create the metadata object and store in $this->metadata.
         */
        private function create_object_meta() {
            if (!isset($this->meta)) {
                require_once( SLPLUS_PLUGINDIR . 'include/class.addon.metadata.php');
                $this->meta = new SLP_Addon_MetaData(array('addon' => $this));
            }
        }

        /**
         * Create an option manager object for this addon.
         */
        private function create_object_option_manager() {
            if ( ! isset( $this->WPOption_Manager ) ) {
                require_once( SLPLUS_PLUGINDIR . 'include/module/options/SLP_WPOption_Manager.php' );
                $this->WPOption_Manager = new SLP_WPOption_Manager( array( 'option_slug' => $this->option_name ) );
            }
        }

        /**
         * Create the user interface object and attach to this->UserInterface
         */
        function createobject_UserInterface() {
            if (!isset($this->userinterface)) {
                require_once($this->dir . 'include/class.userinterface.php');
                $this->userinterface = new $this->userinterface_class_name(
                        array(
                    'addon' => $this,
                    'slplus' => $this->slplus,
                        )
                );
            }
        }

        /**
         * Create and attach the widget object as needed.
         */
        private function create_object_widget() {
            if (!isset($this->widget)) {
                require_once( $this->dir . 'include/class.widget.php' );
                $this->widget = new $this->widget_class_name(array('addon' => $this));
            }
        }

        /**
         * Get the add-on pack version.
         *
         * @return string
         */
        public function get_addon_version() {
            return $this->get_meta('Version');
        }

        /**
         * Get the short slug, just the base/directory part of the fully qualified WP slug for this plugin.
         *
         * @return string
         */
        function get_short_slug() {
            $slug_parts = explode('/', $this->slug);
            return str_replace('.php', '', $slug_parts[count($slug_parts) - 1]);
        }

        /**
         * Run these things when running front-end (Non-Admin or AJAX) stuff.
         */
        function userinterface_init() {
            $this->createobject_UserInterface();
        }

        /**
         * Compare current plugin version with minimum required.
         *
         * Set a notification message.
         * Disable the requesting add-on pack if requirement is not met.
         *
         * $params['addon_name'] - the plain text name for the add-on pack.
         * $params['addon_slug'] - the slug for the add-on pack.
         * $params['min_required_version'] - the minimum required version of the base plugin.
         *
         * @param mixed[] $params
         */
        private function VersionCheck($params) {

            // Minimum version requirement not met.
            //
		    if (version_compare(SLPLUS_VERSION, $params['min_required_version'], '<')) {
                if (is_admin()) {
	                include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
                    deactivate_plugins(array($params['addon_slug']));
	                $this->slplus->Helper->add_wp_admin_notification(
		                sprintf(
			                __( '%s requires Store Locator Plus version %s. '  , 'store-locator-le' ) ,
			                $this->addon->name ,
			                $params['min_required_version']
		                ) .
	                    __( 'This add-on has been deactivated' , 'store-locator-le' )
	                );
                }
                return;
            }
        }

        /**
         * Get the add on metadata property as specified.
         *
         * @param string $property
         *
         * @return string
         */
        public function get_meta($property) {
            $this->create_object_meta();
            return $this->meta->get_meta($property);
        }

        /**
         * Initialize the options properties from the WordPress database.
         *
         */
        function init_options() {
            if (isset($this->option_name)) {
                $this->set_option_defaults();
                $dbOptions = $this->slplus->WPOption_Manager->get_wp_option($this->option_name);
                if (is_array($dbOptions)) {
                    $this->options = array_merge($this->options, $this->options_defaults);
                    $this->options = array_merge($this->options, $dbOptions);
                }
            }
        }

        /**
         * Register an add-on pack.
         *
         * @param string $slug
         * @param object $object
         */
        private function register_addon($slug, $object) {
            $this->slplus->AddOns->register($slug, $object);
        }

        /**
         * Set option defaults outside of hard-coded property values via an array.
         *
         * This allows for gettext() string translations of defaults.
         *
         * Only bring over items in default_value_array that have matching keys in $this->options already.
         *
         */
        function set_option_defaults() {
            $valid_options = array_intersect_key($this->option_defaults, $this->options);
            $this->options = array_merge($this->options, $valid_options);
            return;
        }

        /**
         * Set valid options according to the addon options array.
         *
         * @param $val
         * @param $key
         */
        function set_ValidOptions($val, $key) {
            $simpleKey = str_replace(SLPLUS_PREFIX . '-', '', $key);
            if (array_key_exists($simpleKey, $this->options)) {
                $this->options[$simpleKey] = stripslashes_deep($val);
            }
        }

        /**
         * Generate a proper setting name for the settings class.
         *
         * @param $setting
         *
         * @return string
         */
        function setting_name($setting) {
            return $this->addon->option_name . '[' . $setting . ']';
        }

    }

}