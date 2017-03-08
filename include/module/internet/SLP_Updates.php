<?php

if ( ! class_exists( 'SLP_Updates' ) ) {
	/**
	 * Checks remote SLP server for add-on pack updates.
	 *
	 * @property        string $current_version        The current version installed by the user.
	 * @property        string $license_status         License status.
	 * @property        string $plugin_slug            Plugin Slug (plugin_directory/plugin_file.php)
	 * @property        string $remote_version         The current production version reported by the SLP upates server.
	 * @property        string $slug                   Plugin name (plugin_file)
	 * @property        string $update_request_path    The plugin remote update path.
	 * @property-read   string $updater_url            URL of the update server.
	 */
	class SLP_Updates extends SLPlus_BaseClass_Object {
		public  $current_version;
		public  $license_status;
		public  $plugin_slug;
		public  $remote_version;
		public  $slug;
		public  $update_request_path;
		private $updater_url = 'https://wordpress.storelocatorplus.com/wp-admin/admin-ajax.php';

		/**
		 * Configure an instance of the WordPress Auto-Update class
		 *
		 * @param SLP_BaseClass_Addon $plugin
		 */
		function configure( $plugin ) {
			if ( ! isset( $plugin->min_slp_version ) ) {
				return;
			}
			$this->current_version = $plugin->get_addon_version();      // Plugin Specific
			$this->plugin_slug     = $plugin->slug;                     // Plugin Specific
			list ( $t1, $t2 ) = explode( '/', $plugin->slug );
			$this->slug      = str_replace( '.php', '', $t2 );          // Plugins Specific
			$this->set_update_request_path();
		}

		/**
		 * Add the WP and SLP hooks and filters.
		 */
		public function add_hooks_and_filters() {

			// define the alternative API for updating checking
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
			add_filter( 'http_request_args', array( $this, 'turn_off_ssl_verify' ), 20, 2 );

			// Define the alternative response for information checking
			add_filter( 'plugins_api', array( $this, 'check_info' ), 10, 3 );

			// Multisite needs to override WP update notices
			//
			remove_action( 'after_plugin_row_' . $this->plugin_slug, 'wp_plugin_update_row', 10, 2 );
			add_action( 'after_plugin_row_' . $this->plugin_slug, array( $this, 'show_update_notification' ), 10, 2 );
		}

		/**
		 * Add our self-hosted autoupdate plugin to the filter transient
		 *
		 * @param $transient
		 *
		 * @return object $ transient
		 */
		public function check_update( $transient ) {
			if ( ! is_object( $transient ) ) {
				$transient = new stdClass;
			}

			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			// Do not allow single-site updates on multisite, this is for network admins only.
			//
			global $pagenow;
			if ( 'plugins.php' == $pagenow && is_multisite() ) {
				return $transient;
			}

			if ( empty( $transient->response ) || empty( $transient->response[ $this->plugin_slug ] ) ) {
				$this->getRemote_version( $this->slug );

				// If a newer version is available, add the update
				//
				if ( version_compare( $this->current_version, $this->remote_version, '<' ) ) {
					$transient->response[ $this->plugin_slug ] = $this->get_version_object();
				}

				$transient->last_checked                  = time();
				$transient->checked[ $this->plugin_slug ] = $this->current_version;
			}

			return $transient;
		}

		/**
		 * Return a version object from the current version info.
		 *
		 * Run getRemove_version first.
		 *
		 * @return \stdClass
		 */
		private function get_version_object() {
			if ( ! isset( $this->remote_version ) || empty( $this->remote_version ) ) {
				$this->getRemote_version( $this->slug );
			}

			$obj              = new stdClass();
			$obj->slug        = $this->slug;
			$obj->new_version = $this->remote_version;
			$obj->url         = $this->update_request_path;
			$obj->package     = $this->update_request_path;

			return $obj;
		}

		/**
		 * Add our self-hosted description to the filter
		 *
		 * @see https://developer.wordpress.org/reference/functions/plugins_api/
		 *
		 * @param mixed  $orig original incoming args
		 * @param array  $action
		 * @param object $arg
		 *
		 * @return bool|object
		 */
		public function check_info( $orig, $action, $arg ) {

			// No slug? Not plugin update.
			//
			if ( empty( $arg->slug ) ) {
				return $orig;
			}
			if ( ! array_key_exists( $arg->slug, $this->slplus->AddOns->instances ) ) {
				return $orig;
			}
			if ( ! isset( $this->slplus->infoFetched[ $arg->slug ] ) ) {
				$information                             = $this->getRemote_information( $arg->slug );
				$this->slplus->infoFetched[ $arg->slug ] = true;

				return $information;
			}

			return $orig;
		}

		/**
		 * Return the remote version
		 * @param   string  $slug
		 * @return  string  $remote_version
		 */
		public function getRemote_version( $slug ) {
			$this->set_update_request_path( $slug );

			$request = wp_remote_post( $this->update_request_path . '&fetch=version' );
			if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
				$this->remote_version = $request['body'];
			} else {
				$this->remote_version = false;
			}

			return $this->remote_version;
		}

		/**
		 * Get information about the remote version
		 * @return mixed[] false if cannot get info, unserialized info if we could
		 */
		private function getRemote_information( $slug = null ) {
			$this->set_update_request_path( $slug );

			$request = wp_remote_post(
				$this->update_request_path . '&fetch=info', array(
					'sslverify' => false,
					'timeout'   => '60',
				)
			);
			if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
				return unserialize( $request['body'] );
			}

			return false;
		}

		/**
		 * Set the update path.
		 *
		 * @param   string  $slug
		 */
		private function set_update_request_path( $slug = null ) {

			// Implied slug, use the update class properties.
			//
			if ( is_null( $slug ) ) {
				$slug    = $this->slug;
				$version = $this->current_version;

			// Explicit slug, go look up the info.
			//
			} else {
				$version = $this->slplus->AddOns->instances[ $slug ]->options['installed_version'];
				if ( empty( $version ) ) {
					$version = $this->set_plugin_version( $slug );
				}
			}

			$this->update_request_path = $this->updater_url .
			                             '?action=wpdk_updater' .
			                             '&surl=' . urlencode( site_url() ) .
			                             '&uid=' . $this->slplus->options_nojs['premium_user_id'] .
			                             '&sid=' . $this->slplus->options_nojs['premium_subscription_id'] .
			                             '&target=' . $this->slplus->options_nojs['build_target'] .
			                             '&slug=' . $slug .
			                             '&current_version=' . $version;
		}

		/**
		 * Set the plugin version if not a registered plugin with options['installed_version'] set.
		 *
		 * @param $slug
		 *
		 * @return string
		 */
		function set_plugin_version( $slug ) {
			$version = '00.00.001';

			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
			$plugins = get_plugin_updates();
			foreach ( (array) $plugins as $plugin_file => $plugin_data ) {
				if ( $plugin_data->update->slug === $slug ) {
					$version = $plugin_data->Version;
					break;
				}
			}

			return $version;
		}

		/**
		 * show update notification row -- needed for multisite subsites, because WP won't tell you otherwise!
		 *
		 * @param string $file
		 * @param array  $plugin
		 */
		public function show_update_notification( $file, $plugin ) {
			if ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}
			if ( ! is_multisite() ) {
				return;
			}
			if ( $this->plugin_slug != $file ) {
				return;
			}

			// Remove our filter on the site transient
			remove_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ), 10 );

			$update_cache = get_site_transient( 'update_plugins' );
			$update_cache = is_object( $update_cache ) ? $update_cache : new stdClass();

			if ( empty( $update_cache->response ) || empty( $update_cache->response[ $this->plugin_slug ] ) ) {
				$cache_key    = md5( 'slp_plugin_' . sanitize_key( $this->plugin_slug ) . '_version_info' );
				$version_info = get_transient( $cache_key );
				if ( false === $version_info ) {
					$this->getRemote_version( $this->slug );
					$version_info = $this->get_version_object();
					set_transient( $cache_key, $version_info, 3600 );
				}
				if ( ! is_object( $version_info ) ) {
					return;
				}
				if ( version_compare( $this->current_version, $version_info->new_version, '<' ) ) {
					$update_cache->response[ $this->plugin_slug ] = $version_info;
				}
				$update_cache->last_checked                  = time();
				$update_cache->checked[ $this->plugin_slug ] = $this->current_version;
				set_site_transient( 'update_plugins', $update_cache );
			}

			// Restore our filter
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
		}

		/**
		 * Turn off SSL verification on plugin updates.
		 *
		 * @param array  $args
		 * @param string $url
		 *
		 * @return array
		 */
		public function turn_off_ssl_verify( $args, $url ) {
			if (
				( strpos( $url, 'https://' ) !== false ) &&
				( strpos( $url, 'action=wpdk_updater' ) && ( strpos( $url, '&fetch' ) === false ) )
			) {
				$args['sslverify'] = false;
			}

			return $args;
		}

	}

	/**
	 * @var SLPlus $slplus
	 */
	global $slplus;
	if ( is_a( $slplus, 'SLPlus' ) ) {
		$slplus->add_object( new SLP_Updates() );
	}
}