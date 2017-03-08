<?php
if ( ! class_exists( 'SLP_Addon_MetaData' ) ) {

	/**
	 * Handle Add On MetaData.
	 *
	 * Load the metadata from a plugin header only when needed.
	 * This lightens the memory and disk I/O load on normal UI operations.
	 *
	 * @package StoreLocatorPlus\Addon\MetaData
	 * @author Lance Cleveland <support@storelocatorplus.com>
	 * @copyright 2014-2015 Charleston Software Associates, LLC
	 *
	 * @property		SLP_BaseClass_Addon		$addon
	 * @property-read	string[]				$metadata		Named array of metadata properties.
	 * 					@see https://developer.wordpress.org/reference/functions/get_plugin_data/
	 * @property-read 	bool					$meta_read 		Has the meta data been read from the add on file header?
	 *
	 */
	class SLP_Addon_MetaData extends SLPlus_BaseClass_Object {
		public 	$addon;
		private $metadata;
		private $meta_read = false;

		/**
		 * Read the plugin header meta.
		 */
		private function read_meta() {
			if ( ! $this->meta_read ) {
				if ( isset( $this->addon->file ) ) {
					if( ! function_exists( 'get_plugin_data' ) ) {
						include ABSPATH . '/wp-admin/includes/plugin.php';
					}
					$this->metadata = get_plugin_data( $this->addon->file );
				}
				$this->meta_read = true;
			}
		}

		/**
		 * Return the specified metadata property.
		 *
		 * @param string $property
		 *
		 * @return string
		 */
		public function get_meta( $property ) {
			$this->read_meta();
			if ( ! isset( $this->metadata[$property] ) ) {
				$this->metadata[$property] = '';
			}
			return $this->metadata[$property];
		}

	}

}