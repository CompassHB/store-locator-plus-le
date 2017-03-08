<?php
if ( ! class_exists( 'SLP_SmartOptions' ) ) {

	/**
	 * Class SLP_SmartOptions
	 *
	 * The options management for the base plugin (replaces SLPlus->options / SLPlus->options_nojs)
	 *
	 * @package   StoreLocatorPlus\Options
	 * @author    Lance Cleveland <lance@charlestonsw.com>
	 * @copyright 2016 Charleston Software Associates, LLC
	 *
	 * @since     4.6
	 *
	 * @property SLP_Option      $admin_notice_dismissed                 ;
	 * @property SLP_Option      $distance_unit
	 * @property SLP_Option      $google_server_key
	 * @property SLP_Option      $initial_radius
	 * @property SLP_Option      $invalid_query_message
	 * @property SLP_Option      $instructions
	 * @property SLP_Option      $label_directions
	 * @property SLP_Option      $label_email
	 * @property SLP_Option      $label_fax
	 * @property SLP_Option      $label_hours
	 * @property SLP_Option      $label_image
	 * @property SLP_Option      $label_phone
	 * @property SLP_Option      $label_radius
	 * @property SLP_Option      $label_search
	 * @property SLP_Option      $label_website
	 * @property SLP_Option      $log_schedule_messages
	 * @property SLP_Option      $map_center
	 * @property SLP_Option      $map_center_lat
	 * @property SLP_Option      $map_center_lng
	 * @property SLP_Option      $map_end_icon
	 * @property SLP_Option      $map_height
	 * @property SLP_Option      $map_height_units
	 * @property SLP_Option      $map_home_icon
	 * @property SLP_Option      $map_width
	 * @property SLP_Option      $map_width_units
	 * @property SLP_Option      $message_no_results
	 * @property SLP_Option      $message_no_api_key
	 * @property SLP_Option      $remove_credits
	 * @property SLP_Option      $zoom_level
	 * @property SLP_Option      $zoom_tweak
	 *
	 * @property        array    $change_callbacks                       Stack (array) of callbacks in array ( _func_ , _params_ ) format.
	 *
	 * @property-read   string[] $current_checkboxes                     Array of smart option checkbox slugs for the current admin screen
	 *
	 * @property-read   boolean  $db_loading                             True only when processing the option values loaded from the db.
	 *
	 * @property-read   array    $page_layout                            The page layout array( pages slugs => array( section slugs => array( group_slugs => property slugs ) ) )
	 *
	 * @property-read   string[] $smart_properties                       Array of property names that are smart options.
	 *
	 * @property-read   int      $style_id                               The active style ID (hidden, works with "style" setting).
	 *
	 * @property        string[] $text_options                           An array of smart option slugs that are text options.
	 *
	 * @property        array    $time_callbacks                         Stack (array) of callbacks for cron jobs in array ( _func_ , _params_ ) format.
	 *
	 * @property-read  boolean   $initial_distance_already_calculated    only do this once per change set.
	 *
	 *
	 * TODO: Options for drop downs needs to be hooked to a load_dropdowns method - to offload this overhead so we don't carry around huge arrays for every SLPlus instantiation
	 * note: should be called only when rendering the admin page, the option values should go in the include/module/admin_tabs directory in an SLP_Admin_Experience_Dropdown class
	 *
	 */
	class SLP_SmartOptions extends SLPlus_BaseClass_Object {

		// The SLP User-Set Options
		public $admin_notice_dismissed;
		public $distance_unit;
		public $google_server_key;
		public $initial_radius;
		public $initial_results_returned;
		public $invalid_query_message;
		public $instructions;
		public $label_directions;
		public $label_email;
		public $label_fax;
		public $label_hours;
		public $label_image;
		public $label_phone;
		public $label_radius;
		public $label_search;
		public $label_website;
		public $log_schedule_messages;
		public $map_center;
		public $map_center_lat;
		public $map_center_lng;
		public $message_no_results;
		public $message_no_api_key;
		public $remove_credits;
		public $style_id;
		public $zoom_tweak;

		// Things that help us manage the options.
		private $current_checkboxes;

		protected $change_callbacks = array();

		private $db_loading = false;

		private $initial_distance_already_calculated = false;

		private $initialized = false;

		public $page_layout;

		private $smart_properties;

		public $text_options;

		protected $time_callbacks = array();

		/**
		 * Things we do at the start.
		 */
		public function initialize() {
			if ( $this->initialized ) {
				return;
			}
			require_once( SLPLUS_PLUGINDIR . 'include/unit/SLP_Option.php' );

			$this->create_system_wide_options();

			$this->create_experience_options();

			$this->create_general_options();

			$this->initialized = true;
		}

		/**
		 * Things we do when a new map center is set.
		 *
		 * TODO: look up the address and set the lat/long.
		 *
		 * @param $key
		 * @param $old_val
		 * @param $new_val
		 */
		public function change_map_center( $key, $old_val, $new_val ) {
			$this->map_center_lng->value             = null;
			$this->slplus->options['map_center_lng'] = null;

			$this->map_center_lat->value             = null;
			$this->slplus->options['map_center_lat'] = null;

			$this->slplus->recenter_map();

			$this->recalculate_initial_distance( $key, $old_val, $new_val );
		}

		/**
		 * Run this when the style ID changes.
		 *
		 * @param $key
		 * @param $old_val
		 * @param $new_val
		 */
		public function change_style_id( $key, $old_val, $new_val ) {
			require_once( SLPLUS_PLUGINDIR . 'include/module/style/SLP_Style_Manager.php' );
			$this->slplus->Style_Manager->change_style( $old_val, $new_val );
		}

		/**
		 * System Wide Smart Options
		 */
		private function create_system_wide_options() {

			$smart_options['active_style_css'] = array(
				'default' => <<<ACTIVE_STYLE_CSS
div#map img {
    background-color: transparent;
    box-shadow: none;
    border: 0;
    max-width: none;
    opacity: 1.0
}

div#map div {
    overflow: visible
}

div#map .gm-style-cc > div {
    word-wrap: normal
}

div#map img[src='http://maps.gstatic.com/mapfiles/iws3.png'] {
    display: none
}

.slp_search_form .search_box {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    align-content: stretch
}

.slp_search_form .search_box .search_item {
    flex: 1 1 auto;
    display: flex;
    align-items: flex-start;
    justify-content: stretch;
    margin-bottom: .25em
}

.slp_search_form .search_box .search_item label {
    text-align: right;
    min-width: 8em;
    margin-right: .25em
}

.slp_search_form .search_box .search_item div {
    flex: 1 1 auto;
    display: flex
}

.slp_search_form .search_box .search_item #radius_in_submit {
    text-align: right
}

.slp_search_form .search_box .search_item .slp_ui_button {
    margin: .25em 0
}

.store_locator_plus.tagline {
    font-size: .75em;
    text-align: right
}

.slp_results_container .results_wrapper {
    margin: .5em 0;
    padding: .5em;
    border: solid 1px lightgrey;
    border-radius: .5em
}

.slp_results_container .results_wrapper:hover {
    background-color: lightgrey;
    border: solid 1px grey
}

.slp_results_container .results_wrapper .location_name {
    font-size: 1.15em
}

.slp_results_container .results_wrapper .location_distance {
    float: right;
    vertical-align: text-top
}
ACTIVE_STYLE_CSS
			);
			$smart_options['active_style_date'] = array();

			$smart_options['admin_notice_dismissed'] = array( 'type' => 'checkbox', 'default' => '0' );

			$smart_options['google_server_key'] = array();

			$smart_options['invalid_query_message'] = array( 'is_text' => true, );

			$smart_options['message_no_api_key'] = array( 'is_text' => true, 'use_in_javascript' => true, );

			$this->create_smart_options( $smart_options );
		}

		/**
		 * Experience
		 */
		private function create_experience_options() {
			$this->create_experience_search_options();
			$this->create_experience_map_options();
			$this->create_experience_results_options();
			$this->create_experience_view_options();
		}

		/**
		 * Experience / Search
		 */
		private function create_experience_search_options() {

			// Functionality
			$smart_options['distance_unit'] = array(
				'page'              => 'slp_experience',
				'section'           => 'search',
				'group'             => 'functionality',
				'default'           => 'miles',
				'call_when_changed' => array( $this, 'recalculate_initial_distance' ),
				'use_in_javascript' => true,
				'type'              => 'dropdown',
				'options'           => array(
					array( 'label' => __( 'Kilometers', 'store-locator-le' ), 'value' => 'km' ),
					array( 'label' => __( 'Miles', 'store-locator-le' ), 'value' => 'miles' ),
				),
			);
			$smart_options['radii']         = array(
				'page'              => 'slp_experience',
				'section'           => 'search',
				'group'             => 'functionality',
				'default'           => '10,25,50,100,(200),500',
				'use_in_javascript' => true,
			);

			// Appearance
			$smart_options['searchlayout'] = array(
				'page'               => 'slp_experience',
				'section'            => 'search',
				'group'              => 'appearance',
				'use_in_javascript'  => true,
				'type'               => 'textarea',
				'add_to_settings_tab' => false,
				'default'           => <<<SEARCHLAYOUT
<div id="address_search" class="slp search_box">
    [slp_addon location="very_top"]
    [slp_search_element input_with_label="name"]
    [slp_search_element input_with_label="address"]
    [slp_search_element dropdown_with_label="city"]
    [slp_search_element dropdown_with_label="state"]
    [slp_search_element dropdown_with_label="country"]
    [slp_search_element selector_with_label="tag"]
    [slp_search_element dropdown_with_label="category"]
    [slp_search_element dropdown_with_label="gfl_form_id"]
    [slp_addon location="before_radius_submit"]
    <div class="search_item">
        [slp_search_element dropdown_with_label="radius"]
        [slp_search_element button="submit"]
    </div>
    [slp_addon location="after_radius_submit"]
    [slp_addon location="very_bottom"]
</div>
SEARCHLAYOUT
			);

			// Labels
			$smart_options['label_radius'] = array(
				'page'    => 'slp_experience',
				'section' => 'search',
				'group'   => 'labels',
				'is_text' => true,
			);

			$smart_options['label_search'] = array(
				'page'       => 'slp_experience',
				'section'    => 'search',
				'group'      => 'labels',
				'is_text'    => true,
				'related_to' => 'address_placeholder,hide_address_entry',
			);
			$this->create_smart_options( $smart_options );
		}

		/**
		 * Experience / View
		 */
		private function create_experience_view_options() {
			$smart_options['style_id'] = array(
				'page'              => 'slp_experience',
				'section'           => 'view',
				'group'             => 'appearance',
				'type'              => 'hidden',
				'call_when_changed' => array( $this, 'change_style_id' ),
			);
			$smart_options['style']    = array(
				'page'    => 'slp_experience',
				'section' => 'view',
				'group'   => 'appearance',
				'type'    => 'style_vision_list',
			);
			$smart_options['theme']    = array(
				'page'    => 'slp_experience',
				'section' => 'view',
				'group'   => 'appearance',
				'type'    => 'list',
				'get_items_callback' => array( $this , 'get_theme_items' ),
				'default' => 'a_gallery_style',
			);
			$smart_options['layout'] = array(
				'page'              => 'slp_experience',
				'section'           => 'view',
				'group'             => 'appearance',
				'type'              => 'textarea',
				'add_to_settings_tab' => false,
				'default'           => '<div id="sl_div">[slp_search][slp_map][slp_results]</div>'
			);
			$this->create_smart_options( $smart_options );
		}

		/**
		 * Experience / Map
		 */
		private function create_experience_map_options() {

			// At Startup
			$smart_options['map_center']     = array(
				'page'              => 'slp_experience',
				'section'           => 'map',
				'group'             => 'at_startup',
				'type'              => 'textarea',
				'related_to'        => 'map_center_lat,map_center_lng',
				'use_in_javascript' => true,
				'call_when_changed' => array( $this, 'change_map_center' ),
			);
			$smart_options['map_center_lat'] = array(
				'page'              => 'slp_experience',
				'section'           => 'map',
				'group'             => 'at_startup',
				'related_to'        => 'map_center,map_center_lng',
				'use_in_javascript' => true,
				'call_when_changed' => array( $this, 'recalculate_initial_distance' ),
			);
			$smart_options['map_center_lng'] = array(
				'page'              => 'slp_experience',
				'section'           => 'map',
				'group'             => 'at_startup',
				'related_to'        => 'map_center,map_center_lat',
				'use_in_javascript' => true,
				'call_when_changed' => array( $this, 'recalculate_initial_distance' ),
			);

			// Functionality
			$smart_options['zoom_level'] = array(
				'page'              => 'slp_experience',
				'section'           => 'map',
				'group'             => 'functionality',
				'type'              => 'dropdown',
				'default'           => '12',
				'use_in_javascript' => true,
				'options'           => array(
					array( 'label' => '0' ),
					array( 'label' => '1' ),
					array( 'label' => '2' ),
					array( 'label' => '3' ),
					array( 'label' => '4' ),
					array( 'label' => '5' ),
					array( 'label' => '6' ),
					array( 'label' => '7' ),
					array( 'label' => '8' ),
					array( 'label' => '9' ),
					array( 'label' => '10' ),
					array( 'label' => '11' ),
					array( 'label' => '12' ),
					array( 'label' => '13' ),
					array( 'label' => '14' ),
					array( 'label' => '15' ),
					array( 'label' => '16' ),
					array( 'label' => '17' ),
					array( 'label' => '18' ),
					array( 'label' => '19' ),
				),
			);
			$smart_options['zoom_tweak'] = array(
				'page'              => 'slp_experience',
				'section'           => 'map',
				'group'             => 'functionality',
				'type'              => 'dropdown',
				'default'           => '0',
				'use_in_javascript' => true,
				'options'           => array(
					array( 'label' => '-10' ),
					array( 'label' => '-9' ),
					array( 'label' => '-8' ),
					array( 'label' => '-7' ),
					array( 'label' => '-6' ),
					array( 'label' => '-5' ),
					array( 'label' => '-4' ),
					array( 'label' => '-3' ),
					array( 'label' => '-2' ),
					array( 'label' => '-1' ),
					array( 'label' => '0' ),
					array( 'label' => '1' ),
					array( 'label' => '2' ),
					array( 'label' => '3' ),
					array( 'label' => '4' ),
					array( 'label' => '5' ),
					array( 'label' => '6' ),
					array( 'label' => '7' ),
					array( 'label' => '8' ),
					array( 'label' => '9' ),
					array( 'label' => '10' ),
					array( 'label' => '11' ),
					array( 'label' => '12' ),
					array( 'label' => '13' ),
					array( 'label' => '14' ),
					array( 'label' => '15' ),
					array( 'label' => '16' ),
					array( 'label' => '17' ),
					array( 'label' => '18' ),
					array( 'label' => '19' ),
				),
			);

			// Appearance
			$smart_options['map_height']       = array(
				'page'       => 'slp_experience',
				'section'    => 'map',
				'group'      => 'appearance',
				'default'    => '480',
				'related_to' => 'map_height_units',
			);
			$smart_options['map_height_units'] = array(
				'page'       => 'slp_experience',
				'section'    => 'map',
				'group'      => 'appearance',
				'default'    => 'px',
				'related_to' => 'map_height',
				'type'       => 'dropdown',
				'options'    =>
					array(
						array( 'label' => '%' ),
						array( 'label' => 'px' ),
						array( 'label' => 'em' ),
						array( 'label' => 'pt' ),
						array( 'label' => __( 'CSS / inherit', 'store-locator-le' ), 'value' => ' ' ),
					),
			);
			$smart_options['map_width']        = array(
				'page'       => 'slp_experience',
				'section'    => 'map',
				'group'      => 'appearance',
				'default'    => '100',
				'related_to' => 'map_width_units',
			);
			$smart_options['map_width_units']  = array(
				'page'       => 'slp_experience',
				'section'    => 'map',
				'group'      => 'appearance',
				'default'    => '%',
				'related_to' => 'map_width',
				'type'       => 'dropdown',
				'options'    => array(
					array( 'label' => '%' ),
					array( 'label' => 'px' ),
					array( 'label' => 'em' ),
					array( 'label' => 'pt' ),
					array( 'label' => __( 'CSS / inherit', 'store-locator-le' ), 'value' => ' ' ),
				),
			);
			$smart_options['map_type']         = array(
				'page'              => 'slp_experience',
				'section'           => 'map',
				'group'             => 'appearance',
				'default'           => 'roadmap',
				'type'              => 'dropdown',
				'use_in_javascript' => true,
				'options'           => array(
					array( 'label' => 'Roadmap', 'value' => 'roadmap' ),
					array( 'label' => 'Hybrid', 'value' => 'hybrid' ),
					array( 'label' => 'Satellite', 'value' => 'satellite' ),
					array( 'label' => 'Terrain', 'value' => 'terrain' ),
				),
			);
			$smart_options['remove_credits']   = array(
				'page'    => 'slp_experience',
				'section' => 'map',
				'group'   => 'appearance',
				'type'    => 'checkbox',
				'default' => '0',
			);
			$smart_options['maplayout'] = array(
				'page'               => 'slp_experience',
				'section'            => 'map',
				'group'              => 'appearance',
				'use_in_javascript'  => true,
				'type'               => 'textarea',
				'add_to_settings_tab' => false,
				'default'           => '[slp_mapcontent][slp_maptagline]'
			);
			$smart_options['bubblelayout'] = array(
				'page'               => 'slp_experience',
				'section'            => 'map',
				'group'              => 'appearance',
				'use_in_javascript'  => true,
				'type'               => 'textarea',
				'add_to_settings_tab' => false,
				'default'           => <<<BUBBLELAYOUT
<div id="slp_info_bubble_[slp_location id]" class="slp_info_bubble [slp_location featured]">
    <span id="slp_bubble_name"><strong>[slp_location name  suffix  br]</strong></span>
    <span id="slp_bubble_address">[slp_location address       suffix  br]</span>
    <span id="slp_bubble_address2">[slp_location address2      suffix  br]</span>
    <span id="slp_bubble_city">[slp_location city          suffix  comma]</span>
    <span id="slp_bubble_state">[slp_location state suffix    space]</span>
    <span id="slp_bubble_zip">[slp_location zip suffix  br]</span>
    <span id="slp_bubble_country"><span id="slp_bubble_country">[slp_location country       suffix  br]</span></span>
    <span id="slp_bubble_directions">[html br ifset directions]
    [slp_option label_directions wrap directions]</span>
    <span id="slp_bubble_website">[html br ifset url][slp_location web_link][html br ifset url]</span>
    <span id="slp_bubble_email">[slp_location email         wrap    mailto ][slp_option label_email ifset email][html closing_anchor ifset email][html br ifset email]</span>
    <span id="slp_bubble_phone">[html br ifset phone]
    <span class="location_detail_label">[slp_option   label_phone   ifset   phone]</span>[slp_location phone         suffix    br]</span>
    <span id="slp_bubble_fax"><span class="location_detail_label">[slp_option   label_fax     ifset   fax  ]</span>[slp_location fax           suffix    br]<span>
    <span id="slp_bubble_description"><span id="slp_bubble_description">[html br ifset description]
    [slp_location description raw]</span>[html br ifset description]</span>
    <span id="slp_bubble_hours">[html br ifset hours]
    <span class="location_detail_label">[slp_option   label_hours   ifset   hours]</span>
    <span class="location_detail_hours">[slp_location hours         suffix    br]</span></span>
    <span id="slp_bubble_img">[html br ifset img]
    [slp_location image         wrap    img]</span>
    <span id="slp_tags">[slp_location tags]</span>
</div>
BUBBLELAYOUT
			);

			// Markers
			$smart_options['map_home_icon'] = array(
				'page'              => 'slp_experience',
				'section'           => 'map',
				'group'             => 'markers',
				'type'              => 'icon',
				'use_in_javascript' => true,
				'default'           => SLPLUS_ICONURL . 'bulb_yellow.png',

			);
			$smart_options['map_end_icon']  = array(
				'page'              => 'slp_experience',
				'section'           => 'map',
				'group'             => 'markers',
				'type'              => 'icon',
				'use_in_javascript' => true,
				'default'           => SLPLUS_ICONURL . 'bulb_azure.png',
			);
			$this->create_smart_options( $smart_options );
		}

		/**
		 * Experience / Results
		 */
		private function create_experience_results_options() {

			// At Startup
			$smart_options['immediately_show_locations'] = array(
				'page'              => 'slp_experience',
				'section'           => 'results',
				'group'             => 'at_startup',
				'type'              => 'checkbox',
				'default'           => '1',
				'use_in_javascript' => true,
			);
			$smart_options['initial_radius']             = array(
				'page'              => 'slp_experience',
				'section'           => 'results',
				'group'             => 'at_startup',
				'default'           => '',
				'use_in_javascript' => true,
			);
			$smart_options['initial_results_returned']   = array(
				'page'              => 'slp_experience',
				'section'           => 'results',
				'group'             => 'at_startup',
				'default'           => '25',
				'use_in_javascript' => true,
			);

			// After Search
			$smart_options['max_results_returned'] = array(
				'page'    => 'slp_experience',
				'section' => 'results',
				'group'   => 'after_search',
				'default' => '25',
			);

			// Appearance
			$smart_options['message_no_results'] = array(
				'is_text'           => true,
				'use_in_javascript' => true,
			);
			$smart_options['resultslayout'] = array(
				'page'               => 'slp_experience',
				'section'            => 'results',
				'group'              => 'appearance',
				'use_in_javascript'  => true,
				'type'               => 'textarea',
				'add_to_settings_tab' => false,
				'default'           => <<<RESULTSLAYOUT
<div id="slp_results_[slp_location id]" class="results_entry location_primary [slp_location featured]">
    <div class="results_row_left_column"   id="slp_left_cell_[slp_location id]"   >
        [slp_addon section=primary position=first]
        <span class="location_name">[slp_location name] [slp_location uml_buttons] [slp_location gfi_buttons]</span>
        <span class="location_distance">[slp_location distance_1] [slp_location distance_unit]</span>
        [slp_addon section=primary position=last]
    </div>
    <div class="results_row_center_column location_secondary" id="slp_center_cell_[slp_location id]" >
        [slp_addon section=secondary position=first]
        <span class="slp_result_address slp_result_street">[slp_location address]</span>
        <span class="slp_result_address slp_result_street2">[slp_location address2]</span>
        <span class="slp_result_address slp_result_citystatezip">[slp_location city_state_zip]</span>
        <span class="slp_result_address slp_result_country">[slp_location country]</span>
        <span class="slp_result_address slp_result_phone">[slp_location phone]</span>
        <span class="slp_result_address slp_result_fax">[slp_location fax]</span>
        [slp_addon section=secondary position=last]
    </div>
    <div class="results_row_right_column location_tertiary"  id="slp_right_cell_[slp_location id]"  >
        [slp_addon section=tertiary position=first]
        <span class="slp_result_contact slp_result_website">[slp_location web_link]</span>
        <span class="slp_result_contact slp_result_email">[slp_location email_link]</span>
        <span class="slp_result_contact slp_result_directions"><a href="https://[slp_option map_domain]/maps?saddr=[slp_location search_address]&daddr=[slp_location location_address]" target="_blank" class="storelocatorlink">[slp_location directions_text]</a></span>
        <span class="slp_result_contact slp_result_hours">[slp_location hours]</span>
        [slp_location pro_tags]
        [slp_location iconarray wrap="fullspan"]
        [slp_location eventiconarray wrap="fullspan"]
        [slp_location socialiconarray wrap="fullspan"]
        [slp_addon section=tertiary position=last]
    </div>
</div>
RESULTSLAYOUT
			);

			// Labels
			$smart_options['instructions']     = array(
				'page'    => 'slp_experience',
				'section' => 'results',
				'group'   => 'labels',
				'is_text' => true,
			);
			$smart_options['label_website']    = array(
				'page'              => 'slp_experience',
				'section'           => 'results',
				'group'             => 'labels',
				'is_text'           => true,
				'use_in_javascript' => true,
			);
			$smart_options['label_directions'] = array(
				'page'              => 'slp_experience',
				'section'           => 'results',
				'group'             => 'labels',
				'is_text'           => true,
				'use_in_javascript' => true,
			);
			$smart_options['label_hours']      = array(
				'page'    => 'slp_experience',
				'section' => 'results',
				'group'   => 'labels',
				'is_text' => true,
			);
			$smart_options['label_email']      = array(
				'page'              => 'slp_experience',
				'section'           => 'results',
				'group'             => 'labels',
				'is_text'           => true,
				'use_in_javascript' => true,
			);
			$smart_options['label_phone']      = array(
				'page'              => 'slp_experience',
				'section'           => 'results',
				'group'             => 'labels',
				'is_text'           => true,
				'use_in_javascript' => true,
			);
			$smart_options['label_fax']        = array(
				'page'              => 'slp_experience',
				'section'           => 'results',
				'group'             => 'labels',
				'is_text'           => true,
				'use_in_javascript' => true,
			);
			$smart_options['label_image']      = array(
				'page'    => 'slp_experience',
				'section' => 'results',
				'group'   => 'labels',
				'is_text' => true,
			);
			$this->create_smart_options( $smart_options );
		}

		/**
		 * General
		 */
		private function create_general_options() {
			$this->create_general_admin_options();
		}

		/**
		 * General / Admin
		 */
		private function create_general_admin_options() {
			$page    = 'slp_general';
			$section = 'admin';

			// Messages
			$group                                  = 'messages';
			$smart_options['log_schedule_messages'] = array(
				'page'    => $page,
				'section' => $section,
				'group'   => $group,
				'type'    => 'checkbox',
				'default' => '1',
			);

			$this->create_smart_options( $smart_options );
		}

		/**
		 * Create smart option objects and set to default_if_empty values.
		 *
		 * @param array $smart_option_params array of options arrays = array ( 'slug' , ... )
		 */
		public function create_smart_options( $smart_option_params ) {

			foreach ( $smart_option_params as $slug => $option_params ) {
				$property = $slug;

				if ( property_exists( $this, $property ) && ! empty( $this->{$property} ) ) {
					continue;
				}

				$option_params['slug'] = $slug;
				$this->{$property}     = new SLP_Option( $option_params );

				// JS / no JS
				if ( $this->$property->use_in_javascript ) {
					$this->slplus->options[ $property ] = $this->$property->default;
				} else {
					$this->slplus->options_nojs[ $property ] = $this->$property->default;
				}

				// Cron Job Registration
				if ( defined( 'DOING_CRON' ) && ! empty( $this->$property->call_when_time ) ) {
					$this->time_callbacks[] = array( $this->$property->call_when_time, $property );
				}

				// Text Option
				if ( $this->$property->is_text ) {
					$this->text_options[] = $property;
				}

				// List of Smart Option Slugs
				//
				$this->smart_properties[] = $slug;

				// Page Layout
				//
				if ( ! empty( $this->$property->page ) ) {
					if ( empty( $this->$property->section ) ) {
						$this->$property->section = 'default';
					}
					if ( empty ( $this->$property->group ) ) {
						$this->$property->group = 'default';
					}

					$this->page_layout[ $this->$property->page ][ $this->$property->section ][ $this->$property->group ][] = $slug;
				}

			}
		}

		/**
		 * Execute the stack of change callbacks.
		 *
		 * Use this to run callbacks after all options have been updated.
		 */
		public function execute_change_callbacks() {
			if ( ! empty( $this->change_callbacks ) ) {
				foreach ( $this->change_callbacks as $callback_info ) {
					call_user_func_array( $callback_info[0], $callback_info[1] );
				}
				$this->change_callbacks = array();
			}
		}

		/**
		 * Execute the stack of time callbacks.
		 *
		 * Use this to run callbacks after all options have been updated.
		 */
		public function execute_time_callbacks() {
			if ( defined( 'DOING_CRON' ) && ! empty( $this->time_callbacks ) ) {
				foreach ( $this->time_callbacks as $callback_info ) {
					call_user_func( $callback_info[0], $callback_info[1] );
				}
				$this->time_callbacks = array();
			}
		}

		/**
		 * Does the specified slug exist as a smart option?
		 *
		 * @param string $property
		 *
		 * @return boolean
		 */
		public function exists( $property ) {
			return property_exists( $this, $property );
		}

		/**
		 * Return the property formatted option name.
		 *
		 * @param $property
		 *
		 * @return string
		 */
		public function get_option_name( $property ) {
			if ( property_exists( $this, $property ) ) {
				$base_setting = $this->$property->use_in_javascript ? 'options' : 'options_nojs';

				return "${base_setting}[{$property}]";
			}

			return $property;
		}

		/**
		 * Remember the original value of a setting before we change it.
		 *
		 * @param $new_value
		 * @param $key
		 * @param $option_array
		 * @param $is_smart_option
		 * @param $valid_legacy_option
		 *
		 * @return mixed
		 */
		private function get_original_value( $new_value, $key, &$option_array, $is_smart_option, $valid_legacy_option ) {

			// Invalid Setting - null
			if ( ! $is_smart_option && ! $valid_legacy_option ) {
				return null;
			}

			// Loading from DB - use db value
			if ( $this->db_loading ) {
				return $new_value;
			}

			// Smart option - return value (it reads from options array or default as needed)
			if ( $is_smart_option ) {
				return $this->$key->value;
			}

			// Send back original value
			if ( $valid_legacy_option ) {
				return $option_array[ $key ];
			}
		}

		/**
		 * Get the parameters needed for the SLP_Settings entry.
		 *
		 * @param array $params
		 *
		 * @return array
		 */
		public function get_setting_params( $params ) {
			$option = $this->{$params['option']};

			$property_params = array( 'get_items_callback' , 'description' , 'related_to', 'show_label' , 'type', 'value' );
			foreach ( $property_params as $param ) {
				$params[ $param ] = $option->{$param};
			}

			$params['option_name'] = 'smart_option';
			$params['use_prefix']  = false;

			$params['selectedVal'] = $params['value'];

			$params['setting'] = $this->get_option_name( $params['option'] );
			$params['name']    = $params['setting'];

			if ( $params['show_label'] ) {
				$params['label'] = $option->label;
			}

			if ( $params['type'] === 'dropdown' ) {
				if ( ! empty( $option->options ) ) {
					foreach ( $option->options as $dropdown_option ) {
						if ( ! empty( $dropdown_option['description'] ) ) {
							$params['description'] .= sprintf( '<p class="selections"><span class="label">%s</span><span class="function">%s</span>', $dropdown_option['label'], $dropdown_option['description'] );
						}
					}
				}
			}

			$params['custom'] = $option->options;

			$params['empty_ok'] = true;

			unset( $params['option'] );
			unset( $params['plugin'] );

			return $params;
		}

		/**
		 * Get string defaults.
		 *
		 * @param string $key key name for string to translate
		 *
		 * @return string
		 */
		private function get_string_default( $key ) {
			require_once( SLPLUS_PLUGINDIR . 'include/module/i18n/SLP_Text.php' );
			$text_to_return = $this->slplus->Text->get_text_string( array( 'option_default', $key ) );
			if ( empty( $text_to_return ) ) {
				$text_to_return = apply_filters( 'slp_string_default', '', $key );
			}

			return $text_to_return;
		}

		/**
		 * Return a list of option slugs that are text options.
		 *
		 * @return string[]
		 */
		public function get_text_options() {
			if ( ! isset( $this->text_options ) ) {
				$smart_options = get_object_vars( $this );
				foreach ( $smart_options as $slug => $option ) {
					if ( $option->is_text ) {
						$this->text_options[] = $option->slug;
					}
				}
			}

			return $this->text_options;
		}

		/**
		 * Callback for getting theme (plugin style) item list.
		 */
		public function get_theme_items() {
			require_once( SLPLUS_PLUGINDIR . 'include/module/style/SLP_Plugin_Style.php' );
			return $this->slplus->Plugin_Style->get_theme_list();
		}

		/**
		 * Things we do once after the plugins are loaded.
		 */
		public function initialize_after_plugins_loaded() {
			$this->set_text_string_defaults();
			$this->slp_specific_setup();
		}

		/**
		 * Recalculate the initial distance for a location from the map center.
		 *
		 * Called if 'distance_unit' changes.
		 */
		public function recalculate_initial_distance( $key, $old_val, $new_val ) {
			if ( ! $this->initial_distance_already_calculated ) {
				require_once( SLPLUS_PLUGINDIR . 'include/module/locations/SLP_Location_Manager.php' );
				$this->slplus->Location_Manager->recalculate_initial_distance();
				$this->initial_distance_already_calculated = true;
			}
		}

		/**
		 * Set the smart option value and the legacy options/options_nojs
		 *
		 * @param $property
		 * @param $value
		 */
		public function set( $property, $value ) {
			if ( property_exists( $this, $property ) ) {
				$this->$property->value = $value;

				if ( $this->$property->use_in_javascript ) {
					$this->set_valid_options( $value, $property );
				} else {
					$this->set_valid_options_nojs( $value, $property );
				}
			}
		}

		/**
		 * Set text string defaults.
		 */
		private function set_text_string_defaults() {
			foreach ( $this->get_text_options() as $key ) {

				if ( array_key_exists( $key, $this->slplus->options ) ) {
					$this->slplus->options[ $key ] = $this->get_string_default( $key );

				} elseif ( array_key_exists( $key, $this->slplus->options_nojs ) ) {
					$this->slplus->options_nojs[ $key ] = $this->get_string_default( $key );

				}
			}
		}

		/**
		 * Initialize the options properties from the WordPress database.
		 *
		 * Called by MySLP Dashboard.
		 */
		public function slp_specific_setup() {
			do_action( 'start_slp_specific_setup' );

			// Serialized Options from DB for JS parameters
			//
			$this->slplus->options_default = $this->slplus->options;
			$dbOptions                     = $this->slplus->WPOption_Manager->get_wp_option( 'js' );
			if ( is_array( $dbOptions ) ) {
				$this->db_loading = true;
				array_walk( $dbOptions, array( $this, 'set_valid_options' ) );
				$this->db_loading = false;
			}

			// Map Center Fallback
			//
			$this->slplus->recenter_map();

			// Load serialized options for noJS parameters
			//
			$this->slplus->options_nojs_default = $this->slplus->options_nojs;
			$dbOptions                          = $this->slplus->WPOption_Manager->get_wp_option( 'nojs' );
			if ( is_array( $dbOptions ) ) {
				$this->db_loading = true;
				array_walk( $dbOptions, array( $this, 'set_valid_options_nojs' ) );
				$this->db_loading = false;
			}
			$this->slplus->javascript_is_forced = $this->slplus->is_CheckTrue( $this->slplus->options_nojs['force_load_js'] );
		}

		/**
		 * Set incoming REQUEST checkboxes for the current admin page.
		 */
		public function set_checkboxes() {
			$this->set_current_checkboxes();
			if ( is_array( $this->current_checkboxes ) ) {
				foreach ( $this->current_checkboxes as $property ) {
					$which_option = $this->$property->use_in_javascript ? 'options' : 'options_nojs';
					if ( isset( $_REQUEST[ $which_option ][ $this->$property->slug ] ) ) {
						continue;
					}
					$_REQUEST[ $which_option ][ $this->$property->slug ] = '0';
				}
			}
		}

		/**
		 * Builds a list of checkboxes for the current admin settings page.
		 */
		private function set_current_checkboxes() {
			if ( empty( $this->current_checkboxes ) ) {
				if ( empty( $this->page_layout[ $this->slplus->current_admin_page ] ) ) {
					return;
				}
				foreach ( $this->page_layout[ $this->slplus->current_admin_page ] as $sections ) {
					foreach ( $sections as $groups ) {
						foreach ( $groups as $property ) {
							if ( $this->$property->type === 'checkbox' ) {
								$this->current_checkboxes[] = $property;
							}
						}
					}
				}
			}
		}

		/**
		 * Set the value of a smart option & legacy option array copy
		 *
		 * @param $value
		 * @param $key
		 * @param $option_array
		 * @param $is_smart_option
		 * @param $valid_legacy_option
		 *
		 * @return mixed
		 */
		private function set_the_val( $value, $key, &$option_array, $is_smart_option, $valid_legacy_option ) {
			if ( $is_smart_option ) {
				if ( $this->{$key}->type === 'textarea' ) {
					$value = stripslashes( $value );
				}
				$this->$key->value = $option_array[ $key ] = $value;
			} elseif ( $valid_legacy_option ) {
				$option_array[ $key ] = $value;
			} else {
				return;
			}
		}

		/**
		 * Set an option in an array only if the key already exists, for empty values set to default.
		 *
		 * @param mixed  $val - the value of a form var
		 * @param string $key - the key for that form var
		 * @param        $option_array
		 * @param        $default_array
		 */
		public function set_valid_option( $val, $key, &$option_array, $default_array ) {
			$is_smart_option     = property_exists( $this, $key );
			$valid_legacy_option = array_key_exists( $key, $option_array );

			// Remember the original value for smart options when not loading from DB
			if ( ! $this->db_loading && $is_smart_option ) {
				$original_value = $this->get_original_value( $val, $key, $option_array, $is_smart_option, $valid_legacy_option );
			}

			// Set the value
			$this->set_the_val( $val, $key, $option_array, $is_smart_option, $valid_legacy_option );

			// Loading from DB - our work is done
			if ( $this->db_loading ) {
				return;
			}

			// Not a smart option or valid legacy option - no need for defaults or change callbacks
			if ( ! $is_smart_option && ! $valid_legacy_option ) {
				return;
			}

			// Setting an option?  Set defaults if it comes in empty.
			$value_is_empty = ! ( is_numeric( $val ) || is_bool( $val ) || ! empty( $val ) );
			if ( $value_is_empty ) {
				$default_value = $is_smart_option ? $this->$key->default : $default_array[ $key ];
				$this->set_the_val( $default_value, $key, $option_array, $is_smart_option, $valid_legacy_option );
			}

			// Set callbacks for option changes.
			if ( $is_smart_option ) {
				$this->setup_smart_callback( $key, $original_value );
			}
		}

		/**
		 * Set valid slplus->options and copy to smart_options
		 *
		 * @param $val
		 * @param $key
		 */
		public function set_valid_options( $val, $key ) {
			$this->set_valid_option( $val, $key, $this->slplus->options, $this->slplus->options_default );
		}

		/**
		 * Set valid slplus->options_nojs and copy to smart_options
		 *
		 * @param $val
		 * @param $key
		 */
		public function set_valid_options_nojs( $val, $key ) {
			$this->set_valid_option( $val, $key, $this->slplus->options_nojs, $this->slplus->options_nojs_default );
		}

		/**
		 * Set value change callback methods for smart options.
		 *
		 * That are defined as on this page (or the page is not defined)
		 * Whose original value from slplus->options or slplus->options_nojs DOES NOT match the new value (from the DB usually)
		 * ... reset the original value temp var to the smart option default value (if provided , null if not provided)
		 * ... and set the smart option to the new value if not empty or the smart option default if the new value was empty
		 *
		 * @param $key
		 * @param $original_value
		 */
		private function setup_smart_callback( $key, $original_value ) {
			if ( ! empty( $this->$key->call_when_changed ) && ( $this->$key->value !== $original_value ) ) {
				$this->change_callbacks[] = array(
					$this->$key->call_when_changed,
					array( $key, $original_value, $this->$key->value ),
				);
			}
		}

	}

	/**
	 * Make use - creates as a singleton attached to slplus->object['Style_Manager']
	 *
	 * @var SLPlus $slplus
	 */
	global $slplus;
	if ( is_a( $slplus, 'SLPlus' ) ) {
		$slplus->add_object( new SLP_SmartOptions() );
		$slplus->smart_options = $slplus->SmartOptions; // TODO: remove this when all things refer to SmartOptions not smart_options
	}
}