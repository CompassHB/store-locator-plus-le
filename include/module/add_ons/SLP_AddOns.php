<?php
if ( ! class_exists( 'SLP_AddOns' ) ) {
	require_once( SLPLUS_PLUGINDIR . 'include/unit/SLP_AddOn.php' );


	/**
	 * The Store Locator Plus Add On Manager
	 *
	 * @property    array                 $available                        An array of all the available add-on packs we know about. $this->slplus->add_ons->available['slp-pro']['link']
	 * @var         string                $available                        [<slug>]['name']   translated text name of add on
	 * @var         string                $available                        [<slug>]['link']   full HTML anchor link to the product.  <a href...>Name</a>.
	 *
	 * @property    SLP_BaseClass_Addon[] $instances                        The add on objects in a named array.  The slug is the key. $instances['slp-pro'] => \SLPPro instance.
	 * @property    SLP_AddOn             $list                             The list of Add Ons we've been playing with.
	 * @property    boolean               $network_notified                 Network notified?
	 * @property    boolean               $premier_subscription_valid       Is the subscription valid?
	 * @property    string[]              $recommended_upgrades             Array of add-on slugs that have recommended upgrades.
	 * @property    string[]              $upgrades_already_recommended     Array of add-on slugs for those we already told the user about.
	 * @property    array                 $upgrade_paths                    key = the add-on slug to be upgraded, value = the slug for the add-on to upgrade to.
	 * @property-read   string            $wpdk_url
	 *
	 */
	class SLP_AddOns extends SLPlus_BaseClass_Object {
		public  $available                    = array();
		public  $instances                    = array();
		public  $list                         = array();
		public  $network_notified             = false;
		public  $premier_subscription_valid   = null;
		public  $recommended_upgrades         = array();
		private $upgrades_already_recommended = array();
		private $upgrade_paths                = array();
		private $wpdk_url                     = 'https://wordpress.storelocatorplus.com/wp-json/wp-dev-kit/v1/';

		/**
		 * Given the text to display and the leaf (end) portion of the product URL, return a full HTML link to the product page.
		 *
		 * @param string $text
		 * @param string $addon_slug
		 *
		 * @return string
		 */
		private function create_string_product_link( $text, $addon_slug ) {

			// If addon_slug is not a simple product slug but a URL:
			//
			if ( strpos( $addon_slug, '/' ) !== false ) {
				preg_match( '/^.*\/(.*)\/$/', $addon_slug, $matches );
				if ( isset( $matches[1] ) ) {
					$addon_slug = $matches[1];
				}
			}

			require_once( SLPLUS_PLUGINDIR . 'include/module/i18n/SLP_Text.php' );

			return $this->slplus->Text->get_web_link( 'shop_for_' . $addon_slug );
		}

		/**
		 * Return an upgrade recommended notice.
		 *
		 * @param   string $slug The slug for the add-on needed an upgrade.
		 *
		 * @return  string
		 */
		private function create_string_for_recommendations( $slug ) {
			$legacy_name  = $this->instances[ $slug ]->name;
			$upgrade_slug = $this->recommend_upgrade( $slug );
			$upgrade_name = $this->get_product_url( $upgrade_slug );

			return
				sprintf( __( 'The %s add-on is not running as efficiently as possible. ', 'store-locator-le' ), $legacy_name ) .
				'<br/>' .
				sprintf( __( 'Upgrading to the latest %s add-on is recommended. ', 'store-locator-le' ), $upgrade_name );
		}

		/**
		 * Create the recommended upgrades notification text.
		 *
		 * @return string
		 */
		public function get_recommendations_text() {
			$html = '';
			foreach ( $this->recommended_upgrades as $slug ) {
				if ( ! in_array( $slug, $this->upgrades_already_recommended ) ) {
					$html .= $this->create_string_for_recommendations( $slug );
					$this->upgrades_already_recommended[] = $slug;
				}
			}

			return $html;
		}

		/**
		 * Fetched installed and active version info.
		 *
		 * @return array
		 */
		public function get_versions() {
			$version_info = array();

			foreach ( $this->instances as $slug => $instance ) {
				$version_info[ $slug ] = $this->get( $slug , 'version' );
			}

			return $version_info;
		}

		/**
		 * Get the most recently installed version, does not have to be active currently.
		 *
		 * @param   string $slug
		 * @return  string
		 */
		public function get_installed_version( $slug ) {
			$plugin = $this->get( $slug );
			$was_active = $plugin->was_active;  // This forces both active and previously active versions to be set.
			return $plugin->version;
		}

		/**
		 * Return the product URL of the specified registered/active add-on pack.
		 *
		 * @param string $slug
		 *
		 * @return string
		 */
		public function get_product_url( $slug ) {

			// Active object, get from meta
			//
			if ( isset( $this->instances[ $slug ] ) && is_object( $this->instances[ $slug ] ) ) {

				// Newer meta interface
				//
				if ( method_exists( $this->instances[ $slug ], 'get_meta' ) ) {
					return $this->create_string_product_link( $this->instances[ $slug ]->name, $this->instances[ $slug ]->get_meta( 'PluginURI' ) );
				}

				// Older meta interface
				// Remove after all plugins are updated to have get_meta()
				//
				return $this->create_string_product_link( $this->instances[ $slug ]->name, $this->instances[ $slug ]->metadata['PluginURI'] );
			}

			// Manually registered in available array, link exists.
			//
			if ( isset( $this->available[ $slug ]['link'] ) ) {
				return $this->available[ $slug ]['link'];
			}
			if ( isset( $this->available[ $slug ] ) ) {
				switch ( $slug ) {

					case 'slp-enhanced-map':
						return $this->create_string_product_link( $this->available[ $slug ]['name'], 'slp4-enhanced-map' );

					case 'slp-enhanced-results':
						return $this->create_string_product_link( $this->available[ $slug ]['name'], 'slp4-enhanced-results' );

					case 'slp-enhanced-search':
						return $this->create_string_product_link( $this->available[ $slug ]['name'], 'slp4-enhanced-search' );

					case 'slp-experience':
						return $this->create_string_product_link( $this->available[ $slug ]['name'], 'experience' );

					case 'slp-power':
						return $this->create_string_product_link( $this->available[ $slug ]['name'], 'power' );

					case 'slp-premier':
						return $this->create_string_product_link( $this->available[ $slug ]['name'], 'premier-subscription' );

					case 'slp-pro' :
						return $this->create_string_product_link( $this->available[ $slug ]['name'], 'slp4-pro' );

					case 'slp-tagalong' :
						return $this->create_string_product_link( $this->available[ $slug ]['name'], 'slp4-tagalong' );
				}

			}

			// Unknown
			//
			return $this->create_string_product_link( __( 'add on', 'store-locator-le' ), '' );
		}

		/**
		 * Get the add on (SLP_AddOn object)
		 *
		 * @param string        $slug
		 * @param string|null   $property   if provided will try to get this property
		 *
		 * @return mixed                    Returns the AddOn object if no property is specified, otherwise it returns the property value
		 */
		public function get( $slug , $property = null ) {
			if ( ! in_array( $slug , $this->list ) ) {
				$args[ 'slug' ] = $slug;
				if ( ! empty( $this->instances[ $slug ] ) ) {
					$args[ 'instance' ] = $this->instances[ $slug ];
				}
				$this->list[ $slug ] = new SLP_AddOn( $args );
			}
			if ( is_null( $property ) ) {
				return $this->list[ $slug ];
			} else {
				return $this->list[ $slug ]->$property;
			}
		}

		/**
		 * Returns true if an add on, specified by its slug, is active.
		 *
		 * TODO: remove when add ons use slplus->AddOns->get( <slug> , 'active' ) ( CEX , GFI )
		 *
		 * @deprecated 4.7.6 use slplus->AddOns->get( <slug> , 'active' )
		 * @param string $slug
		 *
		 * @return boolean
		 */
		public function is_active( $slug ) {
			$plugin = $this->get( $slug );
			return $plugin->active;
		}

		/**
		 * Add a sanctioned add on pack to the available add ons array.
		 *
		 * @param string              $slug
		 * @param string              $name
		 * @param boolean             $active
		 *
		 * @param SLP_BaseClass_Addon $instance
		 */
		private function make_available( $slug, $name, $active = false, $instance = null ) {
			if (
				! isset( $this->available[ $slug ] ) ||
				is_null( $this->available[ $slug ]['addon'] ) && ! is_null( $instance )
			) {

				$this->available[ $slug ] = array(
					'name'   => $name,
					'active' => $active,
					'addon'  => $instance
				);
			}
		}

		/**
		 * Network notice.
		 *
		 */
		public function network_notice() {
			if ( $this->network_notified ) {  // already notified
				return;
			}
			$this->network_notified = true;

			if ( ! is_multisite() ) { // not multisite
				return;
			}

			if ( empty( $this->instances ) ) { // no premium add ons
				return;
			}

			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {    // not network active
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			if ( ! is_plugin_active_for_network( $this->slplus->slug ) ) {
				return;
			}

			if ( $this->na_multisite_premier_license_is_valid() ) {  // premier license valid
				return;
			}

			add_action(
				'admin_notices', create_function(
					'', "echo '<div class=\"error\"><p>" .
					    __( 'Please purchase a <a href="https://wordpress.storelocatorplus.com/product/premier-subscription/" target="store_locator_plus">Store Locator Plus Premier Subscription</a> to license your add on packs for a multisite installation. ', 'store-locator-le' ) .
					    "</p></div>';"
				)
			);
		}

		/**
		 * Check Premier License for NA Multisite
		 *
		 * Use transients so we only check once per week.
		 */
		private function na_multisite_premier_license_is_valid() {

			// Set boolean for mainsite on network.
			//
			$main_id  = get_main_network_id();
			$blog_id  = get_current_blog_id();
			$mainsite = ( $blog_id === $main_id );

			// If mainsite, make sure we are not changing the SID/UID.
			//
			if ( $mainsite ) {
				$this->saving_new_subscription();
				$uid = $this->slplus->options_nojs['premium_user_id'];
				$sid = $this->slplus->options_nojs['premium_subscription_id'];
			} else {
				switch_to_blog( $main_id );
				$temp_nojs = $this->slplus->WPOption_Manager->get_wp_option( 'nojs' );
				$uid       = $temp_nojs['premium_user_id'];
				$sid       = $temp_nojs['premium_subscription_id'];
			}

			if ( empty( $uid ) || empty( $sid ) ) {
				restore_current_blog();
				return false;
			}

			$license_status = get_transient( 'slp-multisite-license-status' );

			if ( $license_status === false ) {
				$license_status = $this->get_premier_license_status( $uid, $sid );
				set_transient( 'slp-multisite-license-status', $license_status, WEEK_IN_SECONDS );
			}

			if ( ! $mainsite ) {
				restore_current_blog();
			}

			return ( $license_status === '"valid"' );
		}

		/**
		 * Get Premier License Status
		 *
		 * Use transients so we only check once per week.
		 *
		 * @params string   $uid
		 * @params string   $sid
		 *
		 * @return string
		 */
		private function get_premier_license_status( $uid = null, $sid = null ) {
			if ( is_null( $uid ) ) {
				$uid = $this->slplus->options_nojs['premium_user_id'];
			}
			if ( is_null( $sid ) ) {
				$sid = $this->slplus->options_nojs['premium_subscription_id'];
			}

			if ( ! $this->valid_license_format( $uid, $sid ) ) {
				return 'invalid';
			}

			$license_status = get_transient( 'slp-premier-subscription-status' );

			if ( $license_status !== 'valid' ) {
				$url = $this->wpdk_url . "license/validate/slp-premier/{$uid}/{$sid}";

				$request = wp_remote_get( $url, array( 'sslverify' => false, 'timeout' => '15', ) );
				if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
					$license_status = preg_replace( '/\W/', '', $request['body'] );
				} else {
					$license_status = 'invalid';
				}
				set_transient( 'slp-premier-subscription-status', $license_status, DAY_IN_SECONDS );
			}

			return $license_status;
		}

		/**
		 * Is the license format valid.
		 *
		 * @return bool
		 */
		private function valid_license_format( $uid, $sid ) {
			if ( empty( $uid ) || empty( $sid ) ) {
				return false;
			}

			if ( preg_match( '/^(\d+)$/', $uid ) !== 1 ) {
				return false;
			}

			if ( preg_match( '/^(\d+)_(\d+)$/', $sid ) !== 1 ) {
				return false;
			}

			return true;
		}

		/**
		 * Is the premier subscription valid?
		 *
		 * @return bool
		 */
		public function is_premier_subscription_valid() {
			if ( is_null( $this->premier_subscription_valid ) ) {
				$this->premier_subscription_valid = ( $this->get_premier_license_status() === 'valid' );
			}

			return $this->premier_subscription_valid;
		}

		/**
		 * Recommend an add-on for upgrading a legacy plugin.
		 *
		 * @param    string $slug
		 *
		 * @return    string    the slug of the add-on to upgrade to.
		 */
		public function recommend_upgrade( $slug ) {
			if ( empty( $this->upgrade_paths ) ) {
				$this->set_upgrade_paths();
			}

			return ( array_key_exists( $slug, $this->upgrade_paths ) ? $this->upgrade_paths[ $slug ] : $slug );
		}

		/**
		 * Register an add on object to the manager class.
		 *
		 * @param string              $slug
		 * @param SLP_BaseClass_Addon $object
		 */
		public function register( $slug, $object ) {
			if ( ! is_object( $object ) ) {
				return;
			}

			if ( property_exists( $object, 'short_slug' ) ) {
				$short_slug = $object->short_slug;
			} else {
				$slug_parts = explode( '/', $slug );
				$short_slug = str_replace( '.php', '', $slug_parts[ count( $slug_parts ) - 1 ] );
			}

			if ( ! isset( $this->instances[ $short_slug ] ) || is_null( $this->instances[ $short_slug ] ) ) {
				$this->instances[ $short_slug ] = $object;
				$this->make_available( $short_slug, $object->name, true, $object );
				$this->network_notice();
			}
		}

		/**
		 * Check to make sure we are not saving a new subscription first.
		 *
		 * @return bool
		 */
		private function saving_new_subscription() {
			if ( isset( $_POST ) && ! empty( $_POST ) && isset( $_REQUEST ) && isset( $_REQUEST['action'] ) && ( $_REQUEST['action'] === 'update' ) &&
			     isset( $_REQUEST['page'] ) && ( ( $_REQUEST['page'] === 'slp_general' ) || ( $_REQUEST['page'] === 'slp-network-admin' ) )
			) {
				$pre_sid = $this->slplus->options_nojs['premium_user_id'] . $this->slplus->options_nojs['premium_subscription_id'];

				$post_sid = $_POST['options_nojs']['premium_user_id'] . $_POST['options_nojs']['premium_subscription_id'];

				// Premier UID or SID changed.
				if ( $pre_sid !== $post_sid ) {
					$this->slplus->options_nojs['premium_user_id']         = $_POST['options_nojs']['premium_user_id'];
					$this->slplus->options_nojs['premium_subscription_id'] = $_POST['options_nojs']['premium_subscription_id'];
					delete_transient( 'slp-multisite-license-status' );
				}
			}
		}

		/**
		 * Set the add-on upgrade paths.
		 */
		private function set_upgrade_paths() {
			$this->upgrade_paths['slp-enhanced-map']     = 'slp-experience';
			$this->upgrade_paths['slp-enhanced-results'] = 'slp-experience';
			$this->upgrade_paths['slp-enhanced-search']  = 'slp-experience';
			$this->upgrade_paths['slp-widget']           = 'slp-experience';
		}

		/**
		 * Was the specified SLP add on ever active?
		 *
		 * @param string $slug  the add on slug
		 *
		 * @return bool         true if the add on appears to have ever been active
		 */
		public function was_active( $slug ) {
			$plugin = $this->get( $slug );
			return $plugin->was_active;
		}
	}

	/**
	 * @var SLPlus $slplus
	 */
	global $slplus;
	if ( is_a( $slplus, 'SLPlus' ) ) {
		$slplus->add_object( new SLP_AddOns() );
	}
	$slplus->add_ons = $slplus->AddOns; // TODO: remove when all ->add_ons references are ->AddOn (MUP, EXP, POW, PREM, all legacy)
}
