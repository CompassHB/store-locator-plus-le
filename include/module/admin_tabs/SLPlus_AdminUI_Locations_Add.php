<?php
if ( ! class_exists( 'SLPlus_AdminUI_Locations_Add' ) ) {
    /**
     * Store Locator Plus basic admin user interface.
     *
     * @property-read   boolean      $adding             True if adding a location.
     * @property-read   array[]      $group_params       The metadata needed to build a settings group.
     * @property        string       $mode               Current operating mode 'add' | 'edit'
     * @property-read   array[]      $section_params     The metadata needed to build a settings section.
     * @property        SLP_Settings $settings           SLP Settings Interface reference SLPlus->ManageLocations->settings
     * @property        SLPlus       $slplus
     */
    class SLPlus_AdminUI_Locations_Add extends SLPlus_BaseClass_Object {
        private $adding          = true;
        private $group_params;
        private $locations_group = '';
        public  $mode            = 'add';
        private $section_params;
        public  $settings;

        /**
         * Initialize this object.
         */
        public function initialize() {
            $this->locations_group = __( 'Location', 'store-locator-le' );

            if ( ( $this->mode === 'edit' ) && isset( $_REQUEST['id'] ) ) {
                $this->section_params['name']   = __( 'Edit', 'store-locator-le' );
                $this->section_params['slug']   = 'edit';
                $this->section_params['div_id'] = 'edit_location';
                $this->adding                   = false;
                $this->slplus->currentLocation->set_PropertiesViaDB( intval( $_REQUEST['id'] ) );

            } else {
                $this->section_params['name'] = __( 'Add', 'store-locator-le' );
                $this->section_params['slug'] = 'add';
                $this->slplus->currentLocation->reset();

            }

	        if ( isset( $_REQUEST[ 'start' ] ) ) {
	        	$start_at = intval( $_REQUEST[ 'start' ] );
		        $hidden_start = "<input type='hidden' name='start' id='start' value='{$start_at}' />";
	        } else {
	        	$hidden_start = '';
	        }

            $this->section_params['opening_html'] =
                "<form id='manualAddForm' name='manualAddForm' method='post'>" .
                ( $this->adding ? '<input type="hidden" id="act" name="act" value="add" />' : '<input type="hidden" id="act" name="act" value="edit" />' ) .
                "<input type='hidden' name='id' " .
                "id='id' value='{$this->slplus->currentLocation->id}' />" .
                "<input type='hidden' name='locationID' " .
                "id='locationID' value='{$this->slplus->currentLocation->id}' />" .
                "<input type='hidden' name='linked_postid-{$this->slplus->currentLocation->id}' " .
                "id='linked_postid-{$this->slplus->currentLocation->id}' value='" .
                $this->slplus->currentLocation->linked_postid .
                "' />" .
                $hidden_start .
                "<a name='a{$this->slplus->currentLocation->id}'></a>";
            $this->section_params['closing_html'] = '</form>';

            $this->settings->add_section( $this->section_params );

            // Common params for all groups in this section.
            //
            $this->group_params['section_slug'] = $this->section_params['slug'];
            $this->group_params['plugin']       = $this->slplus;

        }

        /**
         * Create the address block
         */
        private function address_block() {
            $this->group_params['header']     = __( 'Location', 'store-locator-le' );
            $this->group_params['group_slug'] = 'location';
            $this->group_params['div_group']  = 'left_side';
            $this->settings->add_group( $this->group_params );

            $this->create_input(
                'store',
                __( 'Name', 'store-locator-le' ),
                $this->slplus->currentLocation->store
            );
            $this->create_input(
                'address',
                __( 'Street - Line 1', 'store-locator-le' ),
                $this->slplus->currentLocation->address
            );
            $this->create_input(
                'address2',
                __( 'Street - Line 2', 'store-locator-le' ),
                $this->slplus->currentLocation->address2
            );
            $this->create_input(
                'city',
                __( 'City', 'store-locator-le' ),
                $this->slplus->currentLocation->city
            );
            $this->create_input(
                'state',
                __( 'State', 'store-locator-le' ),
                $this->slplus->currentLocation->state
            );
            $this->create_input(
                'zip',
                __( 'ZIP / Postal Code', 'store-locator-le' ),
                $this->slplus->currentLocation->zip
            );
            $this->create_input(
                'country',
                __( 'Country', 'store-locator-le' ),
                $this->slplus->currentLocation->country
            );

            $leave_lat_blank = __( 'Leave blank to have Google look up the latitude. ', 'store-locator-le' );
            $original_lat    =
                $this->adding ? '' : sprintf( __( 'Latitude was originally set to %f.', 'store-locator-le' ), $this->slplus->currentLocation->latitude );
            $this->create_input(
                'latitude',
                __( 'Latitude (N/S)', 'store-locator-le' ),
                $this->slplus->currentLocation->latitude,
                'input',
                $leave_lat_blank,
                $leave_lat_blank . $original_lat
            );

            $leave_lng_blank = __( 'Leave blank to have Google look up the longitude. ', 'store-locator-le' );
            $original_lng    =
                $this->adding ? '' : sprintf( __( 'Longitude was originally set to %f.', 'store-locator-le' ), $this->slplus->currentLocation->longitude );
            $this->create_input(
                'longitude',
                __( 'Longitude (E/W)', 'store-locator-le' ),
                $this->slplus->currentLocation->longitude,
                'input',
                $leave_lng_blank,
                $leave_lng_blank . $original_lng
            );

            $this->create_input(
                'description',
                __( 'Description', 'store-locator-le' ),
                $this->slplus->currentLocation->description,
                'textarea'
            );
            $this->create_input(
                'url',
                $this->slplus->WPML->get_text( 'label_website' ),
                $this->slplus->currentLocation->url
            );
            $this->create_input(
                'email',
                $this->slplus->WPML->get_text( 'label_email' ),
                $this->slplus->currentLocation->email
            );
            $this->create_input(
                'hours',
                $this->slplus->WPML->get_text( 'label_hours' ),
                $this->slplus->currentLocation->hours,
                'textarea'
            );
            $this->create_input(
                'phone',
                $this->slplus->WPML->get_text( 'label_phone' ),
                $this->slplus->currentLocation->phone
            );
            $this->create_input(
                'fax',
                $this->slplus->WPML->get_text( 'label_fax' ),
                $this->slplus->currentLocation->fax
            );
            $this->create_input(
                'image',
                __( 'Image URL', 'store-locator-le' ),
                $this->slplus->currentLocation->image
            );

            $this->create_input(
                "private",
                __( 'Private Entry', 'store-locator-le' ),
                $this->slplus->is_CheckTrue( $this->slplus->currentLocation->private ),
                'checkbox',
                '',
                __( 'Check this to prevent the location from showing up on user searches and the map.', 'store-locator-le' )
            );

            $this->settings->add_ItemToGroup( array(
                'group_params' => $this->group_params,
                'type'         => 'custom',
                'custom'       => $this->submit_button(),
            ) );
        }

        /**
         * Build the add or edit interface.
         */
        public function build_interface() {

            $this->address_block();

            $this->details_block();         // Add Ons With Location Fields

            $this->extended_data_block();   // Add Ons Using Extended Data Fields

            $this->map();
        }

        /**
         * Create form inputs.
         *
         * @param   string $fldName     name of the field, base name only
         * @param   string $fldLabel    label to show ahead of the input
         * @param   string $fldValue
         * @param   string $inType
         * @param   string $placeholder the placeholder for the input field (default: blank)
         * @param   string $description The help text
         *
         * @return string the form HTML output
         */
        private function create_input( $fldName, $fldLabel, $fldValue, $inType = 'input', $placeholder = '', $description = '' ) {
            $matches  = array();
            $matchStr = '/(.+)\[(.*)\]/';
            if ( preg_match( $matchStr, $fldName, $matches ) ) {
                $fldName    = $matches[1];
                $subFldName = '[' . $matches[2] . ']';
            } else {
                $subFldName = '';
            }

            $this->settings->add_ItemToGroup( array(
                'group_params' => $this->group_params,
                'label'        => $fldLabel,
                'id'           => "edit-{$fldName}-{$this->slplus->currentLocation->id}{$subFldName}",
                'name'         => "{$fldName}-{$this->slplus->currentLocation->id}{$subFldName}",
                'data_field'   => $fldName,
                'value'        => $fldValue,
                'type'         => $inType,
                'placeholder'  => $placeholder,
                'description'  => $description,
            ) );

            return '';
        }

        /**
         * Add a details block for legacy add-on support.
         */
        private function details_block() {
            $this->group_params['header']     = __( 'Details', 'store-locator-le' );
            $this->group_params['group_slug'] = 'details';
            $this->group_params['div_group']  = 'left_side';
            $this->settings->add_group( $this->group_params );

            /**
             * Filter to add HTML to the top of the add/edit location form.
             *
             * @filter     slp_edit_location_right_column
             *
             * @params     string      current HTML
             */
            $details = apply_filters( 'slp_edit_location_right_column', '', $this->settings, $this->group_params );

            if ( ! empty( $details ) ) {
                $this->settings->add_ItemToGroup( array(
                    'group_params' => $this->group_params,
                    'type'         => 'custom',
                    'custom'       => $details,
                    'section'      => $this->section_params['name'],
                ) );
            }

            if ( $this->settings->sections[ $this->section_params['slug'] ]->groups['details']->has_items() ) {
                $this->settings->add_ItemToGroup( array(
                    'group_params' => $this->group_params,
                    'type'         => 'custom',
                    'custom'       => $this->submit_button(),
                ) );
            }
        }

        /**
         * Add extended data to location add/edit form.
         */
        private function extended_data_block() {
            $this->slplus->Admin_Locations->set_active_columns();
            $this->slplus->Admin_Locations->filter_active_columns();
            if ( empty( $this->slplus->Admin_Locations->active_columns ) ) {
                return;
            }

            $data =
                ( (int) $this->slplus->currentLocation->id > 0 ) ?
                    $this->slplus->database->extension->get_data( $this->slplus->currentLocation->id ) :
                    null;

            // For each extended data field, add an item.
            //
            $groups = array();
            foreach ( $this->slplus->Admin_Locations->active_columns as $data_field ) {
                $slug = $data_field->slug;
                $display_type = $this->set_extended_data_display_type( $data_field );
                if ( $display_type === 'none' ) {
                    continue;
                }

                $this->slplus->database->extension->set_options( $slug );

                $group_name = $this->set_extended_data_group( $data_field );

                // Group does not exist, add it to settings.
                //
                if ( ! in_array( $group_name, $groups ) ) {
                    $groups[] = $group_name;

                    $this->group_params['header']       = $group_name;
                    $this->group_params['group_slug']   = sanitize_key( $group_name );
                    $this->group_params['div_group']    = 'left_side';
                    $this->group_params['section_slug'] = $this->section_params['slug'];
                    $this->group_params['plugin']       = $this->slplus;

                    $this->settings->add_group( $this->group_params );

                    // Group exists, only need to set slug
                    //
                } else {
                    $this->group_params['group_slug'] = sanitize_key( $group_name );
                    unset( $this->group_params['header'] );
                }

                // Standard data types
                //
                if ( $display_type !== 'callback' ) {
                    $this->settings->add_ItemToGroup( array(
                        'group_params' => $this->group_params,
                        'label'        => $data_field->label,
                        'id'           => "edit-{$slug}",
                        'data_field'   => $slug,
                        'name'         => "{$slug}-{$this->slplus->currentLocation->id}",
                        'value'        => ( ( is_null( $data ) || ! isset( $data[ $slug ] ) ) ? '' : $data[ $slug ] ),
                        'type'         => $display_type,
                        'description'  => $this->slplus->database->extension->get_option( $data_field->slug, 'help_text' ),
                        'custom'       => $this->slplus->database->extension->get_option( $data_field->slug, 'custom'    ),
                    ) );

                // Callback Display Type
                //
                } else {

                    /**
                     * ACTION:     slp_add_location_custom_display
                     *
                     * Runs when the extended data display type is set to callback.
                     *
                     * @param   SLP_Settings $settings           SLP Settings Interface reference SLPlus->ManageLocations->settings
                     * @param   array[]      $group_params       The metadata needed to build a settings group.
                     * @param   array[]      $data_field         The current extended data field meta.
                     */
                    do_action( 'slp_add_location_custom_display' , $this->settings , $this->group_params , $data_field );
                }
            }

            // For each group - add a submit button pair.
            //
            foreach ( $groups as $group_name ) {
                $this->group_params['group_slug'] = sanitize_key( $group_name );

                if ( $this->settings->sections[ $this->section_params['slug'] ]->groups[ $this->group_params['group_slug'] ]->has_items() ) {
                    $this->settings->add_ItemToGroup( array(
                        'group_params' => $this->group_params,
                        'type'         => 'custom',
                        'custom'       => $this->submit_button(),
                    ) );
                }
            }
        }

        /**
         * Render a map of where the location is (edit only).
         */
        private function map() {
            if ( empty( $this->slplus->currentLocation->latitude ) ) { return; }
            if ( empty( $this->slplus->currentLocation->longitude ) ) { return; }

            $this->group_params['header']     = __( 'Map', 'store-locator-le' );
            $this->group_params['group_slug'] = 'map';
            $this->group_params['div_group']  = 'right_side';
            $this->settings->add_group( $this->group_params );

            $html =
                "<div id='admin_map_location'></div>"
                ;

            $this->settings->add_ItemToGroup( array(
                'group_params' => $this->group_params,
                'type'         => 'custom',
                'show_label'   => false,
                'custom'       => $html,
            ) );

        }

        /**
         * Set the display type.
         *
         * @param    array $data_field
         *
         * @return    string the display_type
         */
        private function set_extended_data_display_type( $data_field ) {
            $display_type = $this->slplus->database->extension->get_option( $data_field->slug, 'display_type' );
            if ( is_null( $display_type ) ) {
                switch ( $data_field->type ) {

                    case 'boolean':
                        $display_type = 'checkbox';
                        break;

                    case 'text':
                        $display_type = 'textarea';
                        break;

                    default:
                        $display_type = 'text';
                        break;
                }
            }

            return $display_type;
        }

        /**
         * Set the SLPlus_Settings group name.
         *
         * @param    array $data_field
         *
         * @return    string        the SLPlus_Settings group name
         */
        private function set_extended_data_group( $data_field ) {
            if (
                is_null( $this->slplus->AddOns ) ||
                empty( $data_field->option_values['addon'] )
            ) {
                $group_name = __( 'Extended Data ', 'store-locator-le' );

            } else {
                $group_name = $this->slplus->AddOns->instances[ $data_field->option_values['addon'] ]->name;
            }

            return $group_name;
        }

        /**
         * Put the add/cancel button on the add/edit locations form.
         *
         * This is rendered AFTER other HTML stuff.
         *
         * @return string HTML of the form inputs
         */
        public function submit_button( ) {
            $edCancelURL = isset( $_REQUEST['id'] ) ?
                preg_replace( '/&id=' . $_REQUEST['id'] . '/', '', $_SERVER['REQUEST_URI'] ) :
                $_SERVER['REQUEST_URI'];
            $alTitle     =
                ( $this->adding ?
                    __( 'Add Location', 'store-locator-le' ) :
                    sprintf( "%s #%d", __( 'Update Location', 'store-locator-le' ), $this->slplus->currentLocation->id )
                );

            $value =
                ( $this->adding ) ?
                    __( 'Add', 'store-locator-le' ) :
                    __( 'Update', 'store-locator-le' );

            $onClick =
                ( $this->adding ) ?
                    "AdminUI.doAction('add' ,'','manualAddForm');" :
                    "AdminUI.doAction('save','','locationForm' );";

            return
                "<div id='slp_form_buttons'>" .
                "<input " .
                "type='submit'        " .
                'value="' . $value . '" ' .
                'onClick="' . $onClick . '" ' .
                "' alt='$alTitle' title='$alTitle' class='button-primary'" .
                ">" .
                "<input type='button' class='button' " .
                "value='" . __( 'Cancel', 'store-locator-le' ) . "' " .
                "onclick='location.href=\"" . $edCancelURL . "\"'>" .
                "<input type='hidden' name='option_value-{$this->slplus->currentLocation->id}' " .
                "value='" . ( $this->adding ? '' : $this->slplus->currentLocation->option_value ) .
                "' />" .
                "</div>";
        }

    }
}