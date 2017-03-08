<?php
if ( ! class_exists('SLP_UI') ) {
	/**
	 * Store Locator Plus basic user interface.
	 *
	 * @property        string             $name                       Name of this module.
	 * @property        string[]           $options                    Options for the UI class, needed of registered modules.
	 * @property        string             $radius_default             Radius Default
	 * @property        string[]           $radius_selector_radii      A string array of each radius in the slplus->options['radii'] setting.
	 */
	class SLP_UI extends SLPlus_BaseClass_Object {
		public  $radius_default;
		public  $radius_selector_radii;

		/**
		 * Things we do at the start.
		 */
		protected function initialize() {
			add_shortcode( 'STORE-LOCATOR'  , array( $this , 'render_shortcode' ) );
			add_shortcode( 'SLPLUS'         , array( $this , 'render_shortcode' ) ); // yes, add_shortcode() is case sensitive
			add_shortcode( 'slplus'         , array( $this , 'render_shortcode' ) );
		}

		/**
		 * Create a search form input div.
		 *
		 * @param null|string $fldID
		 * @param string      $label
		 * @param string      $placeholder
		 * @param bool        $hidden
		 * @param null|string $divID
		 * @param string      $default
		 *
		 * @return string|void
		 */
		function createstring_InputDiv( $fldID = null, $label = '', $placeholder = '', $hidden = false, $divID = null, $default = '' ) {
			if ( $fldID === null ) {
				return;
			}
			if ( $divID === null ) {
				$divID = $fldID;
			}

			// Escape output for special char friendliness
			//
			if ( $default !== '' ) {
				$default = esc_html( $default );
			}
			if ( $placeholder !== '' ) {
				$placeholder = "placeholder='" . esc_html( $placeholder ) . "'";
			}

			$label_class = empty( $label ) ? 'empty' : 'text length_' . strlen( $label );
			$input_class = "label_{$label_class}";
			$input_type  = $hidden ? 'hidden' : 'text';

			$content =
				( $hidden ? '' : "<div id='$divID' class='search_item'>" ) .
				( ( $hidden || ( $label === '' ) ) ? '' : "<label for='{$fldID}' class='{$label_class}'>{$label}</label>" ) .
				"<input class='{$input_class}' type='{$input_type}' id='{$fldID}' name='{$fldID}' {$placeholder} size='50' value='$default' />" .
				( $hidden ? '' : "</div>" );

			return $content;
		}

		/**
		 * Output the search form based on the search results layout.
		 */
		function createstring_SearchForm() {
			if ( $this->slplus->is_CheckTrue( $this->slplus->options['hide_search_form'] ) ) {
				return '';
			}

			// Register our custom shortcodes
			// SHORTCODE: slp_search_element
			//
			add_shortcode( 'slp_search_element', array( $this, 'create_SearchElement' ) );

			/**
			 * FILTER: slp_searchlayout
			 *
			 * @params string search layout
			 * @params string modified search layout
			 */
			$HTML = '<div class="slp_search_container">' . do_shortcode( apply_filters( 'slp_searchlayout', $this->slplus->SmartOptions->searchlayout->value ) ) . '</div>';

			remove_shortcode( 'slp_search_element' );

			// Make sure the search form is wrapped in the form action to make it work with the JS submit.
			return
				'<form id="searchForm" class="slp_search_form" action="" ' .
				"onsubmit='cslmap.searchLocations(); return false;'  >" .
				$this->rawDeal( $HTML ) .
				'</form>';
		}

		/**
		 * Placeholder for the Tagalong legend placement in SLP Layout controls.
		 *
		 * Does nothing but stop the [tagalong ...] shortcode text from appearing in output when Tagalong is not active.
		 *
		 * @param mixed[] shortcode attributes array
		 * @param string  $content
		 *
		 * @return string blank text
		 */
		public function createstring_TagalongPlaceholder( $attributes, $content = '' ) {
			return '';
		}

		/**
		 * Return a plugin option value.
		 *
		 * [slp_option name="<option_name>"] - render value of option_name setting checking SmartOptions, then nojs, then js options.
		 *
		 * [slp_option name="<option_name>" ifset] - only render the span if the option is not empty
		 *
		 * [slp_option name="<option_name>" ifset="div"] - only render span if option is not empty and also wrap that span in a div
		 *
		 * @param      $attributes
		 * @param null $content
		 *
		 * @return string
		 */
		function create_string_slp_option_value( $attributes, $content = null ) {
			$attributes = apply_filters( 'shortcode_slp_option', $attributes );

			$only_if_set  = false;
			$option_value = '';
			$div          = '';

			foreach ( $attributes as $att => $value ) {
				if ( (int) $att !== $att ) {
					switch ( $att ) {
						case 'ifset':
							$only_if_set = true;
							$div         = '<div class="option_%s">%s</div>';
							break;

						case 'js':
						case 'nojs':
						case 'name':
							if ( $this->slplus->SmartOptions->exists( $value ) ) {
								$option_value = $this->slplus->SmartOptions->{$value}->value;
								$type         = 'smart';
							} elseif ( array_key_exists( $value, $this->slplus->options_nojs ) ) {
								$option_value = isset( $this->slplus->options_nojs[ $value ] ) ? $this->slplus->options_nojs[ $value ] : '';
								$type         = 'nojs';
							} else {
								$option_value = isset( $this->slplus->options[ $value ] ) ? $this->slplus->options[ $value ] : '';
								$type         = 'js';
							}
							$key = $value;
							break;

						case 'key' :
							$key = $attributes['key'];
							break;

						case 'type':
							$type = $attributes['type'];
							break;

						case 'value':
							$option_value = $attributes['value'];
							break;
					}
				} else {
					switch ( strtolower( $value ) ) {
						case 'ifset':
							$only_if_set = true;
							break;
					}
				}
			}

			if ( ! empty( $option_value ) || ! $only_if_set ) {
				$output = sprintf( '<span id="slp_option_%s_%s">%s</span>', $type, $key, $option_value );
				if ( ! empty( $div ) ) {
					$output = sprintf( $div, $key, $output );
				}
			} else {
				$output = '';
			}

			return $output;
		}

		/**
		 * @param string $HTML current map HTML default is blank
		 *
		 * @return string modified map HTML
		 */
		function filter_SetDefaultMapLayout( $HTML ) {
			if ( ! empty( $HTML ) ) {
				return $HTML;
			}

			return $this->slplus->SmartOptions->maplayout->value;
		}

		/**
		 * Create the default search address div.
		 *
		 * FILTER: slp_search_default_address
		 */
		function createstring_DefaultSearchDiv_Address( $placeholder = '' ) {
			return $this->slplus->UI->createstring_InputDiv(
				'addressInput',
				$this->slplus->WPML->get_text( 'sl_search_label', $this->slplus->options_nojs['label_search'] ),
				$placeholder,
				false,
				'addy_in_address',
				apply_filters( 'slp_search_default_address', '' )
			);
		}

		/**
		 * Create the default search radius div.
		 */
		public function create_string_radius_selector_div() {
			$label       = $this->slplus->WPML->get_text( 'sl_radius_label', $this->slplus->options_nojs['label_radius'] );
			$label_class = empty( $label ) ? 'empty' : 'text length_' . strlen( $label );
			$HTML        =
				"<div id='addy_in_radius'>" .
				"<label for='radiusSelect' class='{$label_class}'>{$label}</label>" .
				$this->create_string_radius_selector() .
				"</div>";

			/**
			 * FILTER: slp_change_ui_radius_selector
			 *
			 * Augment the HTML for the radius selector.
			 *
			 * @params string $HTML the current HTML
			 *
			 * @return string       the modified HTML
			 */
			return apply_filters( 'slp_change_ui_radius_selector', $HTML );
		}

		/**
		 * Build the radius selector string.
		 *
		 * @return string
		 */
		public function create_string_radius_selector() {
			return "<select id='radiusSelect'>" . $this->create_string_radius_selector_options() . '</select>';
		}

		/**
		 * Create the options HTML for the radius select string.
		 * @return string
		 */
		public function create_string_radius_selector_options() {
			$this->radius_default = $this->find_default_radius();
			$this->set_radius_selector_radii();
			$options         = array();
			$distance_suffix = ( $this->slplus->SmartOptions->distance_unit->value === 'km' ) ? __( 'km', 'store-locator-le' ) : __( 'miles', 'store-locator-le' );
			foreach ( $this->radius_selector_radii as $radius ) {
				$selected  = ( $radius === $this->radius_default ) ? " selected='selected' " : '';
				$options[] = "<option value='$radius' $selected>{$radius} {$distance_suffix}</option>";
			}

			/**
			 * FILTER: slp_radius_selections
			 *
			 * Allow add ons to change the radius selections.
			 */
			$options = apply_filters( 'slp_radius_selections', $options, $this );

			return join( $options );
		}

		/**
		 * Find the default radius in the radii string.
		 *
		 * It is wrappped with () or the first/only entry in a comma-separated list.
		 *
		 * @return string
		 */
		public function find_default_radius() {
			preg_match( '/\((.*?)\)/', $this->slplus->options['radii'], $selectedRadius );
			if ( isset( $selectedRadius[1] ) ) {
				return preg_replace( '/[^0-9\.]/', '', $selectedRadius[1] );
			}
			$this->set_radius_selector_radii();

			return $this->radius_selector_radii[0];
		}

		/**
		 * Set the radius_selector_radii array.
		 */
		private function set_radius_selector_radii() {
			if ( ! isset( $this->radius_selector_radii ) ) {
				$radii                       = explode( ",", preg_replace( '/[^0-9\.\,]/', '', $this->slplus->options['radii'] ) );
				$this->radius_selector_radii = is_array( $radii ) ? $radii : (array) $radii;
			}
		}

		/**
		 * Create the default search submit div.
		 *
		 * If we are not hiding the submit button.
		 */
		private function create_DefaultSearchDiv_Submit() {
			$button_style = 'type="submit" class="slp_ui_button"';

			return
				"<div id='radius_in_submit'>" .
				"<input $button_style " .
				"value='" . $this->get_find_button_text() . "' " .
				"id='addressSubmit'/>" .
				"</div>";
		}

		/**
		 * Retrive the proper find button default text.
		 */
		private function get_find_button_text() {
			require_once( SLPLUS_PLUGINDIR . 'include/module/i18n/SLP_Text.php' );
			$find_label = $this->slplus->Text->get_text_string( array(
				'option_default',
				'label_for_find_button'
			) );

			/**
			 * FILTER: slp_find_button_text
			 *
			 * @param   string $find_label The current find button text.
			 *
			 * @return  string                      The revised find button text.
			 */
			return apply_filters( 'slp_find_button_text', $find_label );
		}

		/**
		 * Create the HTML for the map.
		 */
		function create_Map() {
			/**
			 * FILTER: slp_map_html
			 */
			add_filter( 'slp_map_html', array( $this, 'filter_SetDefaultMapLayout' ), 10 );
			add_shortcode( 'slp_mapcontent', array( $this, 'create_MapContent' ) );
			add_shortcode( 'slp_maptagline', array( $this, 'create_MapTagline' ) );
			$mapContent = do_shortcode( apply_filters( 'slp_map_html', '' ) );
			remove_shortcode( 'slp_mapcontent' );
			remove_shortcode( 'slp_maptagline' );

			// Remove the credits
			//
			if ( $this->slplus->SmartOptions->remove_credits->is_true ) {
				$mapContent = preg_replace( '/<div id="slp_tagline"(.*?)<\/div>/', '', $mapContent );
			}

			return $this->rawDeal( $mapContent );
		}

		/**
		 * Replace [slp_mapcontent]
		 */
		function create_MapContent() {
			// FILTER: slp_googlemapdiv
			return apply_filters( 'slp_googlemapdiv', '<div id="map" class="slp_map"></div>' );
		}

		/**
		 * Create the map tagline for SLP link
		 *
		 */
		function create_MapTagline() {
			return '<div id="slp_tagline" class="store_locator_plus tagline">' .
			       sprintf(
				       __( 'search provided by %s', 'store-locator-le' ),
				       "<a href='{$this->slplus->slp_store_url}' target='_blank'>".SLPLUS_NAME.'</a>'
			       ) .
			       '</div>';
		}

		/**
		 * Create the HTML for the search results.
		 */
		function create_Results() {
			return
				$this->rawDeal(
					'<div id="map_sidebar" class="slp_results_container">' .
					'<div class="text_below_map">' .
					$this->slplus->SmartOptions->instructions->value .
					'</div>' .
					'</div>'
				);
		}

		/**
		 * Process shortcodes for search form.
		 */
		public function create_SearchElement( $attributes, $content = null ) {

			/**
			 * Filter to man-handle the attributes before processed.
			 *
			 * Add ons often use this to return a value 'hard_coded_value' => 'xyz' to output a specific string.
			 *
			 * @filter      slp_manage_locations_actionbuttons
			 *
			 * @params      string  current HTML
			 * @params      array   current location data
			 */
			$attributes = apply_filters( 'shortcode_slp_searchelement', $attributes );

			foreach ( $attributes as $name => $value ) {

				switch ( $name ) {

					// Hard coded entries take precedence.
					//
					case 'hard_coded_value':
						if ( $name == 0 && $value === 'add_on' ) {
							return '';
						}

						return $value;
						break;

					case 'dropdown_with_label':
						switch ( $value ) {
							case 'radius':
								return $this->create_string_radius_selector_div();
								break;

							default:
								break;
						}
						break;

					case 'input_with_label':
						switch ( $value ) {
							case 'address':
								return $this->createstring_DefaultSearchDiv_Address();
								break;

							default:
								break;
						}
						break;

					case 'button':
						switch ( $value ) {
							case 'submit':
								return $this->create_DefaultSearchDiv_Submit();
								break;

							default:
								break;
						}
						break;

					default:
						break;
				}
			}

			return '';
		}

		/**
		 * Do not texturize our shortcodes.
		 *
		 * @param array $shortcodes
		 *
		 * @return array
		 */
		static function no_texturize_shortcodes( $shortcodes ) {
			return array_merge( $shortcodes, array( 'STORE-LOCATOR', 'SLPLUS', 'slplus' ) );
		}

		/**
		 * Process the store locator plus shortcode.
		 *
		 * Variables this function uses and passes to the template
		 * we need a better way to pass vars to the template parser so we don't
		 * carry around the weight of these global definitions.
		 * the other option is to unset($GLOBAL['<varname>']) at then end of this
		 * function call.
		 *
		 * We now use $this->plugin->data to hold attribute data.
		 *
		 * @link https://docs.google.com/drawings/d/10HCyJ8vSx8ew59TbP3zrTcv2fVZcedG-eHzY78xyWSA/edit?usp=sharing Flowchart for render_shortcode
		 *
		 * @param type $attributes
		 * @param type $content
		 *
		 * @return string HTML the shortcode will render
		 */
		function render_shortcode( $attributes, $content = null ) {
			if ( ! is_object( $this->slplus ) ) {
				return sprintf( __( '%s is not ready', 'store-locator-le' ), __( 'Store Locator Plus', 'store-locator-le' ) );
			}

			// Force some plugin data properties
			//
			$this->slplus->data['radius_options'] = ( isset( $this->slplus->data['radius_options'] ) ? $this->slplus->data['radius_options'] : '' );

			// Setup the base plugin allowed attributes
			//
			add_filter( 'slp_shortcode_atts', array( $this, 'filter_SetAllowedShortcodes' ), 80, 3 );

			/**
			 * FILTER: slp_shortcode_atts
			 * Apply the filter of allowed attributes.
			 *
			 * @param array list of allowed attributes and their defaults
			 * @param array the attribute key=>value pairs from the shortcode being processed [slplus att='val']
			 * @param array content between the start and end shortcode block, always empty for slplus.
			 */
			$attributes =
				shortcode_atts(
					apply_filters( 'slp_shortcode_atts', array(), $attributes, $content ),
					$attributes,
					'slplus'
				);
			do_action( 'slp_before_render_shortcode', $attributes );

			// Set plugin data and options to include the attributes.
			// TODO: data needs to go away and become part of options.  Will impact EM/ES/ELM/PRO/SME/TAG/W
			//
			$this->slplus->data    =
				array_merge(
					$this->slplus->data,
					(array) $attributes
				);
			$this->slplus->options =
				array_merge(
					$this->slplus->options,
					(array) $attributes
				);

			// If Force Load JavaScript is NOT checked...
			// Localize the CSL Script - modifies the CSLScript with any shortcode attributes.
			// Setup the style sheets
			//
			if ( ! $this->slplus->javascript_is_forced ) {
				$this->localize_script();
				wp_enqueue_script( 'slp_core' );
				wp_enqueue_script( 'csl_script' );
				$this->setup_stylesheet_for_slplus( $attributes['theme'] );
			}

			// Shortcodes for SLPLUS layouts
			//
			add_shortcode( 'slp_option', array( $this, 'create_string_slp_option_value' ) );
			add_shortcode( 'slp_addon', array( $this, 'remove_slp_addon_shortcodes' ) );

			add_shortcode( 'slp_search', array( $this, 'createstring_SearchForm' ) );
			add_shortcode( 'slp_map', array( $this, 'create_Map' ) );
			add_shortcode( 'slp_results', array( $this, 'create_Results' ) );

			// Placeholders
			// TODO: Rip this out in SLP 5.0 when we drop legacy support
			//
			if ( ! $this->slplus->is_AddOnActive( 'slp-tagalong' ) ) {
				add_shortcode( 'tagalong', array( $this, 'createstring_TagalongPlaceholder' ) );
			}

			// Set our flag for later processing
			// of JavaScript files
			//
			if ( ! defined( 'SLPLUS_SHORTCODE_RENDERED' ) ) {
				define( 'SLPLUS_SHORTCODE_RENDERED', true );
			}
			$this->slplus->shortcode_was_rendered = true;

			$current_theme = wp_get_theme();

			/**
			 * FILTER: slp_layout
			 *
			 * @params string overall layout
			 *
			 * @return string modified layout
			 */
			$HTML = '<div class="store_locator_plus ' . sanitize_title( $current_theme->template ) . '">' .
			        do_shortcode( apply_filters( 'slp_layout', $this->slplus->SmartOptions->layout->value ) ) .
			        '</div>';

			remove_shortcode( 'slp_option' );
			remove_shortcode( 'slp_addon' );
			remove_shortcode( 'slp_search' );
			remove_shortcode( 'slp_map' );
			remove_shortcode( 'slp_results' );
			remove_shortcode( 'tagalong' );

			do_action( 'slp_after_render_shortcode', $attributes );

			return $HTML;
		}

		/**
		 * Set the allowed shortcode attributes
		 *
		 * @param mixed[] $atts
		 * @param mixed[] $attributes
		 * @param string  $content
		 *
		 * @return mixed[] $atts modified attributes array
		 */
		function filter_SetAllowedShortcodes( $atts, $attributes, $content ) {
			$ret_atts = array_merge(
				array(
					'initial_radius'   => $this->slplus->SmartOptions->initial_radius->value,
					'theme'            => null,
					'id'               => null,
					'hide_search_form' => null,
				),
				$atts
			);
			if ( isset( $attributes['id'] ) ) {
				$locData =
					$this->slplus->database->get_Record(
						array( 'selectall', 'whereslid' ),
						$attributes['id']
					);
				if ( is_array( $locData ) ) {
					$ret_atts['id_addr'] = $locData['sl_latitude'] . ', ' . $locData['sl_longitude'];
				}
			}

			return $ret_atts;
		}

		/**
		 * Localize the CSL Script
		 */
		public function localize_script() {
			// Handle any IconAttributes optionally set using the shortcode in combination with the Pro Pack
			$this->handleIconAttributes( 'map_home_icon', 'homeicon' );
			$this->handleIconAttributes( 'map_end_icon', 'endicon' );

			$slplus_home_icon_file           = str_replace( SLPLUS_ICONURL, SLPLUS_ICONDIR, $this->slplus->SmartOptions->map_home_icon );
			$slplus_end_icon_file            = str_replace( SLPLUS_ICONURL, SLPLUS_ICONDIR, $this->slplus->SmartOptions->map_end_icon );
			$this->slplus->data['home_size'] = ( function_exists( 'getimagesize' ) && file_exists( $slplus_home_icon_file ) ) ?
				getimagesize( $slplus_home_icon_file ) :
				array( 0 => 20, 1 => 34 );
			$this->slplus->data['end_size']  = ( function_exists( 'getimagesize' ) && file_exists( $slplus_end_icon_file ) ) ?
				getimagesize( $slplus_end_icon_file ) :
				array( 0 => 20, 1 => 34 );

			add_shortcode( 'slp_location', array( $this, 'process_slp_location_Shortcode' ) );

			// Set starting map center
			//
			$this->slplus->SmartOptions->map_center->value = $this->set_MapCenter();

			// Lets get some variables into our script.
			// "Higher Level" JS Options are those noted below.
			//
			// TODO: What are these?  Can they move?
			//
			$scriptData = array(
				'map_home_sizew' => $this->slplus->data['home_size'][0],
				'map_home_sizeh' => $this->slplus->data['home_size'][1],
				'map_end_sizew'  => $this->slplus->data['end_size'][0],
				'map_end_sizeh'  => $this->slplus->data['end_size'][1],
			);

			$this->slplus->options['results_layout'] = $this->set_ResultsLayout( false, true );

			/**
			 * FILTER: slp_js_options
			 *
			 * Extend the options available in the slp.js file in the options attribute.
			 *
			 * @param   array $options The current settings (options) saved by the user for the plugin.
			 *
			 * @return  array           A modified or extended options array.
			 */
			add_filter( 'slp_js_options', array( $this, 'add_to_js_options' ) );
			$scriptData['options'] = apply_filters( 'slp_js_options', $this->slplus->options );

			remove_shortcode( 'slp_location' );

			// Set the environment data
			//
			$environment['addons']      = $this->slplus->AddOns->get_versions();
			$environment['slp_version'] = SLPLUS_VERSION;
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			$environment['network_active'] = is_plugin_active_for_network( $this->slplus->slug ) ? 'yes' : 'no';
			if ( is_multisite() ) {
				$environment['network_sites'] = get_blog_count();
			} else {
				$environment['network_sites'] = '1';
			}
			$environment['php_version'] = phpversion();
			$environment['wp_debug'] = defined( 'WP_DEBUG' ) ? WP_DEBUG : false;

			// Set jQuery version in use on UI
			//
			global $wp_scripts;
			$bad_version = __( 'Your site is running an non-standard version of jQuery.', 'store-locator-le' );
			if ( ! isset( $wp_scripts->registered['jquery'] ) ) {
				$environment['jquery_version']  = __( 'jQuery deregistered', 'store-locator-le' );
				$environment['text_bad_jquery'] = $bad_version;
			} else {
				if ( isset( $wp_scripts->registered['jquery']->ver ) ) {
					$environment['jquery_version'] = $wp_scripts->registered['jquery']->ver;
					if ( version_compare( $wp_scripts->registered['jquery']->ver, '1.12.3', '<' ) ) {
						$environment['text_bad_jquery'] = $bad_version;
					}
				} else {
					$environment['jquery_version']  = __( 'Unknown', 'store-locator-le' );
					$environment['text_bad_jquery'] = $bad_version;
				}
			}
			if ( $this->slplus->options_nojs['ui_jquery_version'] !== $environment['jquery_version'] ) {
				$this->slplus->options_nojs['ui_jquery_version'] = $environment['jquery_version'];
				$this->slplus->WPOption_Manager->update_wp_option( 'nojs' );
			}

			/**
			 * FILTER: slp_js_environment
			 */
			$scriptData['environment'] = apply_filters( 'slp_js_environment', $environment );

			// AJAX and URL Data
			//
			$scriptData['plugin_url'] = SLPLUS_PLUGINURL;
			$scriptData['ajaxurl']    = admin_url( 'admin-ajax.php' );
			$scriptData['nonce']      = wp_create_nonce( 'em' );

			wp_localize_script( 'slp_core', 'slplus', $scriptData );

		}

		/**
		 * Show force load option in JS options.
		 */
		function add_to_js_options( $options ) {

			$additional_options =
				array(
					'force_load_js' => $this->slplus->options_nojs['force_load_js'],
					'map_region'    => $this->slplus->Country_Manager->countries[ $this->slplus->options_nojs['default_country'] ]->cctld
				);

			// ONLY do this if EM is enabled (Experience will use the 'map_options' pub/sub in slp.js and userinterface.js
			// TODO: SLP 4.6 Drop this killing support for EM legacy.
			//
			if ( $this->slplus->AddOns->get(  'slp-enhanced-map'  , 'active' ) ) {
				$additional_options['map_options_scrollwheel']    = ( $this->slplus->WPOption_Manager->get_wp_option( SLPLUS_PREFIX . '_disable_scrollwheel' ) == 0 );
				$additional_options['map_options_scaleControl']   = ( $this->slplus->WPOption_Manager->get_wp_option( SLPLUS_PREFIX . '_disable_scalecontrol' ) == 0 );
				$additional_options['map_options_mapTypeControl'] = ( $this->slplus->WPOption_Manager->get_wp_option( SLPLUS_PREFIX . '_disable_maptypecontrol' ) == 0 );
			}

			return array_merge( $options, $additional_options );
		}

		/**
		 * Assign the plugin specific UI stylesheet.
		 *
		 * For this to work with shortcode testing you MUST call it via the WordPress wp_footer action hook.
		 *
		 * @param string $themeFile if set use this theme v. the database setting
		 */
		private function assign_user_stylesheet( $themeFile ) {

			// append .css if left off
			if ( ( strlen( $themeFile ) < 4 ) || substr_compare( $themeFile, '.css', - strlen( '.css' ), strlen( '.css' ) ) != 0 ) {
				$themeFile .= '.css';
			}

			// If the theme file is readable (after forcing default if necessary) queue it up
			//
			if ( is_readable( SLPLUS_PLUGINDIR . 'css/' . $themeFile ) ) {
				wp_deregister_style( SLPLUS_PREFIX . '_user_header_css' );
				wp_dequeue_style( SLPLUS_PREFIX . '_user_header_css' );
				wp_enqueue_style( SLPLUS_PREFIX . '_user_header_css', SLPLUS_PLUGINURL . '/css/' . $themeFile );
			}

			// Add New Style Gallery Inline Style
			//
			if ( ! empty( $this->slplus->SmartOptions->active_style_css->value ) && ( $themeFile === 'a_gallery_style.css' ) ) {
				wp_add_inline_style( SLPLUS_PREFIX . '_user_header_css', $this->slplus->SmartOptions->active_style_css->value );
			}
		}

		/**
		 * Handle any IconAttributes optionally set using the shortcode in combination with the Pro Pack.
		 */
		function handleIconAttributes( $data_element, $attribute_element ) {

			// Check Settings for $attribute_element
			//
			if ( isset( $this->slplus->data[ $attribute_element ] ) && ! empty( $this->slplus->data[ $attribute_element ] ) ) {

				// Start with attribute_element value
				$icon_url = $this->slplus->data[ $attribute_element ];

				// Prepends value with SLPLUS_ICONURL when it is not a url (could use url_test() )
				//  Try WordPress is_valid_url() from the common.php library.
				//
				if ( ! $this->slplus->is_valid_url( $icon_url ) ) {
					$icon_url = SLPLUS_ICONURL . $icon_url;

					// If file doesn't exist, try to make relative url into absolute url
					$icon_file = str_replace( SLPLUS_ICONURL, SLPLUS_ICONDIR, $icon_url );
					if ( ! file_exists( $icon_file ) ) {
						$icon_url = get_site_url() . $this->slplus->data[ $attribute_element ];
					}
				}

				// Store value found in data_element
				$this->slplus->data[ $data_element ] = $icon_url;
			}

		}

		/**
		 * Set the starting point for the center of the map.
		 *
		 * Uses country by default.
		 */
		function set_MapCenter() {
			require_once( SLPLUS_PLUGINDIR . 'include/module/i18n/SLP_Country_Manager.php' );

			// Map Settings "Center Map At"
			//
			$customAddress = $this->slplus->SmartOptions->map_center->value;
			if ( ( preg_replace( '/\W/', '', $customAddress ) != '' ) ) {
				$customAddress = str_replace( array( "\r\n", "\n", "\r" ), ', ', esc_attr( $customAddress ) );
			} else {
				$customAddress = esc_attr( $this->slplus->Country_Manager->countries[ $this->slplus->options_nojs['default_country'] ] );
			}

			return apply_filters( 'slp_map_center', $customAddress );
		}

		/**
		 * Set the results layout string.
		 *
		 * @param bool $add_shortcode set to false if doing your own slp_location shortcode handling.
		 * @param bool $raw           set to true to skip the stripslashes and esc_textarea processing.
		 *
		 * @return string $html
		 */
		public function set_ResultsLayout( $add_shortcode = true, $raw = false ) {

			if ( $add_shortcode ) {
				add_shortcode( 'slp_location', array( $this, 'process_slp_location_Shortcode' ) );
			}

			/**
			 * FILTER: slp_javascript_results_string
			 *
			 * Sets or modifies the default results layout string.
			 *
			 * @param   string $default_layout The default layout from the resultslayout SmartOption
			 *
			 * @return  string                      The modified default layout.
			 */
			$results_layout = apply_filters( 'slp_javascript_results_string', $this->slplus->SmartOptions->resultslayout->value );

			if ( ! $raw ) {
				$results_layout =
					do_shortcode(
						stripslashes(
							esc_textarea(
								$results_layout
							)
						)
					);
			}

			if ( $add_shortcode ) {
				remove_shortcode( 'slp_location' );
			}

			$results_layout = do_shortcode( $results_layout );

			return $results_layout;
		}

		/**
		 * Remove the [slp_addon ...] shortcodes from results layout.
		 *
		 * @return string
		 */
		public function remove_slp_addon_shortcodes() {
			return '';
		}

		/**
		 * Setup the CSS for the product pages.
		 *
		 * @param string $theme
		 */
		public function setup_stylesheet_for_slplus( $theme = '' ) {
			if ( empty( $theme ) ) {
				$theme = ( ! empty ( $this->slplus->SmartOptions->theme->value ) ) ? $this->slplus->SmartOptions->theme->value : 'a-style-gallery.css';
			}
			$this->assign_user_stylesheet( $theme );
		}

		/**
		 * Process the [slp_location] shortcode in a results string.
		 *
		 * Attributes for [slp_location] include:
		 *     <field name> where field name is a locations table field.
		 *
		 * Usage: [slp_location country]
		 *
		 * @param mixed[] $atts
		 *
		 * @return mixed[]
		 */
		function process_slp_location_Shortcode( $atts ) {

			$shortcode_label = 'slp_location';
			$fldName         = '';
			$attributes      = '';

			// Process the keys
			//
			if ( is_array( $atts ) ) {
				foreach ( $atts as $key => $value ) {
					$key   = strtolower( $key );
					$value = preg_replace( '/[\W^[.]]/', '', htmlspecialchars_decode( $value ) );
					switch ( $key ) {

						// First attribute : field name placeholders
						//
						case '0':
							$fldName = strtolower( $value );

							switch ( $fldName ):

								// slp_location with more attributes
								//
								case 'web_link':
								case 'pro_tags':
									$attributes .= ' raw';
									break;

								case 'distance_1'     :
									$fldName = 'distance';
									$attributes .= ' format="decimal1"';
									break;

								case 'hours':
									$attributes = ' format text';
									break;

								// convert to slp_option
								//
								case 'map_domain'     :
								case 'distance_unit'  :
									$shortcode_label = 'slp_option';
									break;

								case 'directions_text':
									$shortcode_label = 'slp_option';
									$fldName         = 'label_directions';
									break;

								// Leave untouched
								//
								default:
									break;

							endswitch;
							break;

						default:
							$attributes .=
								' ' .
								(
								is_numeric( $key ) ?
									$value :
									$key . '="' . $value . '"'
								);
							break;
					}
				}
			}

			return "[{$shortcode_label} {$fldName}{$attributes}]";
		}

		/**
		 * Strip all \r\n from the template to try to "unbreak" Theme Forest themes.
		 * They have a known bug that MANY Theme Forest authors have introduced which will change this:
		 * <table
		 *    style="display:none"
		 *    >
		 *
		 * To this:
		 * <table<br/>
		 *    style="display:none"<br/>
		 *    >
		 *
		 * @param string $inStr
		 *
		 * @return string
		 */
		function rawDeal( $inStr ) {
			return str_replace( array( "\r", "\n" ), '', $inStr );
		}
	}

	/**
	 * Make use - creates as a singleton attached to slplus->object['AdminUI']
	 *
	 * @var SLPlus $slplus
	 */
	global $slplus;
	if ( is_a( $slplus, 'SLPlus' ) ) {
		$slplus->add_object( new SLP_UI() );
	}

}
