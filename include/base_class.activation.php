<?php
if (! class_exists('SLP_BaseClass_Activation')) {

    /**
     * A base class that helps add-on packs separate activation functionality.
     *
     * Add on packs should include and extend this class.
     *
     * This allows the main plugin to only include this file during activation.
     *
     * @package StoreLocatorPlus\BaseClass\Activation
     * @author Lance Cleveland <support@storelocatorplus.com>
     * @copyright 2014 - 2015 Charleston Software Associates, LLC
     *
     * @property    SLP_BaseClass_Addon     $addon
     * @property    array                   $legacy_options     Set this if you are converting legacy options.
     *                                                              @see SLPLUS class.activation.upgrade for examples.
     * @property    string                  $updating_from      The version of this add-on that was installed previously.
     */
    class SLP_BaseClass_Activation extends SLPlus_BaseClass_Object {
        protected $addon;
	    protected $legacy_options;
	    protected $obsolete_options = array();
	    protected $smart_options = array();
        protected $updating_from;

	    /**
	     * Convert the legacy settings to the new serialized settings.
	     *
	     */
	    private function convert_legacy_settings() {
			if ( empty( $this->legacy_options ) ) { return; }

		    foreach ( $this->legacy_options as $legacy_option => $new_option_meta ) {
                $since_version = isset( $new_option_meta[ 'since' ] ) ? $new_option_meta[ 'since' ] : null;

                // Run the conversion if the current addon version is less then the since version (changed in version) for this option.
                //
                $current_installed_version = isset( $this->addon->options['installed_version'] ) ? $this->addon->options['installed_version'] : '0';
                if ( is_null( $since_version ) || ( version_compare( $current_installed_version , $since_version , '<=' ) ) ) {

                    // Get the legacy option
                    //
                    $option_value = get_option($legacy_option, null);

                    // No legacy option?  Is there a default?
                    if (is_null($option_value) && isset($new_option_meta['default'])) {
                        $option_value = $new_option_meta['default'];
                    }

                    // If there was a legacy option or a default setting override.
                    // Set that in the new serialized option string.
                    // Otherwise leave it at the default setup in the SLPlus class.
                    //
                    if (!is_null($option_value)) {

                        // Callback processing
                        //
                        if (isset($new_option_meta['callback'])) {
                            $option_value = call_user_func_array($new_option_meta['callback'], array($option_value));
                        }

                        $this->addon->options[$new_option_meta['key']] = $option_value;

                        // Delete the legacy option
                        //
                        delete_option($legacy_option);
                    }
                }
		    }
	    }


	    /**
	     * Convert legacy add on options to smart options.
	     *
	     * The parent base_class.admin.php from SLP will auto-call update_option( ) for addon->options.
	     */
	    private function convert_to_smart_options() {
		    if ( empty( $this->smart_options ) ){
		    	return;
		    }
		    foreach ( $this->smart_options as $option_slug ) {
			    if ( isset( $this->addon->options[ $option_slug ] ) ) {
				    $this->slplus->SmartOptions->{$option_slug}->value = $this->addon->options[ $option_slug ];
				    unset ( $this->addon->options[ $option_slug ] );
			    }
		    }
	    }

	    /**
	     * Remove any options listed in obsolete options or smart options lists.
	     */
	    private function remove_obsolete_options() {
	    	$remove_these = array_merge( $this->obsolete_options, $this->smart_options );
	    	if ( empty( $remove_these ) ) {
	    		return;
		    }
	    	foreach ( $remove_these as $key ) {
	    		if ( array_key_exists( $key , $this->addon->options ) ) {
	    			unset( $this->addon->options[ $key ] );
			    }
		    }
	    }

        /**
         * Things we do at startup.
         */
        function initialize() {
            $this->updating_from = $this->addon->options['installed_version'];
        }

        /**
         * Do this whenever the activation class is instantiated.
         *
         * This is triggered via the update_prior_installs method in the admin class,
         * which is run via update_install_info() in the admin class.
         *
         * update_install_info should be something you put in any add-on pack
         * that is using the base add-on class.  It typically goes inside the
         * do_admin_startup() method which is overridden by the new add on
         * adminui class code.
         *
         * Set your $legacy_options.
         *
         */
        public function update() {
            $this->convert_legacy_settings();
	        $this->convert_to_smart_options();
	        $this->remove_obsolete_options();
	        update_option( $this->addon->option_name , $this->addon->options );
        }
    }
}