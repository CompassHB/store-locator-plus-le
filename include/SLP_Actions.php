<?php
if ( ! class_exists('SLP_Actions') ) {

	/**
	 * Store Locator Plus action hooks.
	 * The methods in here are normally called from an action hook that is called via the WordPress action stack.
	 */
	class SLP_Actions extends SLPlus_BaseClass_Object {
		private $menu_icon =  'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAyMC4wLjAsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiDQoJIHZpZXdCb3g9IjAgMCAzMy4zIDQyLjkiIHN0eWxlPSJlbmFibGUtYmFja2dyb3VuZDpuZXcgMCAwIDMzLjMgNDIuOTsiIHhtbDpzcGFjZT0icHJlc2VydmUiPg0KPHN0eWxlIHR5cGU9InRleHQvY3NzIj4NCgkuc3Qwe2ZpbGw6dXJsKCNTVkdJRF8xXyk7fQ0KPC9zdHlsZT4NCjx0aXRsZT5TTFAtbG9nby1zbWFsbC1jb2xvcjwvdGl0bGU+DQo8bGluZWFyR3JhZGllbnQgaWQ9IlNWR0lEXzFfIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjMzNy43ODY3IiB5MT0iLTI4Ny40ODIyIiB4Mj0iMzY5LjA5NjYiIHkyPSItMjg3LjQ4MjIiIGdyYWRpZW50VHJhbnNmb3JtPSJtYXRyaXgoMSAwIDAgLTEgLTMzNi42MiAtMjY1Ljk4NjcpIj4NCgk8c3RvcCAgb2Zmc2V0PSIwIiBzdHlsZT0ic3RvcC1jb2xvcjojRUY1MzIzIi8+DQoJPHN0b3AgIG9mZnNldD0iMC4xNiIgc3R5bGU9InN0b3AtY29sb3I6I0UwNDkyNiIvPg0KCTxzdG9wICBvZmZzZXQ9IjAuNDMiIHN0eWxlPSJzdG9wLWNvbG9yOiNDRDNDMkEiLz4NCgk8c3RvcCAgb2Zmc2V0PSIwLjcxIiBzdHlsZT0ic3RvcC1jb2xvcjojQzIzNTJDIi8+DQoJPHN0b3AgIG9mZnNldD0iMSIgc3R5bGU9InN0b3AtY29sb3I6I0JFMzIyRCIvPg0KPC9saW5lYXJHcmFkaWVudD4NCjxwYXRoIGNsYXNzPSJzdDAiIGQ9Ik0zMi41LDE3LjJjMC00LjgtMi4yLTkuMy01LjktMTIuM2MtMC42LTAuNS0xLjUtMC40LTIsMC4yYzAsMCwwLDAsMCwwbC0xLjEsMS40bDAsMGwtMS44LDIuM2wtMC4xLTAuMQ0KCWMtMC40LTAuMi0wLjgtMC40LTEuMi0wLjZjLTAuNy0wLjMtMS40LTAuNS0yLjItMC42Yy0wLjMtMC4xLTAuNy0wLjEtMS0wLjFjLTAuNCwwLTAuOCwwLTEuMSwwYy0wLjMsMC0wLjYsMC4xLTAuOSwwLjENCgljLTAuNCwwLjEtMC43LDAuMS0xLDAuMkMxMy44LDgsMTMuNCw4LjIsMTMsOC40Yy0wLjMsMC4xLTAuNiwwLjMtMC45LDAuNWMtMC40LDAuMi0wLjgsMC41LTEuMSwwLjhjLTAuNCwwLjMtMC44LDAuNy0xLjIsMS4xDQoJYy0wLjYsMC43LTEuMSwxLjQtMS41LDIuM2MtMC4yLDAuNC0wLjQsMC45LTAuNSwxLjNjLTAuMSwwLjItMC4xLDAuNC0wLjIsMC42djAuMWMwLDAuMi0wLjEsMC40LTAuMSwwLjdzMCwwLjQtMC4xLDAuNg0KCWMtMC4xLDAuNS0wLjEsMSwwLDEuNWMwLjEsMC41LDAuNSwwLjgsMC45LDAuOGgxNC41bDAsMGMtMC4xLDAuNC0wLjMsMC44LTAuNCwxLjJjLTAuMiwwLjQtMC40LDAuNy0wLjYsMWMtMC4yLDAuMy0wLjUsMC42LTAuNywwLjgNCgljLTAuMiwwLjItMC41LDAuNC0wLjcsMC41Yy0wLjMsMC4yLTAuNywwLjQtMSwwLjVjLTAuNCwwLjItMC44LDAuMy0xLjIsMC40Yy0wLjQsMC4xLTAuOCwwLjEtMS4yLDAuMWMtMC4zLDAtMC43LDAtMSwwDQoJYy0wLjctMC4xLTEuNC0wLjMtMi4xLTAuNmwtMC40LTAuMmMtMC40LTAuMi0wLjgtMC41LTEuMS0wLjljLTAuMi0wLjItMC40LTAuMy0wLjctMC4zYy0wLjIsMC0wLjUsMC4xLTAuNiwwLjNsLTAuOCwwLjhsLTAuMSwwLjENCgljLTAuMSwwLjEtMC4xLDAuMS0wLjIsMC4ybC0yLjcsMi43bC0wLjEtMC4xbC0wLjEtMC4xTDcsMjQuOWMtMS45LTIuMy0yLjktNS4zLTIuNy04LjNDNC41LDExLDguNSw2LjIsMTQuMSw0LjkNCgljMS44LTAuNCwzLjctMC40LDUuNSwwYzAuNywwLjIsMS40LTAuMSwxLjgtMC42YzAuNS0wLjcsMC40LTEuNi0wLjItMi4xQzIxLDIuMSwyMC44LDIsMjAuNiwxLjljLTguNC0yLjEtMTYuOSwzLTE5LDExLjQNCgljLTEuMiw0LjgtMC4xLDkuOCwzLDEzLjZsMCwwTDYsMjguNmMwLjUsMC42LDEuNSwwLjcsMi4xLDAuMmMwLDAsMC4xLTAuMSwwLjEtMC4xTDkuOSwyN2wwLDBsMi0yYzAuMiwwLjEsMC41LDAuMywwLjgsMC40DQoJYzAuOCwwLjQsMS43LDAuNywyLjUsMC44YzAuNCwwLjEsMC44LDAuMSwxLjIsMC4xaDAuNGMwLjcsMCwxLjMtMC4xLDItMC4yYzAuNC0wLjEsMC44LTAuMiwxLjItMC4zYzEtMC40LDEuOS0wLjksMi44LTEuNQ0KCWMwLjctMC41LDEuMi0xLjEsMS43LTEuOGMwLjUtMC42LDAuOC0xLjMsMS4xLTIuMWMwLjItMC41LDAuMy0wLjksMC40LTEuNGMwLjEtMC4zLDAuMS0wLjYsMC4xLTAuOWMwLTAuMywwLTAuNiwwLjEtMXYtMC4xDQoJYzAtMC4yLDAtMC40LDAtMC41YzAtMC4xLDAtMC4xLDAtMC4yaC0wLjhsMC44LDBjMC0wLjUtMC4zLTAuOS0wLjgtMC45Yy0wLjEsMC0wLjEsMC0wLjIsMGgtNi42Yy0wLjEsMC0wLjIsMC0wLjMsMGgtNy43bDAuMS0wLjINCgljMC40LTEuNCwxLjMtMi43LDIuNi0zLjVjMC43LTAuNCwxLjQtMC43LDIuMi0wLjljMC4zLTAuMSwwLjctMC4xLDEtMC4xYzAuMywwLDAuNiwwLDAuOSwwYzAuMiwwLDAuNSwwLjEsMC43LDAuMQ0KCWMwLjMsMC4xLDAuNywwLjIsMSwwLjNsMC4zLDAuMWwwLjcsMC40bDAuMSwwYzAuMywwLjIsMC42LDAuNSwwLjksMC44YzAuMiwwLjIsMC40LDAuMywwLjYsMC4zYzAuMiwwLDAuNS0wLjEsMC42LTAuM2wwLjgtMC44DQoJbDAuMy0wLjNsMi4zLTIuOWM0LjQsNC41LDQuNywxMS42LDAuOCwxNi41TDI2LjUsMjVsLTkuNywxMi4xTDE0LDMzLjZsMS4zLDAuMWgwLjJjMC45LDAsMS41LTAuNywxLjUtMS42YzAtMC44LTAuNi0xLjQtMS40LTEuNQ0KCWMtMS43LTAuMi0zLjUtMC4yLTUuMi0wLjJjLTAuNiwwLjEtMS4xLDAuNy0xLjIsMS4zbC0wLjYsNC42QzguNiwzNy4yLDkuMiwzNy45LDEwLDM4aDAuMmMwLjgsMCwxLjQtMC42LDEuNS0xLjRsMC4xLTAuOWw0LjQsNS40DQoJYzAuMywwLjMsMC44LDAuNCwxLjEsMC4xYzAsMCwwLjEtMC4xLDAuMS0wLjFMMjksMjYuOGwwLDBDMzEuMiwyNCwzMi40LDIwLjcsMzIuNSwxNy4yeiIvPg0KPC9zdmc+DQo='; // SVG SLP Logo

		/**
		 * Things to do at startup.
		 */
		function initialize( ) {
			add_action('init'               , array($this,'init'                    ) , 11  );

			add_action( "load-post.php"     , array( $this, 'action_AddToPageHelp'  ) , 20  );
			add_action( "load-post-new.php" , array( $this, 'action_AddToPageHelp'  ) , 20  );

            add_action( 'slp_deletelocation_starting', array( $this, 'delete_location_extended_data' ) ); // REST and ADMIN UI

			add_action('wp_head'            , array($this,'wp_head'                 )       ); // UI

			add_action('wp_footer'          , array($this,'wp_footer'               )       ); // UI

			add_action('shutdown'           , array($this,'shutdown'                )       ); // BOTH
		}

		/**
		 * Add SLP setting to the admin bar on the top of the WordPress site.
		 *
		 * @param $admin_bar
		 */
		public function add_slp_to_admin_bar( $admin_bar ) {
            if( ! current_user_can( 'manage_slp_admin' ) ) { return; }

            $args = array(
				'parent' => 'site-name',
				'id'     => 'store-locator-plus',
				'title'  => SLPLUS_NAME,
				'href'   => esc_url( admin_url( 'admin.php?page=slp_manage_locations' ) ),
				'meta'   => false
			);
			$admin_bar->add_node( $args );
		}

        /**
         * Delete extended data.
         *
         * Hooks onto slp_deletelocation_starting
         */
        public function delete_location_extended_data() {
            if ( ! $this->slplus->database->has_extended_data() ) { return; }
            $this->slplus->db->delete(
                $this->slplus->database->extension->data_table['name'],
                array( 'sl_id' => $this->slplus->currentLocation->id )
            );
        }

		/**
		 * Add content tab help to the post and post-new pages.
		 */
		public function action_AddToPageHelp() {
			get_current_screen()->add_help_tab(
				array(
					'id'      => 'slp_help_tab',
					'title'   => __( 'SLP Hints', 'store-locator-le' ),
					'content' =>
						'<p>' .
						sprintf(
							__( 'Check the <a href="%s" target="slp">Store Locator Plus documentation</a> online.<br/>', 'store-locator-le' ),
							$this->slplus->support_url
							) .
						sprintf(
							__( 'View the <a href="%s" target="csa">[slplus] shortcode documentation</a>.', 'store-locator-le' ),
							$this->slplus->support_url . '/blog/slplus-shortcode/'
							) .
						'</p>'

				)
			);
		}

		/**
		 * Add the Store Locator panel to the admin sidebar.
		 *
		 * Roles and Caps
		 * manage_slp_admin
		 * manage_slp_user
		 *
		 * WordPress Store Locator Plus Menu Roles & Caps
		 *
		 * Info : manage_slp_admin
		 * Locations: manage_slp_user
		 * Experience: manage_slp_admin
		 * General: manage_slp_admin
		 *
		 */
		function admin_menu() {
			require_once( SLPLUS_PLUGINDIR . 'include/module/admin_tabs/SLP_AdminUI.php' );
			do_action( 'slp_admin_menu_starting' );

			// The main hook for the menu
			//
			$slp_menu_name = apply_filters( 'slp_admin_menu_text' , SLPLUS_NAME );
			$this->slplus->admin_page_prefix = sanitize_title( $slp_menu_name ) . '_page_';
			add_menu_page( $slp_menu_name, $slp_menu_name, 'manage_slp',  SLPLUS_PREFIX, array( $this->slplus->AdminUI, 'renderPage_GeneralSettings' ), $this->menu_icon , 31 );

			// Default menu items
			//
			$menuItems = array();
			$menuItems[] =
				array(
					'label'    => __( 'Locations', 'store-locator-le' ),
					'slug'     => 'slp_manage_locations',
					'class'    => $this->slplus->AdminUI,
					'function' => 'renderPage_Locations'
				);
			$menuItems[] =
				array(
					'label'    => __( 'Experience', 'store-locator-le' ),
					'slug'     => 'slp_experience',
					'class'    => $this->slplus->AdminUI,
					'function' => 'render_experience_tab'
				);
			$menuItems[] =
				array(
					'label'    => __( 'General', 'store-locator-le' ),
					'slug'     => 'slp_general',
					'class'    => $this->slplus->AdminUI,
					'function' => 'renderPage_GeneralSettings'
				);

			// Third party plugin add-ons
			//
			$menuItems = apply_filters( 'slp_menu_items', $menuItems );

			// Put Info At The End
			$menuItems[] =
				array(
					'label'    => __( 'Info', 'store-locator-le' ),
					'slug'     => 'slp_info',
					'class'    => $this->slplus->AdminUI,
					'function' => 'render_info_tab'
				);

			// Tweak any menu item after the array is complete with all add ons contributing
			// Add using 'slp_menu_items', revise-only here.
			//
			$menuItems = apply_filters( 'slp_revise_menu_items' , $menuItems );

			// Attach Menu Items To Sidebar and Top Nav
			//
			foreach ( $menuItems as $menuItem ) {

				// Sidebar connect...
				//
				// Differentiate capability for User Managed Locations
				if ( $menuItem['label'] == __( 'Locations', 'store-locator-le' ) ) {
					$slpCapability = 'manage_slp_user';
				} else {
					$slpCapability = 'manage_slp_admin';
				}

				// Using class names (or objects)
				//
				if ( isset( $menuItem['class'] ) ) {
					add_submenu_page(
						SLPLUS_PREFIX,
						$menuItem['label'],
						$menuItem['label'],
						$slpCapability,
						$menuItem['slug'],
						array( $menuItem['class'], $menuItem['function'] )
					);

					// Full URL or plain function name
					//
				} else {
                    if ( isset( $menuItem['url'] ) && isset( $menuItem['label'] ) ) {
                        add_submenu_page( SLPLUS_PREFIX, $menuItem['label'], $menuItem['label'], $slpCapability, $menuItem['url'] );
                    }
				}
			}

			// Remove the duplicate menu entry
			//
			remove_submenu_page( SLPLUS_PREFIX, SLPLUS_PREFIX );
		}

		/**
		 * Called when the WordPress init action is processed.
		 *
		 * Current user is authenticated by this time.
		 */
		function init() {

			// Fire the SLP init starting trigger
			//
			do_action( 'slp_init_starting', $this );

			// Do not texturize our shortcodes
			//
			add_filter( 'no_texturize_shortcodes', array( 'SLP_UI', 'no_texturize_shortcodes' ) );

			/**
			 * Register the store taxonomy & page type.
			 *
			 * This is used in multiple add-on packs.
			 *
			 */
			if ( ! taxonomy_exists( 'stores' ) ) {
				// Store Page Labels
				//
				$storepage_labels =
					apply_filters(
						'slp_storepage_labels',
						array(
							'name'          => __( 'Store Pages', 'store-locator-le' ),
							'singular_name' => __( 'Store Page', 'store-locator-le' ),
                            'all_items'     => __( 'All Pages' , 'store-locator-le' ),
						)
					);

				$storepage_features =
					apply_filters(
						'slp_storepage_features',
						array(
							'title',
							'editor',
							'author',
							'excerpt',
							'trackback',
							'thumbnail',
							'comments',
							'revisions',
							'custom-fields',
							'page-attributes',
							'post-formats'
						)
					);

				$storepage_attributes = apply_filters( 'slp_storepage_attributes', array(
                    'labels'          => $storepage_labels,
                    'public'          => false,
                    'has_archive'     => true,
                    'description'     => __( 'Store Locator Plus location pages.', 'store-locator-le' ),
                    'menu_position'    => 32,
                    'menu_icon'       => $this->menu_icon,
                    'show_in_menu'    => current_user_can( 'manage_slp_admin' ),
                    'capabilities'     => array(
                        'create_posts' => 'do_not_allow',
                    ),
                    'map_meta_cap'      => true,
                    'supports'        => $storepage_features,
                ) );

				// Register Store Pages Custom Type
				register_post_type( SLPlus::locationPostType, $storepage_attributes );

				register_taxonomy(
					SLPLus::locationTaxonomy,
					SLPLus::locationPostType,
					array(
						'hierarchical' => true,
						'labels'       =>
							array(
								'menu_name' => __( 'Categories', 'store-locator-le' ),
								'name'      => __( 'Store Categories', 'store-locator-le' ),
							),
						'capabilities' =>
							array(
								'manage_terms' => 'manage_slp_admin',
								'edit_terms'   => 'manage_slp_admin',
								'delete_terms' => 'manage_slp_admin',
								'assign_terms' => 'manage_slp_admin',
							)
					)
				);
			}

			// Fire the SLP initialized trigger
			//
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );

			// HOOK: slp_init_complete
			//
			do_action( 'slp_init_complete' );

			//  If the current user can manage_slp (roles & caps), add these admin hooks.
			//
			if ( current_user_can( 'manage_slp' ) ) {
				add_action('admin_menu'		    , array( $this , 'admin_menu'			)		); 	// ADMIN

				if ( ! $this->slplus->site_architect_active ) {
					add_action( 'admin_bar_menu', array( $this, 'add_slp_to_admin_bar' ), 999 );    // ADMIN
				}

                add_action('network_admin_menu'	, array( $this , 'network_admin_menu'	) , 999 ); 	// Multisite Admin Menu

			}
		}

		/**
		 * This is called whenever the WordPress wp_enqueue_scripts action is called.
		 */
		function wp_enqueue_scripts() {

			//------------------------
			// Register our scripts for later enqueue when needed
			//
			if ( ! $this->slplus->is_CheckTrue( $this->slplus->options_nojs['no_google_js'] ) ) {
				$this->slplus->enqueue_google_maps_script();
			}

			$sslURL =
				( is_ssl() ?
					preg_replace( '/http:/', 'https:', SLPLUS_PLUGINURL ) :
					SLPLUS_PLUGINURL
				);


			// Force load?  Enqueue and localize.
			//
			if ( $this->slplus->javascript_is_forced ) {
				wp_enqueue_script( 'slp_core', $sslURL . '/js/slp_core.js', array( 'jquery' ), SLPLUS_VERSION, ! $this->slplus->javascript_is_forced );
				wp_enqueue_script( 'csl_script', $sslURL . '/js/wpslp.js', array( 'slp_core' ), SLPLUS_VERSION, ! $this->slplus->javascript_is_forced );
				$this->slplus->UI->localize_script();
				$this->slplus->UI->setup_stylesheet_for_slplus();

			// No force load?  Register only.
			// Localize happens when rendering a shortcode.
			//
			} else {
				wp_register_script( 'slp_core', $sslURL . '/js/slp_core.js', array( 'jquery' ), SLPLUS_VERSION, ! $this->slplus->javascript_is_forced );
				wp_register_script( 'csl_script', $sslURL . '/js/wpslp.js', array( 'jquery' ), SLPLUS_VERSION, ! $this->slplus->javascript_is_forced );
			}
		}


		/**
		 * This is called whenever the WordPress shutdown action is called.
		 */
		function wp_footer() {
			SLP_Actions::ManageTheScripts();
		}


		/**
		 * Called when the <head> tags are rendered.
		 */
		function wp_head() {
			if ( ! isset( $this->slplus ) ) {
				return;
			}


			echo '<!-- SLP Custom CSS -->' . "\n" . '<style type="text/css">' . "\n" .

			     // Map
			     "div#map.slp_map {\n" .
			     "width:{$this->slplus->options_nojs['map_width']}{$this->slplus->options_nojs['map_width_units']};\n" .
			     "height:{$this->slplus->options_nojs['map_height']}{$this->slplus->options_nojs['map_height_units']};\n" .
			     "}\n" .

			     // Tagline
			     "div#slp_tagline {\n" .
			     "width:{$this->slplus->options_nojs['map_width']}{$this->slplus->options_nojs['map_width_units']};\n" .
			     "}\n" .

			     // FILTER: slp_ui_headers
			     //
			     apply_filters( 'slp_ui_headers', '' ) .

			     '</style>' . "\n\n";
		}

        /**
         * Network menu admin.
         */
        public function network_admin_menu() {
	        require_once( SLPLUS_PLUGINDIR . 'include/module/admin_tabs/SLP_AdminUI.php' );
            add_menu_page(
	            SLPLUS_NAME,
	            SLPLUS_NAME,
                'manage_network_options',
                'slp-network-admin',
                array( $this->slplus->AdminUI, 'renderPage_GeneralSettings' ),
                $this->menu_icon
                );
        }

		/**
		 * This is called whenever the WordPress shutdown action is called.
		 */
		function shutdown() {
			SLP_Actions::ManageTheScripts();
		}

		/**
		 * Unload The SLP Scripts If No Shortcode
		 */
		function ManageTheScripts() {
			if ( ! defined( 'SLPLUS_SCRIPTS_MANAGED' ) || ! SLPLUS_SCRIPTS_MANAGED ) {

				// If no shortcode rendered, remove scripts
				//
				if ( ! defined( 'SLPLUS_SHORTCODE_RENDERED' ) || ! SLPLUS_SHORTCODE_RENDERED ) {
					wp_dequeue_script( 'google_maps' );
					wp_deregister_script( 'google_maps' );
					wp_dequeue_script( 'slp_core' );
					wp_deregister_script( 'slp_core' );
					wp_dequeue_script( 'csl_script' );
					wp_deregister_script( 'csl_script' );
				}
				define( 'SLPLUS_SCRIPTS_MANAGED', true );
			}
		}
	}

	/**
	 * Make use - creates as a singleton attached to slplus->object['AdminUI']
	 *
	 * @var SLPlus $slplus
	 */
	global $slplus;
	if ( is_a( $slplus, 'SLPlus' ) ) {
		$slplus->add_object( new SLP_Actions() );
	}
}

// These dogs are loaded up way before this class is instantiated.
//
add_action("load-post",array('SLP_Actions','init'));
add_action("load-post-new",array('SLP_Actions','init'));

