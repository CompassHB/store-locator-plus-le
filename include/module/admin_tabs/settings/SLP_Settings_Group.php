<?php
if ( ! class_exists( 'SLP_Settings_Group' ) ) {
	require_once( SLPLUS_PLUGINDIR . 'include/unit/SLP_Setting.php');

	/**
	 * Groups are collections of individual settings (items).
	 *
	 * @package   SLPlus\Settings\Group
	 * @author    Lance Cleveland <lance@charlestonsw.com>
	 * @copyright 2013 - 2017 Charleston Software Associates, LLC
	 *
	 * @property        string                 $div_group    the div group we belong in
	 * @property        string                 $header       the header
	 * @property        string                 $intro        the starting text
	 * @property-read   SLP_Settings_default[] $items
	 * @property        string                 $slug         the slug
	 */
	class SLP_Settings_Group extends SLPlus_BaseClass_Object {
		public $div_group;
		public $intro;
		public $header;
		public $slug;
		public $SLP_Settings;

		private $items;

		/**
		 * Add an item to the group.
		 *
		 * @param mixed[] $params
		 */
		function add_item( $params ) {
			$item_object = $this->get_object_definition( $this->get_type( $params ) );
			$this->items[] = new $item_object( $params );
		}

		/**
		 * Map old types to new ones and set default to 'custom' if not set.
		 * 
		 * @param $params
		 *
		 * @return string
		 */
		private function get_type( $params ) {
			if ( empty( $params[ 'type'] ) ) {
				return 'custom';
			}

			switch ( $params['type']) {
				case 'submit_button':
					return 'submit';
				case 'text':
					return 'input';
				default:
					return $params['type'];
			}

		}

		/**
		 * Does this group have any items?
		 *
		 * @return bool
		 */
		function has_items() {
			return ! empty( $this->items );
		}

		/**
		 * Load a class file if it exists for the item type.
		 *
		 * @param string $type
		 * @return string
		 */
		private function get_object_definition( $type ) {
			if ( ! isset( $this->SLP_Settings->known_classes[ $type ] ) ) {
				$class = 'SLP_Setting_' . $type;
				foreach ( $this->SLP_Settings->definition_directories as $directory ) {
					$fq_filename = $directory . $class . '.php';
					if ( is_readable( $fq_filename ) ) {
						require_once( $fq_filename );
						$this->SLP_Settings->known_classes[ $type ] = $class;
						break;
					}
				}

				// Load custom Item Definition If Not Defined
				//
				if ( empty( $this->SLP_Settings->known_classes[ $type ] ) ) {
					require_once( SLPLUS_PLUGINDIR . 'include/module/admin_tabs/settings/SLP_Setting_custom.php');
					$this->SLP_Settings->known_classes[ $type ] = 'SLP_Setting_custom';
				}

			}

			return $this->SLP_Settings->known_classes[ $type ];
		}

		/**
		 * Render a group.
		 */
		function render_Group() {
			if ( ! $this->has_items() && empty( $this->intro )) { return; }
			$this->render_Header();
			if ( isset( $this->items ) ) {
				foreach ( $this->items as $item ) {
					$item->display();
				}
			}
			echo '</div></div>';
		}

		/**
		 * Output the group header.
		 */
		function render_Header() {
			echo
				"\n<h4 class='settings-header ui-accordion-header'>{$this->header}</h4>" .
				"<div id='wpcsl_settings_group-{$this->slug}' class='settings-group ui-accordion-content-active' >" .
				"<div class='inside'>" .
				(
				( $this->intro != '' ) ?
					"<div class='section_column_intro' id='wpcsl_settings_group_intro-{$this->slug}'>{$this->intro}</div>" :
					''
				);
		}

	}

}
