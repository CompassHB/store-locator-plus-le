<?php
if ( ! class_exists( 'SLP_Activation' ) ) {
	/**
	 * Store Locator Plus Activation handler.
	 *
	 * Mostly handles data structure changes.
	 * Update the plugin version in config.php on every structure change.
	 *
	 * @property   SLP_Upgrade $Upgrade
	 */
	class SLP_Activation extends SLP_Object_With_Objects {
		public  $disabled_experience = false;

		protected $objects = array(
			'Upgrade' => array(
				'subdir' => 'include/',
				'object' => null,
			),
		);

		/**
		 * Is this add-on being deactivated?
		 *
		 * @return bool
		 */
		private function being_deactivated() {
			$action_is_deactivate = isset( $_REQUEST['action'] ) && ( $_REQUEST['action'] === 'deactivate' );
			$deactivate_is_true   = isset( $_REQUEST['deactivate'] ) && ( $_REQUEST['deactivate'] === 'true' );
			$plugin_is_this_one   = isset( $_REQUEST['plugin'] ) && ( $_REQUEST['plugin'] === $this->slplus->slug );

			return ( $plugin_is_this_one && ( $action_is_deactivate || $deactivate_is_true ) );
		}

		/**
		 * Update the data structures on new db versions.
		 *
		 * @global object $wpdb
		 *
		 * @param type    $sql
		 * @param type    $table_name
		 *
		 * @return string
		 */
		function dbupdater( $sql, $table_name ) {
			global $wpdb;
			$retval = ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) ? 'new' : 'updated';

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			$were_showing_errors = $wpdb->show_errors;
			$wpdb->hide_errors();
			dbDelta( $sql );
			global $EZSQL_ERROR;
			$EZSQL_ERROR = array();
			if ( $were_showing_errors ) {
				$wpdb->show_errors();
			}

			return $retval;
		}

		/**
		 * Setup the extended data tables.
		 */
		function install_ExtendedDataTables() {
			global $wpdb;
			$charset_collate = '';
			if ( ! empty( $wpdb->charset ) ) {
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$charset_collate .= " COLLATE $wpdb->collate";
			}

			// Meta Data Table
			// Contains the architecture of the extended data fields.
			//
			$table_name   = $wpdb->prefix . 'slp_extendo_meta';
			$sql          = "CREATE TABLE $table_name (
                        id mediumint(8) not null auto_increment,
                        field_id varchar(15),
                        label varchar(250),
                        slug varchar(250),
                        type varchar(55),
                        options text,
                        KEY id (id),
                        KEY field_id (field_id),
                        KEY label (label),
                        KEY slug (slug)
                )
                $charset_collate
                ";
			$table_status = $this->dbupdater( $sql, $table_name );

			// Extendo Table Was Already Here
			// Set the next field id based on the extendo options and write it back out.
			//
			if ( $table_status === 'updated' ) {

				// Check that we did not import the next field ID first.
				//
				$slplus_options = get_option( SLPLUS_PREFIX . '-options_nojs' );
				if ( ! isset( $slplus_options['next_field_ported'] ) || empty( $slplus_options['next_field_ported'] ) ) {

					// Get the next field ID From Extendo
					//
					$extendo_options = get_option( 'slplus-extendo-options' );
					if ( isset ( $extendo_options['next_field_id'] ) ) {
						if ( ! is_array( $slplus_options ) ) {
							$slplus_options = array();
						}
						if ( isset ( $extendo_options['installed_version'] ) ) {
							unset( $extendo_options['installed_version'] );
						}
						array_merge( $slplus_options, $extendo_options );
					}

					// Set the ported flag and write that along with next field ID back to database.
					//
					$slplus_options['next_field_ported'] = '1';
					update_option( SLPLUS_PREFIX . '-options_nojs', $slplus_options );
				}
			}
		}

		/**
		 * Update main table
		 *
		 * As of version 3.5, use sl_option_value to store serialized options
		 * related to a single location.
		 *
		 * Update the plugin version in config.php on every structure change.
		 *
		 */
		function install_main_table() {

			global $wpdb;

			$charset_collate = '';
			if ( ! empty( $wpdb->charset ) ) {
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( ! empty( $wpdb->collate ) ) {
				$charset_collate .= " COLLATE $wpdb->collate";
			}
			$table_name = $wpdb->prefix . "store_locator";
			$sql        = "CREATE TABLE $table_name (
                sl_id mediumint(8) unsigned NOT NULL auto_increment,
                sl_store varchar(255) NULL,
                sl_address varchar(255) NULL,
                sl_address2 varchar(255) NULL,
                sl_city varchar(255) NULL,
                sl_state varchar(255) NULL,
                sl_zip varchar(255) NULL,
                sl_country varchar(255) NULL,
                sl_latitude char(14) NULL,
                sl_longitude char(14) NULL,
                sl_tags mediumtext NULL,
                sl_description text NULL,
                sl_email varchar(255) NULL,
                sl_url varchar(255) NULL,
                sl_hours text NULL,
                sl_phone varchar(255) NULL,
                sl_fax varchar(255) NULL,
                sl_image varchar(255) NULL,
                sl_private varchar(1) NULL,
                sl_neat_title varchar(255) NULL,
                sl_linked_postid int(11) NULL,
                sl_pages_url varchar(255) NULL,
                sl_pages_on varchar(1) NULL,
                sl_option_value longtext NULL,
                sl_initial_distance numeric(15,10),
                sl_lastupdated timestamp NOT NULL default CURRENT_TIMESTAMP,
                PRIMARY KEY  (sl_id),
                KEY sl_store (sl_store),
                KEY latlng (sl_latitude,sl_longitude),
                KEY sl_initial_distance (sl_initial_distance)
                ) 
                $charset_collate
                ";

			// If we updated an existing DB, do some mods to the data
			//
			if ( $this->dbupdater( $sql, $table_name ) === 'updated' ) {
				// We are upgrading from something less than 2.2
				//
				if ( floatval( $this->slplus->installed_version ) < 2.2 ) {
					dbDelta( "UPDATE $table_name SET sl_lastupdated=current_timestamp " .
					         "WHERE sl_lastupdated < '2011-06-01'"
					);
					dbDelta( "ALTER $table_name MODIFY sl_description text " );
				}

				// We are upgrading from something less than 4.6
				//
				if ( floatval( $this->slplus->installed_version ) < 4.6 ) {
					require_once( SLPLUS_PLUGINDIR . 'include/module/locations/SLP_Location_Manager.php' );
					$this->slplus->Location_manager->recalculate_initial_distance();
				}
			}
		}

		/**
		 * Add roles and cap.
		 */
		function add_splus_roles_and_caps() {
			$role = get_role( 'administrator' );
			if ( is_object( $role ) && ! $role->has_cap( 'manage_slp_admin' ) ) {
				$role->add_cap( 'manage_slp' );
				$role->add_cap( 'manage_slp_admin' );
				$role->add_cap( 'manage_slp_user' );
			}
		}

		/**
		 * Recursively copy source directory (or file) into destination directory.
		 *
		 * @param $source can be a file or a directory
		 * @param $dest   can be a file or a directory
		 */
		private function copyr( $source, $dest ) {
			if ( ! file_exists( $source ) ) {
				return;
			}

			// Make destination directory if necessary
			//
			if ( ! is_dir( $dest ) ) {
				wp_mkdir_p( $dest );
			}

			// Loop through the folder
			$dir = dir( $source );
			if ( is_a( $dir, 'Directory' ) ) {
				while ( false !== $entry = $dir->read() ) {

					// Skip pointers
					if ( $entry == '.' || $entry == '..' ) {
						continue;
					}

					$source_file = "{$source}/{$entry}";
					$dest_file   = "{$dest}/{$entry}";

					// Copy Files
					//
					if ( is_file( $source_file ) ) {
						$this->copy_newer_files( $source_file, $dest_file );
					}

					// Copy Symlinks
					//
					if ( is_link( $source_file ) ) {
						symlink( readlink( $source_file ), $dest_file );
					}

					// Directories, go deeper
					//
					if ( is_dir( $source_file ) ) {
						$this->copyr( $source_file, $dest_file );
					}
				}

				// Clean up
				$dir->close();
			}
		}

		/**
		 * Copy non-empty, readable files to destination if they are newer than the destination file.
		 * OR if the destination file does not exist.
		 *
		 * @param $source_file
		 * @param $destination_file
		 */
		public function copy_newer_files( $source_file, $destination_file ) {
			if ( empty( $source_file ) ) {
				return;
			}
			if ( ! is_readable( $source_file ) ) {
				return;
			}
			if (
				! file_exists( $destination_file ) ||
				(
					file_exists( $destination_file ) &&
					( filemtime( $source_file ) > filemtime( $destination_file ) )
				)
			) {
				copy( $source_file, $destination_file );
			}
		}

		/**
		 * Multisite upgrade.
		 */
		private function multisite_upgrade_options() {

			// WordPress 4.6
			//
			if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
				$sites = get_sites();
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					$this->Upgrade->migrate_settings();
					update_option( SLPLUS_PREFIX . '-installed_base_version', SLPLUS_VERSION );
					restore_current_blog();
				}

				return;
			}

			// WordPress < 4.6
			//
			if ( function_exists( 'wp_get_sites' ) ) {
				$sites = wp_get_sites();
				foreach ( $sites as $site ) {
					switch_to_blog( $site['blog_id'] );
					$this->Upgrade->migrate_settings();
					update_option( SLPLUS_PREFIX . '-installed_base_version', SLPLUS_VERSION );
					restore_current_blog();
				}

				return;
			}
		}

		/**
		 * Update the plugin.
		 */
		public function update() {
			if ( $this->being_deactivated() ) {
				return;
			}

			// Updating Previous Installation
			//
			if ( ! empty( $this->slplus->installed_version ) && ! is_null( $this->slplus->installed_version ) ) {

				// Restore Custom CSS Files
				$this->copyr( SLPLUS_UPLOADDIR . "css", SLPLUS_PLUGINDIR . "css" );

				// Core Icons Moved
				// Change home and end icon if it was in core/images/icons
				// @since 3.8.6
				//
				if ( is_dir( SLPLUS_PLUGINDIR . 'core/images/icons/' ) ) {
					$this->slplus->options['map_home_icon'] = $this->iconMapper( get_option( 'sl_map_home_icon' ) );
					$this->slplus->options['map_end_icon']  = $this->iconMapper( get_option( 'sl_map_end_icon' ) );
				}

				// Migrate settings from older versions
				//
				$this->instantiate( 'Upgrade' );

				// Not multisite or not network active, only update settings for this one site.
				//
				if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
				}
				if ( ! is_multisite() || ! is_plugin_active_for_network( $this->slplus->slug ) ) {
					$this->Upgrade->migrate_settings();

					// Multisite Network Active - migrate settings for each subsite
					//
				} else {
					$this->multisite_upgrade_options();
				}
			}

			// Update Tables, Setup Roles
			//
			$this->install_main_table();
			$this->install_ExtendedDataTables();
			$this->add_splus_roles_and_caps();

			// Always update these options
			//
			update_option( SLPLUS_PREFIX . '-installed_base_version', SLPLUS_VERSION );

			// Fresh install.
			//
			if ( empty( $this->slplus->installed_version ) ) {
				$this->slplus->database->set_database_meta();                                           // Connect DB meta info after create data objects.
				update_option( SLPLUS_PREFIX . '-options_nojs', $this->slplus->options_nojs );          // Save default options_nojs
				update_option( SLPLUS_PREFIX . '-options', $this->slplus->options );               // Save default options
			}
		}

		/**
		 * Updates specific to 3.8.6
		 *
		 * @param string $iconFile
		 *
		 * @return string icon file
		 */
		function iconMapper( $iconFile ) {
			$newIcon = $iconFile;

			// Azure Bulb Name Change (default destination marker)
			//
			$newIcon =
				str_replace(
					'/store-locator-le/core/images/icons/a_marker_azure.png',
					'/store-locator-le/images/icons/bulb_azure.png',
					$iconFile
				);
			if ( $newIcon != $iconFile ) {
				return $newIcon;
			}

			// Box Yellow Home (default home marker)
			//
			$newIcon =
				str_replace(
					'/store-locator-le/core/images/icons/sign_yellow_home.png',
					'/store-locator-le/images/icons/box_yellow_home.png',
					$iconFile
				);
			if ( $newIcon != $iconFile ) {
				return $newIcon;
			}

			// General core/images/icons replaced with images/icons
			$newIcon =
				str_replace(
					'/store-locator-le/core/images/icons/',
					'/store-locator-le/images/icons/',
					$iconFile
				);
			if ( $newIcon != $iconFile ) {
				return $newIcon;
			}

			return $newIcon;
		}

	}

}