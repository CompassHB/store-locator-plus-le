<?php
if ( ! class_exists('SLPlus_BaseClass_Object') ) {

	/**
	 * Class SLPlus_BaseClass_Object
	 *
	 * @property		SLPlus		$slplus
	 * @property		boolean		$uses_slplus		Set to true (default) if the object needs access to the SLPlus plugin object.
	 */
	class SLPlus_BaseClass_Object {
		protected $slplus;
		protected $uses_slplus = true;

		/**
		 * @param array $options
		 */
		function __construct( $options = array() ) {

			if ( ! empty( $options ) && is_array( $options ) ) {
				foreach ( $options as $property => $value ) {
					if ( property_exists( $this, $property ) ) {
						$this->$property = $value;
					}
				}
			}

			if ( $this->uses_slplus ) {
				global $slplus_plugin;
				$this->slplus = $slplus_plugin;
			}

			$this->initialize();
		}

		/**
		 * Do these things when this object is invoked. Override in your class.
		 */
		protected function initialize() {}
	}

}