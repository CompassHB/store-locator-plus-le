<?php

if ( ! class_exists( 'SLPlus' ) ) {
	require_once( SLPLUS_PLUGINDIR . 'include/base_class.object.php' );
	require_once( SLPLUS_PLUGINDIR . 'include/base/SLP_Object_With_Objects.php' );

	/**
	 * The base plugin class for Store Locator Plus.
	 *
	 * @property        SLP_Activation              $Activation
	 * @property        string                      $admin_page_prefix              Admin page prefix, needs to be changed if the
	 * @property        SLP_AJAX                    $AjaxHandler                    The Ajax Handler object.
	 * @property        SLP_BaseClass_Addon         $current_addon                  The current addon being processed.
	 * @property        string                      $current_admin_page
	 * @property        SLPlus_Location             $currentLocation                The current location.
	 * @property        array                       $data                           The settings that impact how the plugin renders.   TODO: Eliminate all references to this ASAP.
	 * @property        SLPlus_Data                 $database                       The data interface helper.
	 * @property        wpdb                        $db                             The global $wpdb object for WordPress.
	 * @property        string                      $dir                            Full path to this plugin directory.
	 * @property        array                       $infoFetched                    Array of slugs + booleans for plugins we've already fetched info for. named array, key = slug, value = true
	 * @property        string                      $installed_version              The version that was installed at the start of the plugin (prior installed version).
	 * @property        boolean                     $javascript_is_forced           Quick reference for the Force Load JavaScript setting.
	 * @property        string                      $name                           TODO: deprecate when no longer referenced use SLPLUS_NAME constant. (ELM, GFL, PRO, SME, TAG, UML, J, PAGES, POWER)
	 * @property        array                       $options                        The options that the user has set for Store Locator Plus that get localized to the slp.js script.
	 * @property        array                       $options_default                The default options (before being read from DB)
	 * @property        array                       $options_nojs                   The options that the user has set for Store Locator Plus that are NOT localized to the slp.js script.
	 * @property        array                       $options_nojs_default           The default options_nojs (before being read from DB).
	 * @property        string                      $plugin_url                     The URL that reaches the home directory for the plugin.
	 * @property-read   SLP_REST_Handler            $rest_handler                   The WP REST API handler.
	 * @property        bool                        $shortcode_was_rendered
	 * @property        boolean                     $site_architect_active          Is Site Architect active?
	 * @property        boolean                     $slider_rendered                True if slider was rendered, preventing multiple inline CSS calls.
	 * @property        array                       $slp_items                      Holder for get_item call stored values.   TODO: remove this when all get_item() calls reference options or options_nojs (EM, EXP, GFL, MM, SLP)
	 * @property        string                      $slp_store_url                  The SLP Store URL.
	 * @property        string                      $slug                           What slug do we go by?    May need to exists if we "eat our own food" extending base classes
	 * @property        string                      $styleHandle                    The style handle for CSS invocation.  Set to  'slp_admin_style'.
	 * @property        string                      $support_url                    The SLP Support Site URL.
	 * @property        SLP_Notification_Manager    $notifications
	 * @property        string                      $prefix                         TODO: deprecate when all references use the SLPLUS_PREFIX constant (MUP, ELM, GFI, GFL, PRO, SME, UML)
	 * @property        SLP_getitem                 $settings                       TODO: deprecate when (GFL) converts to slplus->get_item() , better yet replace with options/options_nojs.
	 * @property        string                      $url                            Full URL to this plugin directory.
	 * @property        SLPlus_WPML                 $WPML
	 *
	 * @property        array                       $objects                        Objects we care about, similar to objects of objects.
	 * @property        SLP_Actions                 $Actions                        Manages high-level WordPress action setup.
	 * @property        SLP_AddOns                  $AddOns                         Manager our add on objects and meta.
	 * @property        SLP_Admin_Locations         $Admin_Locations                Locations admin interface.
	 * @property        SLP_Admin_Locations_Actions $Admin_Locations_Actions        Manage location actions.
	 * @property        SLP_AdminUI                 $AdminUI
	 * @property        SLP_Country_Manager         $Country_Manager
	 * @property        SLP_Helper                  $Helper                         Methods that handle generic functions we use in a few classes.
	 * @property        SLP_Internet_Helper         $Internet_Helper                Assist with Internet queries like remote gets and JSON parsing.
	 * @property        SLP_Location_Manager        $Location_Manager               Higher level location manager things like initial distance updater and total location count management.
	 * @property        SLP_Style_Manager           $Style_Manager                  The thing that manages user-selectable styles.
	 * @property        SLP_SmartOptions            $SmartOptions                   The new settings interface for this plugin.
	 * @property        SLP_Text                    $Text                           The text manager to corral i18n/l10n gettext stuff.
	 * @property        SLP_Updates                 $Updates                        The plugin update system interface.
	 * @property        SLP_UI                      $UI                             The front-end user interface functions.
	 * @property        SLP_WPOption_Manager        $WPOption_Manager               Augment the WordPress get/update/delete option functions with fiters.
	 *
	 * @property        SLP_AddOns                  $add_ons                        TODO: remove when all ->add-ons references are ->AddOns Remove (MUP / CEX / ELM / GFI / GFL / TAG / POW)
	 * @property        SLP_Helper                  $helper                         TODO: remove when all ->helper references are ->Helper (MySLP-Dashboard, ELM, EXP, J, POW) drops (EM, PRO, TAG)
	 * @property        SLP_WPOption_Manager        $option_manager                 TODO: remove when all ->option_manager references are ->WPOoption_Manager (EXP, POW)
	 * @property        SLP_SmartOptions            $smart_options                  TODO: remove when all ->smart_options references are ->SmartOptions (MySLP-Dashboard)
	 */
	class SLPlus extends SLP_Object_With_Objects {
		const earth_radius_km  = 6371;         // Earth Volumetric Mean Radius (km)
		const earth_radius_mi  = 3959;         // Earth Volumetric Mean Radius (km)
		const locationPostType = 'store_page'; // Define the location post type.
		const locationTaxonomy = 'stores';     // Define the location post taxonomy.

		const url_services = 'https://www.storelocatorplus.com/';

		public $uses_slplus = false;

		public $min_add_on_versions = array(
			'slp-premier'    => '4.7.6',
			'slp-power'      => '4.7.6',
			'slp-experience' => '4.7.6',

			'slp-contact-extender'  => '4.2.01',
			'slp-directory-builder' => '4.2',
			'slp-enhanced-map'      => '4.4',
			'slp-enhanced-results'  => '4.4',
			'slp-enhanced-search'   => '99.99.99',             // unsupported
			'slp-janitor'           => '4.6.5',
			'slp-pages'             => '4.4.07',
			'slp-pro'               => '4.6.4',
			'slp-tagalong'          => '4.5.07',
			'slp-widgets'           => '99.99.99',             // unsupported

			'slp-event-location-manager'      => '4.5.02',
			'slp-extended-data-manager'       => '4.5.01',
			'slp-gravity-forms-integration'   => '4.5.01',
			'slp-gravity-forms-location-free' => '4.5.03',
			'slp-multi-map'                   => '99.99.99',  // unsupported
			'slp-social-media-extender'       => '4.5.02',
			'slp-user-managed-locations'      => '4.5.03',
		);

		public $objects;

		public $options = array(
			'ignore_radius'        => '0', // Passed in as form var from Experience
			'map_domain'           => 'maps.google.com',
			'no_autozoom'          => '0',
			'no_homeicon_at_start' => '1', // EM has admin UI for this setting.
			'radius_behavior'      => 'always_use',
			'use_sensor'           => '0',
		);

		public $options_nojs = array(
			'admin_locations_per_page' => '10',
			'broadcast_timestamp'      => '0',
			'build_target'             => 'production',
			'default_country'          => 'us',
			'extended_data_tested'     => '0',
			'force_load_js'            => '0',
			'geocode_retries'          => '3',
			'google_client_id'         => '',
			'google_private_key'       => '',
			'http_timeout'             => '10', // HTTP timeout for GeoCode Requests (seconds)
			'map_language'             => 'en',
			'next_field_id'            => 1,
			'next_field_ported'        => '',
			'no_google_js'             => '0',
			'php_max_execution_time'   => '600',
			'premium_user_id'          => '',
			'premium_subscription_id'  => '',
			'retry_maximum_delay'      => '5.0',
			'slplus_plugindir'         => SLPLUS_PLUGINDIR,
			'slplus_basename'          => SLPLUS_BASENAME,
			'themes_last_updated'      => '0',
			'ui_jquery_version'        => 'WP',
		);

		public  $Activation;
		public  $add_ons;
		public  $admin_page_prefix      = SLP_ADMIN_PAGEPRE;
		public  $AjaxHandler;
		public  $class_prefix           = 'SLP_';
		public  $current_addon;
		public  $current_admin_page     = '';
		public  $currentLocation;
		public  $data                   = array();
		public  $database;
		public  $db;
		public  $dir;
		public  $helper;
		public  $infoFetched            = array();
		public  $installed_version;
		public  $javascript_is_forced   = true;
		public  $location_manager;
		public  $name                   = SLPLUS_NAME;
		public  $network_multisite      = false;
		public  $notifications;
		public  $options_default        = array();
		public  $option_manager;
		public  $options_nojs_default   = array();
		public  $plugin_url             = SLPLUS_PLUGINURL;
		public  $prefix                 = SLPLUS_PREFIX;
		private $rest_handler;
		public  $settings;
		public  $shortcode_was_rendered = false;
		public  $site_architect_active  = false;
		public  $slider_rendered        = false;
		public  $slp_items              = array();
		public  $slp_store_url          = 'https://wordpress.storelocatorplus.com/';
		public  $slug;
		public  $smart_options;
		public  $styleHandle            = 'slp_admin_style';
		public  $support_url            = 'https://docs.storelocatorplus.com';
		public  $url;
		public  $WPML;

		/**
		 * Initialize a new SLPlus Object
		 */
		public function __construct() {
			$this->dir               = plugin_dir_path( SLPLUS_FILE );
			$this->slug              = plugin_basename( SLPLUS_FILE );
			$this->url               = plugins_url( '', SLPLUS_FILE );
			$this->installed_version = get_option( SLPLUS_PREFIX . "-installed_base_version", '' );     // Single Installs, or Multisite Per-Site Activation, on MS Network Activation returns version of MAIN INSTALL only
			$this->current_admin_page = $this->get_admin_page();
		}

		/**
		 * Update the base plugin if necessary.
		 */
		function activate_or_update_slplus() {
			if ( version_compare( $this->installed_version, SLPLUS_VERSION, '<' ) ) {
				$this->createobject_Activation();
				$this->Activation->update();
				if ( $this->Activation->disabled_experience ) {
					add_action(
						'admin_notices', create_function(
							'', "echo '<div class=\"error\"><p>" .
							    __( 'You must upgrade Experience add-on to 4.4.03 or higher or your site will crash. ', 'store-locator-le' ) .
							    "</p></div>';"
						)
					);
				}
			}

			global $site_architect;
			$this->site_architect_active = is_a( $site_architect, 'Site_Architect' );
		}

		/**
		 * Add meta links.
		 *
		 * TODO: ADMIN ONLY
		 *
		 * @param string[] $links
		 * @param string   $file
		 *
		 * @return string
		 */
		function add_meta_links( $links, $file ) {
			if ( $file == SLPLUS_BASENAME ) {
				$links[] = '<a href="' . $this->support_url . '" title="' . __( 'Documentation', 'store-locator-le' ) . '">' .
				           __( 'Documentation', 'store-locator-le' ) . '</a>';
				$links[] = '<a href="' . $this->slp_store_url . '" title="' . __( 'Buy Upgrades', 'store-locator-le' ) . '">' .
				           __( 'Buy Upgrades', 'store-locator-le' ) . '</a>';
				$links[] = '<a href="' . admin_url( 'admin.php?page=slp_experience' ) . '" title="' .
				           __( 'Settings', 'store-locator-le' ) . '">' . __( 'Settings', 'store-locator-le' ) . '</a>';
			}

			return $links;
		}

		/**
		 * Setup WordPress action scripts.
		 *
		 * Note: admin_menu is not called on every admin page load
		 * Reference: http://codex.wordpress.org/Plugin_API/Action_Reference
		 */
		function add_actions() {
			if ( is_admin() ) {
				add_action( 'admin_notices', array( $this->notifications, 'display' ) );
				add_filter( 'plugin_row_meta', array( $this, 'add_meta_links' ), 10, 2 );
			}
			add_action( 'plugins_loaded', array( $this, 'initialize_after_plugins_loaded' ) );
		}

		/**
		 * Create the notifications object and attach it.
		 *
		 */
		private function attach_notifications() {
			if ( ! isset( $this->notifications ) ) {
				require_once( 'manager/SLP_Notification_Manager.php' );
				$this->notifications = new SLP_Notification_Manager();
			}
		}

		/**
		 * Instantiate Activation Object
		 */
		public function createobject_Activation() {
			if ( ! isset( $this->Activation ) ) {
				require_once( 'SLP_Activation.php' );
				$this->Activation = new SLP_Activation();
			}
		}

		/**
		 * @deprecated 4.7.6 slplus->AddOns is started by SLP after plugins_loaded.
		 *
		 * TODO: Remove when CEX / ELM / GFI / GFL / TAG / POW no longer call this directly
		 */
		public function createobject_AddOnManager() {}

		/**
		 * Create the AJAX procssing object and attach to this->ajax
		 */
		function createobject_AJAX() {
			if ( ! isset( $this->ajax ) ) {
				require_once( 'class.ajax.php' );
				$this->ajax        = new SLP_AJAX();
				$this->AjaxHandler = $this->ajax;
			}
		}

		/**
		 * Create the settings object and attach it.
		 *
		 * Apparently only used for the get_item method.
		 *
		 * TODO: deprecate when (GFL) convert to slplus->get_item() , better yet replace with options/options_nojs.
		 *
		 */
		function create_object_settings() {
			if ( ! isset( $this->settings ) ) {
				require_once( 'class.getitem.php' );
				$this->settings = new SLP_getitem();
			}
		}

		/**
		 * Create the WPML object.
		 */
		function create_object_WPML() {
			if ( ! isset( $this->WPML ) ) {
				require_once( 'class.wpml.php' );
				$this->WPML = new SLPlus_WPML();
			}
		}

		/**
		 * Return a deprecated notification.
		 *
		 * TODO : move to a deprecated class.
		 *
		 * @param string $function_name name of function that is deprecated.
		 *
		 * @return string
		 */
		public function createstring_Deprecated( $function_name ) {
			return
				sprintf(
					__( 'The %s method is no longer available. ', 'store-locator-le' ), $function_name
				) .
				'<br/>' .
				__( 'It is likely that one of your add-on packs is out of date. ', 'store-locator-le' ) .
				'<br/>' .
				__( 'You need to <a href="%s" target="csa">upgrade</a> to the latest %s compatible version.', 'store-locator-le' );
		}

		/**
		 * Finish our starting constructor elements.
		 */
		public function initialize() {
			if ( class_exists( 'SLPlus_Location' ) == false ) {
				require_once( 'unit/SLPlus_Location.php' );
			}
			$this->currentLocation = new SLPlus_Location( array( 'slplus' => $this ) );

			require_once( 'module/options/SLP_WPOption_Manager.php' );
			require_once( 'module/options/SLP_SmartOptions.php' );

			// Attach objects
			//
			$this->attach_notifications();
			require_once( SLPLUS_PLUGINDIR . 'include/module/admin_tabs/settings/SLP_Helper.php' );
			$this->create_object_settings();  // TODO: deprecate when GFL calls slplus->get_item directly.

			// Setup pointers and WordPress connections
			//
			$this->add_actions();

			$this->create_object_database();

			// REST Processing
			// Needs to be loaded all the time.
			// The filter on REST_REQUEST true is after rest_api_init has been called.
			//
			$this->load_rest_handler();

			require_once('SLP_Actions.php');
			require_once( 'module/ui/SLP_UI.php' );
			
			add_action( 'plugins_loaded' , array( $this , 'activate_or_update_slplus' ) );
		}

		/**
		 * Things to do after all plugins are loaded.
		 */
		public function initialize_after_plugins_loaded() {
			load_plugin_textdomain( 'store-locator-le', false, plugin_basename( dirname( SLPLUS_PLUGINDIR . 'store-locator-le.php' ) ) . '/languages' );
			require_once( SLPLUS_PLUGINDIR . 'include/module/add_ons/SLP_AddOns.php' );

			$this->create_object_WPML();

			$this->SmartOptions->initialize_after_plugins_loaded();

			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				$this->createobject_AJAX();
			}
		}

		/**
		 * Setup the database properties.
		 *
		 * latlongRegex = '^\s*-?\d{1,3}\.\d+,\s*\d{1,3}\.\d+\s*$';
		 *
		 * @global wpdb $wpdb
		 */
		private function create_object_database() {
			global $wpdb;
			$this->db = $wpdb;
			require_once( SLPLUS_PLUGINDIR . 'include/class.data.php' );
			$this->database = new SLPlus_Data();
		}

		/**
		 * Enqueue the Google Maps Script
		 *
		 * TODO: the google_maps handle should be changed to something like slp_google_maps that is less generic.  Will break pages.  Need to update Power.
		 */
		public function enqueue_google_maps_script() {
			wp_enqueue_script( 'google_maps', $this->get_google_maps_url(), array(), SLPLUS_VERSION, ! $this->javascript_is_forced );
		}

		/**
		 * Get the current admin page.
		 *
		 * @return string
		 */
		private function get_admin_page() {
			if ( isset( $_GET['page'] ) ) {
				$plugin_page = stripslashes( $_GET['page'] );

				return plugin_basename( $plugin_page );
			}

			return '';
		}

		/**
		 * Get the Google Maps URL
		 */
		private function get_google_maps_url() {
			// Google Maps API for Work client ID
			//
			$client_id = ! empty( $this->options_nojs['google_client_id'] ) ?
				'&client=' . $this->options_nojs['google_client_id'] . '&v=3' :
				'';

			// Google JavaScript API server Key
			//
			$server_key = ! empty( $this->SmartOptions->google_server_key->value ) ?
				'&key=' . $this->SmartOptions->google_server_key->value :
				'';

			// Set the map language
			//
			$language = 'language=' . $this->options_nojs['map_language'];
			if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
				$lang_var = ICL_LANGUAGE_CODE;
				if ( ! empty( $lang_var ) ) {
					$language = 'language=' . ICL_LANGUAGE_CODE;
				}
			}

			// Base Google API URL
			//
			$google_api_url = 'https://maps.googleapis.com/maps/api/js?';

			// Region
			//
			require_once( SLPLUS_PLUGINDIR . 'include/module/i18n/SLP_Country_Manager.php' );
			if ( isset( $this->Country_Manager->countries[ $this->options_nojs['default_country'] ] ) ) {
				$country = strtoupper( $this->Country_Manager->countries[ $this->options_nojs['default_country'] ]->cctld );
			} else {
				$country = '';
			}
			$region = ! empty( $country ) ? '&region=' . $country : '';

			return $google_api_url . $language . $region . $client_id . $server_key;
		}

		/**
		 * Manage the old settings->get_item() calls.
		 *
		 * TODO: remove this when all get_item() calls reference options or options_nojs (GFL, MM, PRO, SLP)
		 *
		 * @deprecated
		 *
		 * @param            $name
		 * @param null       $default
		 * @param string     $separator
		 *
		 * @return array
		 */
		function get_item( $name, $default = null, $separator = '-' ) {
			$option_name = SLPLUS_PREFIX . $separator . $name;
			if ( ! array_key_exists( $option_name, $this->slp_items ) ) {
				if ( is_null( $default ) ) {
					$default = false;
				}
				$this->slp_items[ $option_name ] = $this->WPOption_Manager->get_wp_option( $option_name, $default );
			}

			return $this->slp_items[ $option_name ];
		}

		/**
		 * Get the product URL from the add-on manager.
		 *
		 * TODO: Remove when add ons use slplus->AddOns->get_product_url( $slug ) ( MUP )
		 *
		 * @deprecated 4.7.6 use slplus->AddOns->get( $slug , 'active' ) phpStorm replace slplus->get_product_url\((.*?)\) with slplus->AddOns->get\( $1 \)
		 * @param $slug
		 * @return string
		 */
		function get_product_url( $slug ) {
			return $this->AddOns->get_product_url( $slug );
		}

		/**
		 * Return true if the named add-on pack is active.
		 *
		 * TODO: Remove when add ons use slplus->AddOns->get( $slug , 'active' ) ( MUP , GFI , PRO, CPROF, POW, PREM )
		 *
		 * @deprecated 4.7.6 use slplus->AddOns->get( $slug , 'active' ) phpStorm replace ->is_AddonActive\((.*?)\) with ->AddOns->get\( $1 , 'active' \)
		 * @param string $slug
		 * @return boolean
		 */
		public function is_AddonActive( $slug ) {
			return $this->AddOns->get( $slug , 'active');
		}

		/**
		 * Return '1' if the given value is set to 'true', 'on', or '1' (case insensitive).
		 * Return '0' otherwise.
		 *
		 * Useful for checkbox values that may be stored as 'on' or '1'.
		 *
		 * @param        $value
		 * @param string $return_type
		 *
		 * @return bool|string
		 */
		public function is_CheckTrue( $value, $return_type = 'boolean' ) {
			if ( $return_type === 'string' ) {
				$true_value  = '1';
				$false_value = '0';
			} else {
				$true_value  = true;
				$false_value = false;
			}

			// No arrays allowed.
			//
			if ( is_array( $value ) ) {
				error_log( __( 'Array provided to SLPlus::is_CheckTrue()', 'store-locator-le' ) );
				error_log( print_r( debug_backtrace(), true ) );

				return $false_value;
			}

			if ( strcasecmp( $value, 'true' ) == 0 ) {
				return $true_value;
			}
			if ( strcasecmp( $value, 'on' ) == 0 ) {
				return $true_value;
			}
			if ( strcasecmp( $value, '1' ) == 0 ) {
				return $true_value;
			}
			if ( $value === 1 ) {
				return $true_value;
			}
			if ( $value === true ) {
				return $true_value;
			}

			return $false_value;
		}

		/**
		 * Check if certain safe mode restricted functions are available.
		 *
		 * exec, set_time_limit
		 *
		 * @param $funcname
		 *
		 * @return mixed
		 */
		public function is_func_available( $funcname ) {
			static $available = array();

			if ( ! isset( $available[ $funcname ] ) ) {
				$available[ $funcname ] = true;
				if ( ini_get( 'safe_mode' ) ) {
					$available[ $funcname ] = false;
				} else {
					$d = ini_get( 'disable_functions' );
					$s = ini_get( 'suhosin.executor.func.blacklist' );
					if ( "$d$s" ) {
						$array = preg_split( '/,\s*/', "$d,$s" );
						if ( in_array( $funcname, $array ) ) {
							$available[ $funcname ] = false;
						}
					}
				}
			}

			return $available[ $funcname ];
		}

		/**
		 * Checks if a URL is valid.
		 *
		 * @param $url
		 *
		 * @return bool
		 */
		public function is_valid_url( $url ) {
			$url = trim( $url );

			return ( ( strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0 ) &&
			         filter_var( $url, FILTER_VALIDATE_URL ) !== false );
		}

		/**
		 * Load the rest handler.
		 *
		 * The REST_REQUEST define is not ready this soon, so we need to do this on ALL WP loads. <sadness>
		 */
		private function load_rest_handler() {
			if ( ! isset( $this->rest_handler ) ) {
				require_once( 'module/REST/SLP_REST_Handler.php' );
				$this->rest_handler = new SLP_REST_Handler();
			}
		}

		/**
		 * Re-center the map as needed.
		 *
		 * Sets Center Map At ('map-center') and Lat/Lng Fallback if any of those entries are blank.
		 *
		 * Uses the Map Domain ('default_country') as the source for the new center.
		 */
		public function recenter_map() {
			if ( empty( $this->SmartOptions->map_center->value ) ) {
				$this->set_map_center();
			}
			if ( empty( $this->SmartOptions->map_center_lat->value ) ) {
				$this->set_map_center_fallback( 'lat' );
			}
			if ( empty( $this->SmartOptions->map_center_lng->value ) ) {
				$this->set_map_center_fallback( 'lng' );
			}
		}

		/**
		 * Set the Center Map At if the setting is empty.
		 */
		public function set_map_center() {
			require_once( SLPLUS_PLUGINDIR . 'include/module/i18n/SLP_Country_Manager.php' );
			$this->options['map_center'] = $this->Country_Manager->countries[ $this->options_nojs['default_country'] ]->name;
		}

		/**
		 * Set the map center fallback for the selected country.
		 *
		 * @param string $for latlng | lat | lng
		 */
		private function set_map_center_fallback( $for = 'latlng' ) {
			require_once( SLPLUS_PLUGINDIR . 'include/module/i18n/SLP_Country_Manager.php' );

			// If the map center is set to the country.
			//
			if ( $this->options['map_center'] == $this->Country_Manager->countries[ $this->options_nojs['default_country'] ]->name ) {

				// Set the default country lat
				//
				if ( ( $for === 'latlng' ) || ( $for === 'lat' ) ) {
					$this->SmartOptions->map_center_lat->value = $this->Country_Manager->countries[ $this->options_nojs['default_country'] ]->map_center_lat;
					$this->options['map_center_lat']            = $this->SmartOptions->map_center_lat->value;
				}

				// Set the default country lng
				//
				if ( ( $for === 'latlng' ) || ( $for === 'lng' ) ) {
					$this->SmartOptions->map_center_lng->value = $this->Country_Manager->countries[ $this->options_nojs['default_country'] ]->map_center_lng;
					$this->options['map_center_lng']            = $this->SmartOptions->map_center_lng->value;
				}
			}

			// No Lat or Lng in Country Data?  Go ask Google.
			//
			if ( empty( $this->SmartOptions->map_center_lng->value ) || empty( $this->SmartOptions->map_center_lat->value ) ) {
				$json = $this->currentLocation->get_LatLong( $this->SmartOptions->map_center->value );
				if ( ! empty( $json ) ) {
					$json = json_decode( $json );
					if ( is_object( $json ) && ( $json->{'status'} === 'OK' ) ) {
						if ( empty( $this->SmartOptions->map_center_lat->value ) ) {
							$this->SmartOptions->map_center_lat->value = $json->results[0]->geometry->location->lat;
							$this->options['map_center_lat']            = $this->SmartOptions->map_center_lat->value;
						}
						if ( empty( $this->SmartOptions->map_center_lng->value ) ) {
							$this->SmartOptions->map_center_lng->value = $json->results[0]->geometry->location->lng;
							$this->options['map_center_lng']            = $this->SmartOptions->map_center_lng->value;
						}
					}
				}
			}
		}

		/**
		 * Set the PHP max execution time.
		 */
		public function set_php_timeout() {
			ini_set( 'max_execution_time', $this->options_nojs['php_max_execution_time'] );
			if ( $this->is_func_available( 'set_time_limit' ) ) {
				set_time_limit( $this->options_nojs['php_max_execution_time'] );
			}
		}

		/**
		 * Set valid options from the incoming REQUEST
		 *
		 * @param mixed  $val - the value of a form var
		 * @param string $key - the key for that form var
		 */
		function set_ValidOptions( $val, $key ) {
			require_once( 'module/options/SLP_SmartOptions.php' );
			$this->SmartOptions->set_valid_option( $val, $key, $this->options, $this->options_default );
		}

		/**
		 * Set valid options from the incoming REQUEST
		 *
		 * Set this if the incoming value is not an empty string.
		 *
		 * @param mixed  $val - the value of a form var
		 * @param string $key - the key for that form var
		 */
		function set_ValidOptionsNoJS( $val, $key ) {
			require_once( 'module/options/SLP_SmartOptions.php' );
			$this->SmartOptions->set_valid_option( $val, $key, $this->options_nojs, $this->options_nojs_default );
		}
	}

	/**
	 * @var SLPlus $slplus
	 * @var SLPlus $slplus_plugin
	 */
	global $slplus;
	global $slplus_plugin;
	$slplus = new SLPlus();
	$slplus_plugin = $slplus;

	// We do not do any heartbeat processing
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && ! empty( $_POST[ 'action' ] ) && ( $_POST['action'] === 'heartbeat' ) ) {
		return;
	}

	$slplus->initialize();
}