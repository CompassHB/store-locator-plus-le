<?php

if (! class_exists('SLP_getitem')) {
	require_once( SLPLUS_PLUGINDIR . 'include/module/admin_tabs/settings/SLP_Settings.php' );

	/**
	 * SLP_getitem.
	 *
	 * TODO: deprecate when (GFL) convert to slplus->get_item() , better yet replace with options/options_nojs.
	 *
	 * @package   SLPlus\get_item
	 * @author    Lance Cleveland <lance@charlestonsw.com>
	 * @copyright 2015 Charleston Software Associates, LLC
	 *
	 */
	class SLP_getitem extends SLP_Settings {

		/**
		 * Get and item from the WP options table.
		 *
		 * @param            $name
		 * @param null       $default
		 * @param string     $separator
		 *
		 * @return mixed|void
		 */
		function get_item( $name, $default = null, $separator = '-' ) {
			global $slplus_plugin;

			return $slplus_plugin->get_item( $name, $default, $separator );
		}
	}
}