<?php
if ( ! class_exists( 'SLP_Admin_Locations_Actions' ) ) {

    /**
     * Admin Locations Tab Actions Processing
     *
     * @property        boolean                     $all
     * @property-read   string[]                    $locations  The list of locations (IDs) to be processed.
     * @property-read   int                         $offset     Current location offset.
     * @property        SLPlus                      $slplus
     * @property        SLPlus_AdminUI_Locations    $screen     The screen we are processing.
     */
    class SLP_Admin_Locations_Actions extends SLPlus_BaseClass_Object {
        public  $all;
        private $locations;
        private $offset;
        public  $screen;

        /**
         * Add a location.
         *
         * Called for add mode only.
         */
        private function add_location() {
        	$response_code = 'invalid_form';

        	// Add the location based on post data.
	        //
            if ( isset( $_POST['store-'] ) && ! empty( $_POST['store-'] ) ) {
	            $locationData = array();
	            foreach ( $_POST as $key => $sl_value ) {
		            if ( preg_match( '#\-$#', $key ) ) {
			            $fieldName                  = 'sl_' . preg_replace( '#\-$#', '', $key );
			            $locationData[ $fieldName ] = ( ! empty( $sl_value ) ? $sl_value : '' );
		            }
	            }

	            $skipGeocode   =
		            ( isset( $locationData['sl_latitude'] ) && is_numeric( $locationData['sl_latitude'] ) ) &&
		            ( isset( $locationData['sl_longitude'] ) && is_numeric( $locationData['sl_longitude'] ) );
	            $response_code = $this->slplus->currentLocation->add_to_database( $locationData, 'none', $skipGeocode );
            }

	        require_once( SLPLUS_PLUGINDIR . 'include/module/locations/SLP_Location_Manager.php' );
	        $this->slplus->Location_Manager->create_notification( $response_code );
        }

        /**
         * Delete location(s) action.
         */
        private function delete() {
            if ( ! $this->set_locations() ) { return; }

            $id = $this->get_next_location();
            while ( ! is_null( $id ) ) {
                $this->slplus->currentLocation->set_PropertiesViaDB( $id );
                $this->slplus->currentLocation->delete();
                if ( $this->all ) { $this->offset = 0; }
                $id = $this->get_next_location();
            }
        }

        /**
         * Get the next location on the location list.
         *
         * @return mixed|null
         */
        public function get_next_location() {
            if ( $this->all ) {
                $data = $this->slplus->database->get_Record(array('selectslid') , array() , $this->offset++ );
                return ( $data['sl_id'] > 0 ) ? $data['sl_id'] : null;
            } else {
                return ( $this->offset < count( $this->locations) ) ? $this->locations[ $this->offset++ ] : null;
            }
        }

        /**
         * Process any incoming actions.
         *
         * @see http://docs.storelocatorplus.com/plugindevelopment/store-locator-plus-location-actions/
         */
        function process_actions() {
            if ( empty( $this->screen->current_action ) ) {
                return;
            }
            switch ( $this->screen->current_action ) {

                // ADD - the add location form was submitted
                //
                case 'add' :
                    $this->add_location();
                    break;

                // Edit - the manage locations edit button was clicked on a location.
                //
                case 'edit':
                    $_REQUEST['selected_nav_element'] = '#wpcsl-option-edit_location';
                    break;

                // Save - the edit location form was submitted
                //
                case 'save':
                    $this->save_edited_location();
                    $_REQUEST['selected_nav_element'] = '#wpcsl-option-current_locations';
                    break;

                // Delete - manage locations delete a location.
                //
                case 'delete':
                    $this->delete();
                    break;

                // Locations Per Page Action
                //   - update the option first,
                //   - then reload the
                //
                // TODO: Move admin_locations_per_page to WP user meta
                //
                case 'locationsperpage':
                    $newLimit = preg_replace( '/\D/', '', $_REQUEST['admin_locations_per_page'] );
                    if ( ctype_digit( $newLimit ) && (int) $newLimit > 9 ) {
                        $this->slplus->options_nojs['admin_locations_per_page'] = $newLimit;
                        $this->slplus->WPOption_Manager->update_wp_option( 'nojs' );
                    }

                    break;

            }

            /**
             * Hook executes when processing a manage locations action.
             *
             * @action  slp_manage_locations_action
             */
            do_action( 'slp_manage_locations_action' , $this);
        }

        /**
         * Save a location when the edit location form is submitted.
         */
        private function save_edited_location() {
            if ( ! $this->slplus->currentLocation->isvalid_ID( null, 'locationID' ) ) {
                return;
            }
            $this->slplus->notifications->delete_all_notices();

            // Get our original address first
            //
            $this->slplus->currentLocation->set_PropertiesViaDB( $_REQUEST['locationID'] );
            $priorIsGeocoded =
                is_numeric( $this->slplus->currentLocation->latitude ) &&
                is_numeric( $this->slplus->currentLocation->longitude );
            $priorAddress    =
                $this->slplus->currentLocation->address . ' ' .
                $this->slplus->currentLocation->address2 . ', ' .
                $this->slplus->currentLocation->city . ', ' .
                $this->slplus->currentLocation->state . ' ' .
                $this->slplus->currentLocation->zip;

            // Update The Location Data
            //
            foreach ( $_POST as $key => $value ) {
                if ( preg_match( '#\-' . $this->slplus->currentLocation->id . '#', $key ) ) {
                    $slpFieldName = preg_replace( '#\-' . $this->slplus->currentLocation->id . '#', '', $key );
                    if ( ( $slpFieldName === 'latitude' ) || ( $slpFieldName === 'longitude' ) ) {
                        if ( ! is_numeric( $value ) ) {
                            continue;
                        }
                    }

                    // Has the data changed?
                    //
                    $stripped_value = stripslashes_deep( $value );
                    if ( $this->slplus->currentLocation->$slpFieldName !== $stripped_value ) {
                        $this->slplus->currentLocation->$slpFieldName = $stripped_value;
                        $this->slplus->currentLocation->dataChanged   = true;
                    }
                }
            }

            // Missing Checkboxes (private)
            //
            if ( $this->slplus->currentLocation->private && ! isset( $_POST["private-{$this->slplus->currentLocation->id}"] ) ) {
                $this->slplus->currentLocation->private     = false;
                $this->slplus->currentLocation->dataChanged = true;
            }

            // RE-geocode if the address changed
            // or if the lat/long is not set
            //
            $newAddress =
                $this->slplus->currentLocation->address . ' ' .
                $this->slplus->currentLocation->address2 . ', ' .
                $this->slplus->currentLocation->city . ', ' .
                $this->slplus->currentLocation->state . ' ' .
                $this->slplus->currentLocation->zip;
            if ( ( $newAddress != $priorAddress ) || ! $priorIsGeocoded ) {
                $this->slplus->currentLocation->do_geocoding( $newAddress );
            }

            // Extended Data Boolean Check
            //
            $this->slplus->currentLocation->dataChanged = $this->set_extended_data_booleans() || $this->slplus->currentLocation->dataChanged;


            /**
             * HOOK: slp_location_save
             *
             * Executes when a location save action is called from manage locations.
             *
             * @action slp_location_save
             */
            do_action( 'slp_location_save' );
            if ( $this->slplus->currentLocation->dataChanged ) {
                $this->slplus->currentLocation->MakePersistent();
            }

            /**
             * HOOK: slp_location_saved
             *
             * Executes after a location has been saved from the manage locations interface.  After EDIT only!
             *
             * @action slp_location_saved
             *
             */
            do_action( 'slp_location_saved' );
        }

        /**
         * Set extended data booleans.
         *
         * @return  boolean     True if any of these fields changed.
         */
        private function set_extended_data_booleans() {
            $something_changed = false;
            $this->screen->set_active_columns();
            foreach ( $this->screen->active_columns as $extraColumn ) {
                $slug = $extraColumn->slug;
                if ( $extraColumn->type === 'boolean' ) {
                    $new_setting = empty( $_REQUEST[ $slug . '-' . $this->slplus->currentLocation->id ] ) ? '0' : '1';
                    if ( $this->slplus->currentLocation->exdata[$slug] !== $new_setting ) {
                        $this->slplus->currentLocation->exdata[ $slug ] = $new_setting;
                        $something_changed = true;
                    }
                }
            }
            return $something_changed;
        }

        /**
         * Set the location list.
         *
         * @return  boolean        True if apply_to_all or the location id(s) array is set.  False if not doing any locations.
         */
        public function set_locations() {
            $this->offset = 0;
            $this->all = ( isset( $_REQUEST['apply_to_all'] ) && ( $_REQUEST['apply_to_all'] === '1' ) );
            if ( $this->all ) { return true; }

            if ( isset( $_REQUEST['sl_id']  ) ) { $this->locations = (array) $_REQUEST['sl_id']; return true; }
            if ( isset( $_REQUEST['id']     ) ) { $this->locations = (array) $_REQUEST['id']; return true; }

            return false;
        }
    }

	/**
	 * @var SLPlus $slplus
	 */
	global $slplus;
	if ( is_a( $slplus, 'SLPlus' ) ) {
		$slplus->add_object( new SLP_Admin_Locations_Actions() );
	}
}