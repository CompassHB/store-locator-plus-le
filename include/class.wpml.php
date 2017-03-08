<?php
if ( ! class_exists('SLPlus_WPML') ) {

	/**
	 * Store Locator Plus WPML interface.
	 *
	 * @package   StoreLocatorPlus\WPML
	 * @author    Lance Cleveland <lance@charlestonsw.com>
	 * @copyright 2013-2015 Charleston Software Associates, LLC
	 */
	class SLPlus_WPML extends SLPlus_BaseClass_Object {

		/**
		 * @var boolean is_active Is WPML has been installed and activated?
		 */
		private $is_active = null;

		/**
		 * @param array $options
		 */
		public function __construct( $options = array() ) {
			parent::__construct( $options );
			$this->startup_register_text();
		}

		/**
		 * Is WPML has been installed and activated?
		 *
		 * @return boolean true if WPML is active
		 */
		public function isActive() {
			if ( ! is_null( $this->is_active ) ) { return $this->is_active; }

			if ( has_action( 'wpml_register_single_string' ) ) {
				$this->is_active = true;
			} else {
				$this->is_active = false;

			}

			return $this->is_active;
		}

		/**
		 * Return WPML translation string of $value if WPML is active.
		 * If WPML is not active, just return $value.
		 *
		 * @param string The name of the text need to translate.
		 * @param string The value of the text need to translate.
		 * @param string The textdomain to be used for the translation.
		 *
		 * @return string WPML translated text
		 */
		public function get_text( $key, $value = null, $textdomain = 'store-locator-le' ) {

			// No value?  Get SLPlus options/options_nojs
			if ( is_null( $value ) ) {

				if ( array_key_exists( $key , $this->slplus->options ) ) {
					$value = $this->slplus->options[$key];

				} elseif ( array_key_exists( $key , $this->slplus->options_nojs ) ) {
					$value = $this->slplus->options_nojs[$key];

				}
			}

			if ( ! $this->isActive() || is_null( $value ) ) {
				return $value;
			}

            return apply_filters( 'wpml_translate_single_string', $value, $textdomain, $key );

		}

		/**
		 * Register a text need to tanslate to WPML
		 *
		 * @param string text name, which used by WPML to tell user the meaning of the text.
		 * @param string text value, the text need to translate.
		 */
		public function register_text( $name, $value, $textdomain = 'store-locator-le' ) {
			if ( ! $this->isActive() ) { return; }
            do_action( 'wpml_register_single_string', $textdomain, $name, $value );
		}

		/**
		 * Register need save option value to WPML
		 *
		 * @param string[] option name array.
		 * @param          string default value used if $_POST don't have the option.
		 */
		public function register_post_options( $optionname, $default = null ) {
			if ( ! $this->isActive() ) { return; }

			$option_value = isset( $_POST[$optionname] ) ? $_POST[$optionname] : $default;
			if ( ! is_null( $option_value ) ) {
				$this->register_text( $optionname, stripslashes_deep( $option_value ) );
			}
		}

		/**
		 * Register text strings at startup.
		 */
		private function startup_register_text() {
			foreach ( $this->slplus->SmartOptions->get_text_options() as $key ) {
				$this->register_text( $key , $this->get_text( $key ) );
			}
		}
	}
}