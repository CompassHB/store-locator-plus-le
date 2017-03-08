<?php
if (! class_exists('SLP_AJAX_Location_Manager')) {


    /**
     * Handle the AJAX location_manager requests.
     *
     * @property    SLP_AJAX        $ajax
     *
     * @package StoreLocatorPlus\Extension\AJAX\Location_Manager
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2015 Charleston Software Associates, LLC
     */
    class SLP_AJAX_Location_Manager extends SLPlus_BaseClass_Object {
	    public $ajax;

	    /**
	     * Delete a single location.
	     */
	    function delete_location() {
		    $this->slplus->currentLocation->set_PropertiesViaDB( $this->ajax->query_params['location_id'] );

		    $status = $this->slplus->currentLocation->delete();
		    if ( is_int( $status ) ) {
			    $count = $status;
			    $status = 'ok';
		    } else {
		    	$count = '0';
			    $status = 'error';
		    }


		    $response = array(
		    	'status'       => $status,
			    'count'        => $count,
		    	'action'      => 'delete_location',
		    	'location_id' => $this->ajax->query_params['location_id'],
		    );

		    wp_die( json_encode( $response ) );
	    }

	    /**
	     * Remember a users's hidden columns.
	     */
		function hide_column() {

			// Get previously hidden columns
			//
			$previously_hidden_columns = get_user_meta(
				$this->ajax->query_params['user_id']  ,
				'slp_hidden_location_manager_columns' ,
				true
				);
			$previously_hidden_columns = maybe_unserialize( $previously_hidden_columns );

			$current_hidden_columns = (array) $previously_hidden_columns;
			$current_hidden_columns[] = $this->ajax->query_params['data_field'];

			// Make persistent
			update_user_meta(
				$this->ajax->query_params['user_id']    ,
				'slp_hidden_location_manager_columns'   ,
				$current_hidden_columns                 ,
				$previously_hidden_columns
				);

			$data = array(
				'user_id' => $this->ajax->query_params['user_id'],
				'hidden_columns' => $current_hidden_columns
				);

			header( "Content-Type: application/json" );
			echo json_encode( $data );
			wp_die();
		}

	    /**
	     * Remove a hidden column from the user's hidden column list.
	     */
	    function unhide_column() {

		    // Get previously hidden columns
		    //
		    $previously_hidden_columns = get_user_meta(
			    $this->ajax->query_params['user_id']  ,
			    'slp_hidden_location_manager_columns' ,
			    true
		    );
		    $previously_hidden_columns = maybe_unserialize( $previously_hidden_columns );
		    $current_hidden_columns = $previously_hidden_columns;

		    if ( ( $key = array_search( $this->ajax->query_params['data_field'] , $current_hidden_columns ) ) !== false ) {
			    unset( $current_hidden_columns[$key] );
		    }

		    // Make persistent
		    update_user_meta(
			    $this->ajax->query_params['user_id']    ,
			    'slp_hidden_location_manager_columns'   ,
			    $current_hidden_columns                 ,
			    $previously_hidden_columns
		    );

		    $data = array(
			    'user_id' => $this->ajax->query_params['user_id'],
			    'hidden_columns' => $current_hidden_columns
		    );

		    header( "Content-Type: application/json" );
		    echo json_encode( $data );
		    wp_die();
	    }

    }
}