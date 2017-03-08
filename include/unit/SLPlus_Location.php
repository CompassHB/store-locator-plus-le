<?php

if ( ! class_exists( 'SLPlus_Location' ) ) {

	/**
	 * Store Locator Plus location interface and management class.
	 *
	 * Make a location an in-memory object and handle persistence via data I/O to the MySQL tables.
	 *
	 * FIELDS:
	 * @property int             $id
	 * @property string          $store                  the store name
	 * @property string          $address
	 * @property string          $address2
	 * @property string          $city
	 * @property string          $state
	 * @property string          $zip
	 * @property string          $country
	 * @property string          $latitude
	 * @property string          $longitude
	 * @property string          $tags
	 * @property string          $description
	 * @property string          $email
	 * @property string          $url
	 * @property string          $hours
	 * @property string          $phone
	 * @property string          $fax
	 * @property string          $image
	 * @property boolean         $private
	 * @property string          $neat_title
	 * @property int             $linked_postid
	 * @property string          $pages_url
	 * @property boolean         $pages_on
	 * @property string          $option_value
	 * @property datetime        $lastupdated
	 * @property float           $initial_distance
	 *
	 * EXTENDED DATA FIELDS:
	 * @property mixed[]         $exdata                 - the extended data fields
	 *
	 * DESERIALIZED DATA:
	 * @property        mixed[]  $settings               The deserialized option_value field
	 * @property        mixed[]  $attributes             The deserialized option_value field. This can be augmented by
	 *                  multiple add-on packs. Tagalong adds: array[] ['store_categories'] int[] ['stores']
	 *
	 * PAGE RELATIONS:
	 * @property        mixed[]  $pageData               The related store_page custom post type properties.
	 *                                                   WordPress Standard Custom Post Type Features:
	 *                                                     int    ['ID']          - the WordPress page ID
	 *                                                     string ['post_type']   - always set to this.PageType
	 *                                                     string ['post_status'] - current post status, 'draft',
	 *                                                     'published' string ['post_title']  - the title for the page
	 *                                                     string ['post_content']- the page content, defaults to blank
	 *
	 *                                                   Store Pages adds:
	 *                                                      post_content attribute is loaded with auto-generated HTML
	 *                                                      content
	 *
	 *                                                   Tagalong adds:
	 *                                                      mixed[] ['tax_input'] - the custom taxonomy values for this
	 *                                                      location
	 *
	 * @property-read   string   $pageDefaultStatus      The default page status
	 *
	 * DB META:
	 * @property-read   string   $dbFieldPrefix          The database field prefix for locations
	 * @property-read   string[] $dbFields               An array of properties that are in the db table
	 *
	 * FUNCTIONALITY:
	 * @property-read   int      $count                  How many locations have processed for geocoding this session.
	 * @property        boolean  $dataChanged            True if the location data has changed. Used to manage the
	 *                  MakePersistent method, if false do not write to disk.
	 * @property-read   wpdb     $db                     The WPDB connection.
	 * @property-read   string[] $dbfields               Array of locator table field names.
	 * @property-read   int      $delay                  How long to wait between geocoding requests.
	 * @property-read   boolean  $geocodeIssuesRendered
	 * @property        boolean  $geocodeSkipOKNotices   If true do not show valid geocodes.
	 * @property-read   string   $geocodeURL             The URL of the geocoding service.
	 * @property-read   int      $iterations             How many times to retry an address.
	 * @property-read   mixed[]  $locationData           Remember the last location data array passed into set properties
	 *                  via DB.
	 * @property-read   int      $retry_maximum_delayms  Maxmium delay in milliseconds.
	 * @property        bool     $validate_fields        Validate fields? no by default.
	 * @property        SLPlus   $slplus                 The parent plugin object
	 */
	class SLPlus_Location {

		const StartingDelay = 2000000;

		private $id;
		private $store;
		private $address;
		private $address2;
		private $city;
		private $state;
		private $zip;
		private $country;
		private $latitude;
		private $longitude;
		private $tags;
		private $description;
		private $email;
		private $url;
		private $hours;
		private $phone;
		private $fax;
		private $image;
		private $private;
		private $neat_title;
		private $linked_postid;
		private $pages_url;
		private $pages_on;
		private $option_value;
		private $lastupdated;
		private $initial_distance;

		public $exdata = array();

		public  $attributes;
		private $count                 = 0;
		public  $dataChanged           = true;
		private $db;
		private $delay                 = SLPlus_Location::StartingDelay;
		private $dbFields              = array(
			'id',
			'store',
			'address',
			'address2',
			'city',
			'state',
			'zip',
			'country',
			'latitude',
			'longitude',
			'tags',
			'description',
			'email',
			'url',
			'hours',
			'phone',
			'fax',
			'image',
			'private',
			'neat_title',
			'linked_postid',
			'pages_url',
			'pages_on',
			'option_value',
			'lastupdated',
			'initial_distance'
		);
		private $dbFieldPrefix         = 'sl_';
		private $geocodeIssuesRendered = false;
		public  $geocodeSkipOKNotices  = false;
		private $geocodeURL;
		private $locationData;
		private $pageData;
		private $pageDefaultStatus;
		private $iterations;
		private $retry_maximum_delayms = 5000000;
		public  $validate_fields       = false;
		private $slplus;

		/**
		 * Initialize a new location
		 *
		 * @param mixed[] $params - a named array of the plugin options.
		 */
		public function __construct( $params ) {
			foreach ( $params as $property => $value ) {
				$this->$property = $value;
			}
			global $wpdb;
			$this->db = $wpdb;

			$this->geocodeIssuesRendered = defined( 'DOING_CRON' ); // turn off this message for cron

			// Set gettext default properties.
			//
			$this->pageDefaultStatus = __( 'draft', 'store-locator-le' );
		}

		/**
		 * Fetch a location property from the valid object properties list.
		 *
		 * $currentLocation = new SLPlus_Location();
		 * print $currentLocation->id;
		 *
		 * @param mixed $property - which property to set.
		 *
		 * @return null
		 */
		public function __get( $property ) {
			if ( property_exists( $this, $property ) ) {
				return $this->$property;
			}
			if (
				$this->slplus->database->has_extended_data() &&
				isset( $this->exdata[ $property ] )
			) {
				return $this->exdata[ $property ];
			}

			return null;
		}

		/**
		 * Allow isset to be called on private properties.
		 *
		 * @param $property
		 *
		 * @return bool
		 */
		public function __isset( $property ) {
			return isset( $this->$property );
		}

		/**
		 * Set a location property in the valid object properties list to the given value.
		 *
		 * $currentLocation = new SLPlus_Location();
		 * $currentLocation->store = 'My Place';
		 *
		 * @param mixed $property
		 * @param mixed $value
		 *
		 * @return \SLPlus_Location
		 */
		public function __set( $property, $value ) {
			if ( property_exists( $this, $property ) ) {

				// Latitude hard-wired callback
				//
				if  ( $property === 'latitude' ) {
					$this->set_LatLong( $value , null );

				// Longitude hard-wired callback
				//
				} elseif ( $property === 'longitude' )  {
					$this->set_LatLong( null , $value  );

				// All other standard properties
				//
				} else {
					$this->$property = $value;

				}
			}

			// Extended Data, allow property as long as it does not conflict
			// with a built-in property.
			//
			if (
				! property_exists( $this, $property ) &&
				$this->slplus->database->is_Extended() &&
				$this->slplus->database->extension->has_field( $property )
			) {
				$this->exdata[ $property ] = $value;
			}

			return $this;
		}

		/**
		 * Add an address into the SLP locations database.
		 *
		 * NOTE: Only saves PRIMARY data fields.  DOES NOT save extended data.   Use the slp_location_added action.
		 *
		 * duplicates_handling can be:
		 * o none = ignore duplicates
		 * o skip = skip duplicates
		 * o update = update duplicates
		 *
		 * Returns:
		 * o added = new location added
		 * o location_exists = store id provided and not in update mode
		 * o not_updated = existing location not updated
		 * o skipped = duplicate skipped
		 * o updated = existing location updated
		 *
		 * @param array[] $locationData
		 * @param string  $duplicates_handling
		 * @param boolean $skipGeocode
		 *
		 * @return string
		 *
		 */
		public function add_to_database( $locationData, $duplicates_handling = 'none', $skipGeocode = false ) {

			/**
			 * FILTER: slp_add_location_init_code
			 */
			$return_code = apply_filters( 'slp_add_location_init_code' , '' );
			if ( ! empty( $return_code ) ) {
				return $return_code;
			}
			
			$add_mode = ( $duplicates_handling === 'add' );

			// Add Mode : skip lots of duplication checking stuff
			//
			if ( $add_mode ) {
				$locationData['sl_id'] = null;
				$return_code           = 'added';

				// Update, skip, etc. modes need to check for duplicates
				//
			} else {

				// Make sure locationData['sl_id'] is set to SOMETHING.
				//
				if ( ! isset( $locationData['sl_id'] ) ) {
					$locationData['sl_id'] = null;
				}

				// If the incoming location ID is of a valid format...
				// Go fetch that location record.
				// This also ensures that ID actually exists in the database.
				//
				if ( $this->slplus->currentLocation->isvalid_ID( $locationData['sl_id'] ) ) {
					$this->slplus->currentLocation->set_PropertiesViaDB( $locationData['sl_id'] );
					$locationData['sl_id'] = $this->slplus->currentLocation->id;

					// Not a valid incoming ID, reset current location.
					//
				} else {
					$this->slplus->currentLocation->reset();
				}

				// If the location ID is not valid either because it does not exist
				// in the database or because it was not provided in a valid format,
				// Go see if the location can be found by name + address
				//
				if ( ! $this->slplus->currentLocation->isvalid_ID() ) {
					$locationData['sl_id'] = $this->slplus->db->get_var(
						$this->slplus->db->prepare(
							$this->slplus->database->get_SQL( 'selectslid' ) .
							'WHERE ' .
							'sl_store   = %s AND ' .
							'sl_address = %s AND ' .
							'sl_address2= %s AND ' .
							'sl_city    = %s AND ' .
							'sl_state   = %s AND ' .
							'sl_zip     = %s AND ' .
							'sl_country = %s     '
							,
							$this->val_or_blank( $locationData, 'sl_store' ),
							$this->val_or_blank( $locationData, 'sl_address' ),
							$this->val_or_blank( $locationData, 'sl_address2' ),
							$this->val_or_blank( $locationData, 'sl_city' ),
							$this->val_or_blank( $locationData, 'sl_state' ),
							$this->val_or_blank( $locationData, 'sl_zip' ),
							$this->val_or_blank( $locationData, 'sl_country' )
						)
					);
				}

				// Location ID exists, we have a duplicate entry...
				//
				if ( $this->slplus->currentLocation->isvalid_ID( $locationData['sl_id'] ) ) {
					if ( $duplicates_handling === 'skip' ) {
						return 'skipped';
					}

					// array ID and currentLocation ID do not match,
					// must have found ID via address lookup, go load up the currentLocation record
					//
					if ( $locationData['sl_id'] != $this->slplus->currentLocation->id ) {
						$this->slplus->currentLocation->set_PropertiesViaDB( $locationData['sl_id'] );
					}

					$return_code = 'updated';

					// Location ID does not exist, we are adding a new record.
					//
				} else {
					$duplicates_handling = 'add';
					$return_code         = 'added';
				}

				// Update mode and we are NOT skipping the geocode process,
				// check that the address has changed first.
				//
				if ( ! $skipGeocode && ( $duplicates_handling === 'update' ) ) {
					$skipGeocode =
						( $this->val_or_blank( $locationData, 'sl_address' ) == $this->slplus->currentLocation->address ) &&
						( $this->val_or_blank( $locationData, 'sl_address2' ) == $this->slplus->currentLocation->address2 ) &&
						( $this->val_or_blank( $locationData, 'sl_city' ) == $this->slplus->currentLocation->city ) &&
						( $this->val_or_blank( $locationData, 'sl_state' ) == $this->slplus->currentLocation->state ) &&
						( $this->val_or_blank( $locationData, 'sl_zip' ) == $this->slplus->currentLocation->zip ) &&
						( $this->val_or_blank( $locationData, 'sl_country' ) == $this->slplus->currentLocation->country );
				}

				// If the address matches, make sure we retain the existing lat/long.
				//
				if ( $skipGeocode ) {
					if (
						! isset( $locationData['sl_latitude'] ) ||
						! $this->slplus->currentLocation->is_valid_lat( $locationData['sl_latitude'] )
					) {
						$locationData['sl_latitude'] = $this->slplus->currentLocation->latitude;
					}
					if (
						! isset( $locationData['sl_longitude'] ) ||
						! $this->slplus->currentLocation->is_valid_lng( $locationData['sl_longitude'] )
					) {
						$locationData['sl_longitude'] = $this->slplus->currentLocation->longitude;
					}
				}
			}

			// Set the current location data
			//
			// In update duplicates mode this will not obliterate existing settings
			// it will augment them.  To set a value to blank for an existing record
			// it must exist in the column data and be set to blank.
			//
			// Non-update mode, it starts from a blank slate.
			//
			$this->slplus->currentLocation->set_PropertiesViaArray( $locationData, $duplicates_handling );

			// HOOK: slp_location_add
			//
			do_action( 'slp_location_add' );

			// Geocode the location
			//
			if ( ! $skipGeocode ) {
				$this->do_geocoding();
			}

			// Write to disk
			//
			if ( $this->dataChanged ) {
				$this->MakePersistent();

				// Set not updated return code.
				//
			} else {
				$return_code = 'not_updated';
			}

			// HOOK: slp_location_added
			//
			do_action( 'slp_location_added' );

			return $return_code;
		}

		/**
		 * Create or update the custom store_page page type for this location.
		 *
		 * Set MakePersistent to false if you are going to manage the persistent store later.
		 * You can check $this->dataChanged to see if the data is dirty to determine whether or not persistence might be
		 * needed.
		 *
		 * @param boolean $MakePersistent if true will write the location to disk if the linked_postid was changed.
		 *
		 * @return int return the page ID linked to this location.
		 */
		public function crupdate_Page( $MakePersistent = true ) {

			// Setup the page properties.
			//
			$this->set_PageData();

			// Update an existing page.
			//
			if ( intval( $this->linked_postid ) > 0 ) {
				$touched_pageID = wp_update_post( $this->pageData );
				$update_ok      = ( $touched_pageID > 0 );

				// Create a new page.
			} else {
				$touched_pageID = wp_insert_post( $this->pageData, true );
				$update_ok      = ! is_wp_error( $touched_pageID );
			}

			// Ok - we are good...
			//
			if ( $update_ok ) {

				// If we created a page or changed the page ID,
				// set it in our location property and make it
				// persistent.
				//
				if ( $touched_pageID != $this->linked_postid ) {
					$this->linked_postid = $touched_pageID;
					$this->pages_url     = get_permalink( $this->linked_postid );
					$this->dataChanged   = true;

					add_post_meta( $this->linked_postid, 'slp_location_id', $this->id, true );

					if ( $MakePersistent ) {
						$this->MakePersistent();
					}
				}

				// We got an error... oh shit...
				//
			} else {
				$create_or_update = intval( $this->linked_postid > 0 ) ? __( 'update', 'store-locator-le' ) : __( 'create', 'store-locator-le' );
				$this->slplus->notifications->add_notice( 'error', sprintf( __( 'Could not %s the custom page for this location.', 'store-locator-le' ), $create_or_update ) );
			}

			return $this->linked_postid;
		}

		/**
		 * Calculate the initial distance from the map center point.
		 */
		public function calculate_initial_distance() {

			// Check Map Center Lat / Lng
			//
			if ( ! $this->is_valid_lat( $this->slplus->SmartOptions->map_center_lat->value ) ) {
				return;
			}
			if ( ! $this->is_valid_lng( $this->slplus->SmartOptions->map_center_lng->value ) ) {
				return;
			}

			// Check This Location Lat / Lng
			//
			if ( ! $this->is_valid_lat() ) {
				return;
			}
			if ( ! $this->is_valid_lng() ) {
				return;
			}

			$earth_radius           = ( $this->slplus->SmartOptions->distance_unit->value === 'miles' ) ? SLPlus::earth_radius_mi : SLPlus::earth_radius_km;
			$this->initial_distance = ( $earth_radius * acos( cos( deg2rad( $this->slplus->SmartOptions->map_center_lat->value ) ) * cos( deg2rad( $this->latitude ) ) * cos( deg2rad( $this->longitude ) - deg2rad( $this->slplus->SmartOptions->map_center_lng->value ) ) + sin( deg2rad( $this->slplus->SmartOptions->map_center_lat->value ) ) * sin( deg2rad( $this->latitude ) ) ) );
		}

		/**
		 * Decode a string from URL-safe base64.
		 *
		 * @param $value
		 *
		 * @return string
		 */
		private function decode_Base64UrlSafe( $value ) {
			return base64_decode( str_replace( array( '-', '_' ), array( '+', '/' ), $value ) );
		}

		/**
		 * GeoCode a given location, updating the slplus_plugin currentLocation object lat/long.
		 *
		 * Writing to disk is to be handled by the calling function.
		 *
		 * slplus_plugin->currentLocation->dataChanged is set to true if the lat/long is updated.
		 *
		 * @param string $address the address to geocode, if not set use currentLocation
		 */
		public function do_geocoding( $address = null ) {

			// Null address, build from current location
			//
			if ( $address === null ) {
				$address =
					$this->address . ' ' .
					$this->address2 . ' ' .
					$this->city . ' ' .
					$this->state . ' ' .
					$this->zip . ' ' .
					$this->country;
			}
			$address = trim( $address );

			// Only process non-empty addresses.
			//
			if ( ! empty( $address ) ) {
				$this->count ++;
				if ( $this->count === 1 ) {
					$this->retry_maximum_delayms = (int) $this->slplus->options_nojs['retry_maximum_delay'] * 1000000;
					$this->iterations            = max( 1, (int) $this->slplus->options_nojs['geocode_retries'] );
				}

				$errorMessage = '';

				// Get lat/long from Google
				//
				$json_response = $this->get_LatLong( $address );
				if ( ! empty( $json_response ) ) {

					// Process the data based on the status of the JSON response.
					//
					$json = json_decode( $json_response );
					if ( $json === null ) {
						$json = json_decode( json_encode( array( 'status' => 'ERROR', 'message' => $json_response ) ) );
					}

					switch ( $json->{'status'} ) {

						// OK
						// Geocode completed successfully
						// Update the lat/long if it has changed.
						//
						case 'OK':
							$this->set_LatLong( $json->results[0]->geometry->location->lat, $json->results[0]->geometry->location->lng );
							$this->delay = SLPlus_Location::StartingDelay;
							break;

						// OVER QUERY LIMIT
						// Google is getting to many requests from this IP block.
						// Loop through for X retries.
						//
						case 'OVER_QUERY_LIMIT':
							$errorMessage .= sprintf( __( "Address %s (%d in current series) hit the Google query limit.\n", 'store-locator-le' ),
									$address,
									$this->count
							                 ) . '<br/>';
							$attempts   = 1;
							$totalDelay = 0;

							// Keep trying up until the user-selected number of retries.
							// Increase the wait between each try by 1 second.
							// Wait no more than 10 seconds between attempts.
							//
							while ( $attempts ++ < $this->iterations ) {
								if ( $this->delay <= $this->retry_maximum_delayms + 1 ) {
									$this->delay += 1000000;
								}
								$totalDelay += $this->delay;
								usleep( $this->delay );
								$json = $this->get_LatLong( $address );
								if ( $json !== null ) {
									$json = json_decode( $json );
									if ( $json->{'status'} === 'OK' ) {
										$this->set_LatLong( $json->results[0]->geometry->location->lat, $json->results[0]->geometry->location->lng );
									}
								} else {
									break;
								}
							}
							$errorMessage .= sprintf(
								                 __( 'Waited up to %4.2f seconds between request, total wait for this location was %4.2f seconds.', 'store-locator-le' ),
								                 $this->delay / 1000000,
								                 $totalDelay / 1000000
							                 ) .
							                 "\n<br>";
							$errorMessage .= sprintf(
								                 __( '%d total attempts for this location.', 'store-locator-le' ),
								                 $attempts - 1
							                 ) .
							                 "\n<br>";
							break;

						// ZERO RESULTS
						// Bad address provided or nothing found on Google end.
						//
						case 'ZERO_RESULTS':
							$errorMessage .= sprintf( __( "Address #%d : %s <font color=red>failed to geocode</font>.", 'store-locator-le' ),
									$this->id,
									$address
							                 ) . "<br />\n";
							$errorMessage .= sprintf( __( "Unknown Address! Received status %s.", 'store-locator-le' ), $json->{'status'} ) . "\n<br>";
							$this->delay = SLPlus_Location::StartingDelay;
							break;

						// GENERIC
						// Could not geocode.
						//
						default:
							$errorMessage .=
								sprintf( __( "Address #%d : %s <font color=red>failed to geocode</font>.", 'store-locator-le' ),
									$this->id,
									$address ) .
								"<br/>\n" .
								sprintf( __( "Received status %s.", 'store-locator-le' ),
									$json->{'status'} ) .
								"<br/>\n" .
								sprintf( __( "Received data %s.", 'store-locator-le' ),
									'<pre>' . print_r( $json, true ) . '</pre>' );
							$this->delay = SLPlus_Location::StartingDelay;
							break;
					}

					// No raw json
					//
				} else {
					$errorMessage .= __( 'Geocode service non-responsive', 'store-locator-le' ) .
					                 "<br/>\n" .
					                 $this->geocodeURL . urlencode( $address );
				}

				// Blank Address Error
				//
			} else {
				$errorMessage = __( 'Address is blank.', 'store-locator-le' );
			}

			// Show Error Messages
			//
			if ( $errorMessage != '' ) {
				if ( ! $this->geocodeIssuesRendered ) {
					$errorMessage                =
						'<strong>' .
						sprintf(
							__( 'If you are having geocoding issues, %s', 'store-locator-le' ),
							$this->slplus->Text->get_web_link( 'docs_for_geocoding' )
						) .
						"</strong><br/>\n" .
						$errorMessage;
					$this->geocodeIssuesRendered = true;
				}
				$this->slplus->notifications->add_notice( 6, $errorMessage );

				// Good encoding
				//
			} elseif ( ! $this->geocodeSkipOKNotices ) {
				$this->slplus->notifications->add_notice(
					9,
					sprintf(
						__( 'Google thinks %s is at <a href="%s" target="_blank">lat: %s long %s</a>', 'store-locator-le' ),
						$address,
						sprintf( 'https://%s/?q=%s,%s',
							$this->slplus->options['map_domain'],
							$this->latitude,
							$this->longitude ),
						$this->latitude, $this->longitude
					)
				);
			}

			/**
			 * HOOK: slp_location_geocoded
			 *
			 * Run this when the current location is geocoded.
			 *
			 * @param   SLPLus_Location $location
			 */
			do_action( 'slp_location_geocoded', $this );

		}

		/**
		 * Delete this location permanently.
		 *
		 * @param int|null id
		 *
		 * @return WP_Error|int|false
		 */
		public function delete( $id = null ) {

			// ID is passed in , get location to set the data first.
			//
			if ( ! is_null( $id ) ) {
				$this->get_location( $id );
			}

			// Check the ID is valid.
			//
			if ( (int) $this->id < 0 ) {
				return new WP_Error( 'slp_invalid_id', $this->slplus->Text->get_text_string( array(
					'label',
					'slp_invalid_id'
				) ), array( 'status' => 404 ) );
			}

			// ACTION: slp_deletelocation_starting
			//
			do_action( 'slp_deletelocation_starting' );

			// Attached Post ID?  Delete it permanently (bypass trash).
			//
			$saved_id = $this->id;
			if ( (int) $this->linked_postid > 0 ) {
				$post = get_post( $this->linked_postid );
				if ( $post->post_type === SLPlus::locationPostType ) {
					wp_delete_post( $this->linked_postid, true );
				}
			}

			return $this->slplus->db->delete( $this->slplus->database->info['table'], array( 'sl_id' => $saved_id ) );
		}

		/**
		 * Encode a string to URL-safe base64
		 *
		 * @param $value
		 *
		 * @return mixed
		 */
		private function encode_Base64UrlSafe( $value ) {
			return str_replace( array( '+', '/' ), array( '-', '_' ), base64_encode( $value ) );
		}

		/**
		 * Send back a formatted name including the store, city, and state space separated by default.
		 *
		 * Use the slp_formatted_location_name_elements filter to change which elements are returned.
		 * Use the slp_formatted_location_name filter to change the final string.
		 *
		 * @return mixed|null|void
		 */
		public function get_formatted_name() {

			/**
			 * FILTER: slp_formatted_location_name_elements
			 *
			 * @params string[]     and array of the property names we want to use to build the string.
			 *
			 * @return string[]     modified list of property names
			 */
			$name_parts = apply_filters( 'slp_formatted_location_name_elements', array(
				'store',
				'city',
				'state',
				'country',
			) );

			// Get the valid non-empty properties.
			//
			$valid_parts = array();
			foreach ( $name_parts as $property ) {
				if ( property_exists( $this, $property ) && ( ! empty ( $this->$property ) ) ) {
					$valid_parts[] = $this->$property;
				}
			}

			/**
			 * FILTER: slp_formatted_location_name_separator
			 *
			 * @params  string  the separator for the name defaults to a space
			 *
			 * @return  string  the modified separator used for the join
			 */
			$part_separator = apply_filters( 'slp_formatted_location_name_separator', ' ' );

			/**
			 * FILTER: slp_formatted_location_name
			 *
			 * @params  string  the formatted name
			 *
			 * @return  string  the modified name
			 */
			return apply_filters( 'slp_formatted_location_name', join( $part_separator, $valid_parts ) );
		}

		/**
		 * Return a formatted address.  Unlike get_formatted_name this is used for a typical "postal address block".
		 *
		 */
		public function get_formatted_address() {

			$address_parts = array(
				'store'    => '<br/>',
				'address'  => '<br/>',
				'address2' => '<br/>',
				'city'     => ', ',
				'state'    => ' ',
				'zip'      => '<br/>',
				'country'  => '',
			);

			/**
			 * FILTER: slp_formatted_location_address_elements
			 *
			 * @params string[]     and array of the property names (key) and after-field suffix (value) we want to use to build the string.
			 *
			 * @return string[]     modified list of property names
			 */
			$address_parts = apply_filters( 'slp_formatted_location_address_elements', $address_parts );

			// Get the valid non-empty properties.
			//
			$valid_parts = array();
			foreach ( $address_parts as $property => $suffix ) {
				if ( property_exists( $this, $property ) && ( ! empty ( $this->$property ) ) ) {
					$valid_parts[] = $this->$property . $suffix;
				}
			}

			/**
			 * FILTER: slp_formatted_location_address
			 *
			 * @params  string  the formatted name
			 *
			 * @return  string  the modified name
			 */
			return apply_filters( 'slp_formatted_location_address', join( ' ', $valid_parts ) );
		}

		/**
		 * Get the latitude/longitude for a given address.
		 *
		 * Google Server-Side API geocoding is documented here:
		 * https://developers.google.com/maps/documentation/geocoding/index
		 *
		 * Required Google Geocoding API Params:
		 * address
		 * sensor=true|false
		 *
		 * Optional Google Geocoding API Params:
		 * bounds
		 * language
		 * region
		 * components
		 *
		 * @param string $address the address to geocode
		 *
		 * @return string $response the JSON response string
		 */
		function get_LatLong( $address ) {
			$this->set_geocoding_baseURL();
			$fullURL = $this->geocodeURL . urlencode( $address );

			// Client ID in use?   Sign the request.
			//
			if ( ! empty ( $this->slplus->options_nojs['google_client_id'] ) ) {
				$fullURL = $this->sign_url( $fullURL, $this->slplus->options_nojs['google_private_key'] );
			}

			$request_args = array(
				'timeout' => $this->slplus->options_nojs['http_timeout'],
			);
			$response     = wp_remote_get( $fullURL, $request_args );
			$raw_json     = is_wp_error( $response ) ? null : $response['body'];

			return $raw_json;
		}

		/**
		 * Set the current location to the given location ID and return the SLPlus_Location object.
		 *
		 * @param $location_id
		 *
		 * @return SLPLus_location || WP_Error
		 */
		public function get_location( $location_id ) {
			$this->set_PropertiesViaDB( $location_id );

			// Could not find that location id.
			//
			if ( is_null( $this->locationData ) ) {
				$result = new WP_Error( 'slp_no_such_location', $this->slplus->Text->get_text_string( array(
					'error',
					'slp_no_such_location'
				) ), array( 'status' => 404 ) );

				// Location ID does not match the one requested.
				//
			} elseif ( $location_id !== $this->id ) {
				$result = new WP_Error( 'slp_get_location_failed', $this->slplus->Text->get_text_string( array(
					'label',
					'slp_get_location_failed'
				) ), array( 'status' => 404 ) );

				// All good, return the location object.
				//
			} else {
				$result = $this;

			}

			return $result;
		}

		/**
		 * Return the values for each of the persistent properties of this location.
		 *
		 * @param string $property name of the persistent property to get, defaults to 'all' = array of all properties
		 *
		 * @return mixed the value the property or a named array of all properties (default)
		 */
		public function get_PersistentProperty( $property = 'all' ) {
			$persistentData = array_reduce( $this->dbFields, array( $this, 'mapPropertyToField' ) );

			return ( ( $property === 'all' ) ? $persistentData : ( isset( $persistentData[ $property ] ) ? $persistentData[ $property ] : null ) );
		}

		/**
		 * Return true if the field slug or sl_<field_slug> is a base field.
		 *
		 * @param $field_slug
		 *
		 * @return bool
		 */
		public function is_base_field( $field_slug ) {
			$field_slug = preg_replace( '/^sl_/', '', $field_slug );

			return in_array( $field_slug, $this->dbFields );
		}

		/**
		 * Set all the db field properties to blank.
		 */
		public function reset() {
			foreach ( $this->dbFields as $property ) {
				$this->$property = '';
			}
			$this->pageData   = null;
			$this->attributes = null;
			// TODO: set exdata to array()?
		}

		/**
		 * Set the geocoding base URL.
		 */
		private function set_geocoding_baseURL() {
			if ( isset( $this->geocodeURL ) ) {
				return;
			}

			// Google Maps API for Work client ID
			//
			$client_id =
				! empty ( $this->slplus->options_nojs['google_client_id'] ) ?
					'&client=' . $this->slplus->options_nojs['google_client_id'] . '&v=3' :
					'';

			// Google JavaScript API server Key
			//
			$server_key =
				! empty ( $this->slplus->SmartOptions->google_server_key->value ) ?
					'&key=' . $this->slplus->SmartOptions->google_server_key->value :
					'';

			// Set the map language
			//
			$language = '?language=' . $this->slplus->options_nojs['map_language'];

			// Base Google API URL
			//
			$google_api_url =
				'https://' .
				'maps.googleapis.com' .
				'/maps/api/' .
				'geocode/json' .
				'?';

			// Build the URL with all the params
			//
			$this->geocodeURL =
				$google_api_url .
				$language .
				$client_id .
				$server_key .
				'&address=';
		}

		/**
		 * Set latitude & longitude for this location.
		 *
		 * @param float $lat
		 * @param float $lng
		 */
		public function set_LatLong( $lat, $lng ) {
			$lat_or_lng_changed = false;
			if ( ( $this->latitude != $lat ) && ! is_null( $lat ) ) {
				$this->latitude     = $lat;     // __set() magic method calls set_LatLong, do not use magic method here.
				$lat_or_lng_changed = true;
			}
			if ( ( $this->longitude != $lng ) && ! is_null( $lng ) ) {
				$this->longitude    = $lng;     // __set() magic method calls set_LatLong, do not use magic method here.
				$lat_or_lng_changed = true;
			}

			if ( $lat_or_lng_changed || empty( $this->initial_distance ) ) {
				$this->calculate_initial_distance();
				$this->dataChanged = true;
			}
		}

		/**
		 * Setup the data for the current page, run through augmentation filters.
		 *
		 * This method applies the slp_location_page_attributes filter.
		 *
		 * Using that filter allows other parts of the system to change or augment
		 * the data before we create or update the page in the WP database.
		 *
		 * @return mixed[] WordPress custom post type property array
		 */
		private function set_PageData() {

			// We have an existing page
			// should feed a wp_update_post not wp_insert_post
			//
			if ( intval( $this->linked_postid ) > 0 ) {
				$this->pageData = array(
					'ID'        => $this->linked_postid,
					'slp_notes' => 'pre-existing page',
				);

				// No page yet, default please.
				//
			} else {
				$this->pageData = array(
					'ID'           => '',
					'post_type'    => SLPlus::locationPostType,
					'post_status'  => $this->pageDefaultStatus,
					'post_title'   => ( empty( $this->store ) ? 'SLP Location #' . $this->id : $this->store ),
					'post_content' => '',
					'slp_notes'    => 'new page',
				);
			}

			// Apply our location page data filters.
			// This is what allows add-ons to tweak page data.
			//
			// FILTER: slp_location_page_attributes
			//
			$this->pageData = apply_filters( 'slp_location_page_attributes', $this->pageData );

			return $this->pageData;
		}

		/**
		 * Sign a URL with a given crypto key.
		 *
		 * Note that this URL must be properly URL-encoded.
		 *
		 * @param $myUrlToSign
		 * @param $privateKey
		 *
		 * @return string
		 */
		private function sign_url( $myUrlToSign, $privateKey ) {
			// parse the url
			$url = parse_url( $myUrlToSign );

			$urlPartToSign = $url['path'] . "?" . $url['query'];

			// Decode the private key into its binary format
			$decodedKey = $this->decode_Base64UrlSafe( $privateKey );

			// Create a signature using the private key and the URL-encoded
			// string using HMAC SHA1. This signature will be binary.
			$signature = hash_hmac( "sha1", $urlPartToSign, $decodedKey, true );

			$encodedSignature = $this->encode_Base64UrlSafe( $signature );

			return $myUrlToSign . "&signature=" . $encodedSignature;
		}

		/**
		 * Make the location data persistent.
		 *
		 * @return boolean data write OK
		 */
		public function MakePersistent() {
			$dataWritten = true;
			$dataToWrite = array_reduce( $this->dbFields, array( $this, 'mapPropertyToField' ) );

			// sl_id int field blank, unset it we will insert a new auto-int record
			//
			if ( empty( $dataToWrite['sl_id'] ) ) {
				unset( $dataToWrite['sl_id'] );
			}

			// sl_last_upated is blank, unset to get auto-date value
			//
			if ( empty( $dataToWrite['sl_lastupdated'] ) ) {
				unset( $dataToWrite['sl_lastupdated'] );
			}

			// sl_linked_postid is blank, set it to 0
			//
			if ( empty( $dataToWrite['sl_linked_postid'] ) ) {
				$dataToWrite['sl_linked_postid'] = 0;
			}

			// Location is set, update it.
			//
			if ( $this->id > 0 ) {
				if ( ! $this->slplus->db->update( $this->slplus->database->info['table'], $dataToWrite, array( 'sl_id' => $this->id ) ) ) {
					$dataWritten = false;
				}

				// No location, add it.
				//
			} else {
				if ( ! $this->slplus->db->insert( $this->slplus->database->info['table'], $dataToWrite ) ) {
					$this->slplus->notifications->add_notice(
						'warning',
						sprintf( __( 'Could not add %s as a new location', 'store-locator-le' ), $this->store )
					);
					$dataWritten = false;
					$this->id    = '';

					// Set our location ID to be the newly inserted record!
					//
				} else {
					$this->id = $this->slplus->db->insert_id;
				}

			}

			$this->make_extended_data_persistent();

			// Reset the data changed flag, used to manage MakePersistent calls.
			// Stops MakePersistent from writing data to disk if it has not changed.
			//
			$this->dataChanged = false;

			return $dataWritten;
		}

		/**
		 * Make extended data persistent.
		 */
		public function make_extended_data_persistent() {
			if ( ! $this->slplus->database->is_Extended() ) {
				return;
			}
			if ( empty( $this->exdata ) ) {
				return;
			}
			if ( ! $this->dataChanged ) {
				return;
			}

			$this->slplus->database->extension->update_data( $this->id, $this->exdata );

			$this->slplus->database->reset_extended_data_flag();
		}

		/**
		 * Return true of the given string is an int greater than 0.
		 *
		 * If not id is presented, check the current location ID.
		 *
		 * request_param is used if ID is set to null to try to set the value from a request variable of that name.
		 *
		 * @param string $id
		 * @param string $request_param
		 *
		 * @return boolean
		 */
		function isvalid_ID( $id = null, $request_param = null ) {

			if ( ! is_null( $request_param ) && isset( $_REQUEST[ $request_param ] ) ) {
				$id = $_REQUEST[ $request_param ];
			}

			if ( $id === null ) {
				$id = $this->id;
			}

			$id = (int) $id;

			return ( $id > 0 );
		}

		/**
		 * Latitude is valid.
		 *
		 * @param string $lat if not passed defaults to current location latitude.
		 *
		 * @return bool
		 */
		function is_valid_lat( $lat = null ) {
			if ( is_null( $lat ) ) {
				$lat = $this->latitude;
			}
			$regex = '/^(\+|-)?(?:90(?:(?:\.0{1,14})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,14})?))$/';

			return ( preg_match( $regex, $lat ) === 1 );
		}

		/**
		 * Longitude is valid.
		 *
		 * @param string $lng if not passed defaults to current location longitude.
		 *
		 * @return bool
		 */
		function is_valid_lng( $lng = null ) {
			if ( is_null( $lng ) ) {
				$lng = $this->longitude;
			}
			$regex = '/^(\+|-)?(?:180(?:(?:\.0{1,14})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,14})?))$/';

			return ( preg_match( $regex, $lng ) === 1 );
		}

		/**
		 * Return a named array that sets key = db field name, value = location property
		 *
		 * @param mixed  $result
		 * @param string $property - name of the location property
		 *
		 * @return mixed[] - key = string of db field name, value = location property value
		 */
		private function mapPropertyToField( $result, $property ) {
			// Map attributes back into option_value
			//
			if ( $property == 'option_value' ) {
				$this->$property = maybe_serialize( $this->attributes );
			}

			// Set field to property
			//
			$result[ $this->dbFieldPrefix . $property ] = $this->$property;

			return $result;
		}

		/**
		 * Set location properties via a named array containing the field data.
		 *
		 * Used to set properties based on the MySQL SQL fetch to ARRAY_A method
		 * or on a prepped named array where the field names are keys and
		 * field values are the values.
		 *
		 * Mode parameter:
		 * o dbreset  = reset location data to blank before loading it up
		 * o reset = reset location data to blank before loading it up
		 * o update = do NOT reset location data to blank before updating
		 *
		 * Assumes the field names start with 'sl_'.
		 *
		 * @param array  $locationData
		 * @param string $mode which mode?  'reset' or 'update' defaults to reset;
		 */
		public function set_PropertiesViaArray( $locationData, $mode = 'reset' ) {

			// If we have an array, assume we are on the right track...
			if ( is_array( $locationData ) ) {

				// Do not set the data if it is unchanged from the last go-around
				//
				if ( $locationData === $this->locationData ) {
					return;
				}

				// Process mode.
				// Ensures any value other than 'dbreset' or 'update' resets the location data.
				//
				switch ( $mode ) {
					case 'dbreset':
					case 'update':
						break;
					default:
						$this->reset();
						break;
				}

				// Go through the named array and extract properties.
				//
				foreach ( $locationData as $field => $value ) {

					// TODO: This is probably wrong and can be deleted.  Should be sl_id, but that causes duplicate entries.
					if ( $field === 'id' ) {
						continue;
					}

					// Get rid of the leading field prefix (usually sl_)
					//
					$property = str_replace( $this->dbFieldPrefix, '', $field );

					// If this is a valid property in the base properties
					// or extended data properties
					//
					if ( ( ! $this->validate_fields ) || $this->valid_location_property( $property ) ) {
						$ssd_value = stripslashes_deep( $value );
						if ( $this->$property != $ssd_value ) {
							$this->__set( $property , $ssd_value );
							$this->dataChanged = true;
						}
					}
				}

				// Deserialize the option_value field
				//
				$this->attributes = maybe_unserialize( $this->option_value );

				$this->locationData = $locationData;
			}
		}

		/**
		 * Load a location from the database.
		 *
		 * Only re-reads database if the location ID has changed.
		 *
		 * @param int $locationID - ID of location to be loaded
		 *
		 * @return SLPlus_Location $this - the location object
		 */
		public function set_PropertiesViaDB( $locationID ) {

			// Reset the set_PropertiesViaArray tracker.
			//
			$this->locationData = null;

			// Our current ID does not match, load new location data from DB
			//
			if ( $this->id != $locationID ) {
				$this->reset();

				$locData =
					$this->slplus->database->get_Record(
						array( 'selectall', 'whereslid' ),
						$locationID
					);
				if ( is_array( $locData ) ) {
					$this->set_PropertiesViaArray( $locData, 'dbreset' );
				}
			}

			// Reset the data changed flag, used to manage MakePersistent calls.
			// Stops MakePersistent from writing data to disk if it has not changed.
			//
			$this->dataChanged = false;

			return $this;
		}

		/**
		 * Update the location attributes, merging existing attributes with new attributes.
		 *
		 * @param mixed[] $newAttributes
		 */
		public function update_Attributes( $newAttributes ) {
			if ( is_array( $newAttributes ) ) {
				$this->attributes  =
					is_array( $this->attributes ) ?
						array_merge( $this->attributes, $newAttributes ) :
						$newAttributes;
				$this->dataChanged = true;
			}
		}

		/**
		 * Return the value of the specified location data element or blank if not set.
		 *
		 * @param array  $data the location data array
		 * @param string $key  store locator plus location data array key
		 *
		 * @return mixed - the data element value or a blank string
		 */
		private function val_or_blank( $data, $key ) {
			return isset( $data[ $key ] ) ? $data[ $key ] : '';
		}

		/**
		 * Return true if the property is valid.
		 *
		 * @param string $property property name to validate
		 *
		 * @return boolean true if property is OK
		 */
		private function valid_location_property( $property ) {
			if ( property_exists( $this, $property ) ) {
				return true;
			}
			if ( array_key_exists( $property, $this->exdata ) ) {
				return true;
			}
			if ( isset( $this->slplus->database->extension ) ) {
				$this->slplus->database->extension->set_cols();
				if ( array_key_exists( $property, $this->slplus->database->extension->metatable['records'] ) ) {
					return true;
				}
			}

			return false;
		}
	}
}