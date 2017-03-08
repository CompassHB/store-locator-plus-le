// Setup the Location Manager namespace
var SLP_Location_Manager = SLP_Location_Manager || {};

/**
 * Table Class
 */
var SLP_Locations_table = function () {

    /**
     * Initialize the table header.
     */
    this.initialize = function () {
        jQuery('a.delete_location').click( this.delete_location );
    }

    /**
     * Delete Location button
     */
    this.delete_location = function ( event ) {
      //  event.preventDefault();
        var post_data = new Object();
        post_data['action']      = 'slp_delete_location';
        post_data['location_id']  = jQuery( this ).attr( 'data-id' );

        var tr = jQuery( '#location-' + post_data['location_id'] );
        tr.css( "background-color" , "#FFF4DD" );
        tr.fadeOut( 400, function() { tr.remove(); } );

        jQuery.post( ajaxurl, post_data , this.process_delete_location_response , 'json' );
    }

    /**
     * Proces the delete location response.
     */
    this.process_delete_location_response = function ( response ) {
        var json_response = JSON.parse( response );
        if ( json_response.status !== 'ok' ) {
            alert('Location not deleted.');
        }
    }
}

/**
 * Table Header Class
 */
var SLP_Locations_table_header = function () {

        /**
         * Initialize the table header.
         */
        this.initialize = function () {
            jQuery('#admin_locations_per_page').change( this.set_locations_per_page );
            jQuery('#do_action_apply_to_all').click( this.execute_apply_to_all );
            jQuery('#do_action_apply').click( this.execute_apply );
        }

        /**
         * Run the apply button.
         */
        this.execute_apply = function() {
            SLP_Location_Manager.table_header.execute_apply_bulk_action( false );
        }

        /**
         * Run the apply to all button.
         */
        this.execute_apply_to_all = function() {
            SLP_Location_Manager.table_header.execute_apply_bulk_action( true );
        }

        /**
         * Run the apply to all button.
         */
        this.execute_apply_bulk_action = function( all ) {
            var action_val = jQuery('#actionType').val();
            if ( action_val === '-1' ) { return false; }
            var action_text = jQuery('#actionType option:selected').text();
            if ( confirm( 'Are you sure you want to ' + action_text + '?' ) ) {
                if ( all ) {
                    jQuery('<input />').attr('type', 'hidden')
                        .attr('name', "apply_to_all")
                        .attr('value', "1")
                        .appendTo('#locationForm');
                }
                AdminUI.doAction( action_val );
            }
            return false;
        }


        /**
         * Hide Column On Drag Stop
         */
        this.hide_column = function ( event , ui ) {
            var post_data = new Object();
            post_data['action']     = 'slp_hide_column';
            post_data['user_id']    = location_manager.user_id;
            post_data['data_field'] = ui.draggable.attr('data-field');

            var data_fld_selector = '[data-field="' + post_data['data_field'] + '"]';
            var column = SLP_Location_Manager.table.column( data_fld_selector  );
            column.visible( ! column.visible() );

            jQuery.post( ajaxurl, post_data , this.process_hide_column_response );
        }

        /**
         * Proces the hide column response.
         */
        this.process_hide_column_response = function ( response ) {
        }

        /**
         * Set locations per page.
         */
        this.set_locations_per_page = function( ) {
            if ( jQuery( '#admin_locations_per_page').val() > 500 ) {
                if ( ! confirm( SLP_ADMIN_settings['text_location_warning'] ) ) { return false; }
            }
            AdminUI.doAction('locationsperpage','','locationForm');
        }

        /**
         * Unhide Column On Click
         */
        this.unhide_column = function ( event ) {
            var post_data = new Object();
            post_data['action']     = 'slp_unhide_column';
            post_data['user_id']    = location_manager.user_id;
            post_data['data_field'] = jQuery(this).attr('data-field');

            var data_fld_selector = '[data-field="' + post_data['data_field'] + '"]';
            jQuery('th' + data_fld_selector ).show();
            jQuery('td' + data_fld_selector ).show();
            jQuery('span.unhider' + data_fld_selector ).hide();

            jQuery.post( ajaxurl, post_data , this.process_hide_column_response );

        }

    }

/**
 * Admin Locator Page Maps
 *
 * @constructor
 */
var SLP_Locations_map = function () {
    var map;
    var marker;

    /**
     * Initialize the map , only if location ID is set.
     */
    this.initialize = function () {
        if ( ! jQuery( '#id' ).val() ) { return; }
        if ( ! jQuery( '[data-field="latitude"]' ).val() ) { return; }
        if ( ! jQuery( '[data-field="longitude"]' ).val() ) { return; }

        var center = new google.maps.LatLng( jQuery( '[data-field="latitude"]' ).val() ,  jQuery( '[data-field="longitude"]' ).val() );

        var map_options = {
            center: center,
            disableDefaultUI: true,
            draggable: true,
            disableDoubleClickZoom: true,
            keyboardShortcuts: false,
            zoom: 14,
        };
        this.map = new google.maps.Map( document.getElementById('admin_map_location') , map_options );

        var marker_image =  jQuery('#edit-marker').val();
        var marker_options = {
            position: center,
            map: this.map,
            title: jQuery('[data-field="store"]').val(),
            icon:  marker_image,
        };
        this.marker =  new google.maps.Marker( marker_options );

        // FILTER: location_map_initialized
        // Fires after the location map has been initialized
        //
        slp_AdminFilter( 'location_map_initialized' ).publish();
    }

    this.add_marker_at_center = function () {
            this.centerMarker = new slp_Marker(this, '', this.homePoint, this.mapHomeIconUrl);
    };
}

/*
 * Location Details
 */
var SLP_Location_Details = function () {

    /**
     * Initialize the UX
     */
    this.initialize = function () {
        jQuery('tr.slp_managelocations_row').mouseenter( function() {
            SLP_Location_Manager.Details.show_location_details( jQuery( this ) );
        });
    }

    /**
     * Show the location details.
     */
    this.show_location_details = function( table_row ) {
        var location_cells = table_row.find(".slp_manage_locations_cell");

        var content = '';
        for ( var cnt = 0 , len = location_cells.length; cnt < len; cnt++ ) {
             if ( location_cells[ cnt ] ) {
                 content += '<span class="location_detail">' + location_cells[ cnt ].innerHTML + '</span>';
             }
        }

        content += table_row.find('.actions').html();

        jQuery('.settings-description').toggleClass('is-visible').html( content );

    }

    /**
     * Clear the more info box.
     */
    this.clear_more_info = function() {
        jQuery('.settings-description').toggleClass('is-visible').html('');
    }
}

/**
 * Locations Tab Admin JS
 */
jQuery(document).ready(
    function() {
        SLP_Location_Manager.table = new SLP_Locations_table();
        SLP_Location_Manager.table.initialize();


        SLP_Location_Manager.table_header = new SLP_Locations_table_header();
        SLP_Location_Manager.table_header.initialize();

        var dataTable_options = new Object();
        dataTable_options['stripeClasses'] = [];
        dataTable_options['paging'] = false;
        dataTable_options['searching'] = false;
        dataTable_options['responsive'] = true;
        dataTable_options['colReorder'] = true;
        dataTable_options['columnDefs'] = [
            { targets: [0,1] , visible: true , orderable: false },
            { targets: '_all' , visible: true , searchable: false, orderable: true },
        ];

        // Options: Full Data Set Displayed
        if (location_manager.all_displayed) {
            dataTable_options['ordering'] = true;

            // Options: Partial Data Set Displayed
        } else {
            dataTable_options['ordering'] = false;
        }

        // Manage Locations Table : DataTable Handler
        //
        SLP_Location_Manager.table = jQuery('#manage_locations_table').DataTable(dataTable_options);

        // Droppable Divs
        //
        var droppable_options = new Object();
        droppable_options['activeClass'] = 'drop_activated';
        droppable_options['tolerance'  ] = 'pointer';
        droppable_options['drop'       ]   = SLP_Location_Manager.table_header.hide_column;
        jQuery('#column_hider').droppable( droppable_options );

        // Draggable Headers
        //
        var draggable_options = new Object();
        draggable_options['revert'] = 'invalid';
        jQuery('th.manage-column').draggable( draggable_options );

        // Unhider links
        //
        jQuery('span.unhider').on( 'click' , SLP_Location_Manager.table_header.unhide_column );

        // No locations, show add form.
        //
        if ( jQuery('#wpcsl-option-current_locations div.section_description').is(':empty') ) {
            jQuery('#wpcsl-option-add_location_sidemenu').click();
        }

        // Location Map
        //
        SLP_Location_Manager.locations_map = new SLP_Locations_map();
        SLP_Location_Manager.locations_map.initialize();

        // Location Details
        //
        SLP_Location_Manager.Details = new SLP_Location_Details();
        SLP_Location_Manager.Details.initialize();
    }
);
