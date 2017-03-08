<?php
if ( ! class_exists( 'SLP_Admin_General_Text' ) ) {
	/**
	 * Class SLP_Admin_General_Text
	 *
	 * Extend text for admin general tab
	 *
	 * @package   StoreLocatorPlus\Admin\General\Text
	 * @author    Lance Cleveland <lance@charlestonsw.com>
	 * @copyright 2016 Charleston Software Associates, LLC
	 *
	 * @since     4.7.1
	 *
	 */
	class SLP_Admin_General_Text {
		private $text;

		/**
		 * SLP_Admin_Experience_Text constructor.
		 */
		public function __construct() {
			$this->initialize();
		}

		/**
		 * Do at the start.
		 */
		private function initialize() {
			$this->set_text_strings();
			add_filter( 'slp_get_text_string', array( $this, 'get_text_string' ), 10, 2 );
		}

		/**
		 * Set the strings we need on the admin panel.
		 *
		 * @param string   $text
		 * @param string[] $slug
		 *
		 * @return string
		 */
		public function get_text_string( $text, $slug ) {
			if ( $slug[0] === 'settings_group_header' ) {
				$slug[0] = 'settings_group';
			}
			if ( isset( $this->text[ $slug[0] ][ $slug[1] ] ) ) {
				return $this->text[ $slug[0] ][ $slug[1] ];
			}

			return $text;
		}

		/**
		 * Set our text strings.
		 */
		private function set_text_strings() {
			if ( isset( $this->text ) ) {
				return;
			}
			$this->text['settings_section']['admin'         ]   = __( 'Admin', 'store-locator-le' );
			$this->text['settings_section']['user_interface']   = __( 'User Interface', 'store-locator-le' );
			$this->text['settings_section']['server'        ]   = __( 'Server', 'store-locator-le' );
			$this->text['settings_section']['data'          ]   = __( 'Data', 'store-locator-le' );
			$this->text['settings_section']['schedule'      ]   = __( 'Schedule', 'store-locator-le' );

			$this->text['settings_group']['locations'   ]       = __( 'Locations', 'store-locator-le' );
			$this->text['settings_group']['add_on_packs']       = __( 'Add Ons', 'store-locator-le' );
			$this->text['settings_group']['messages'    ]       = __( 'Messages', 'store-locator-le' );

			$this->text['label'      ]['log_schedule_messages'] = __( 'Log Schedule Messages' , 'store-locator-le' );
			$this->text['description']['log_schedule_messages'] = __( 'Scheduled tasks such as the Power scheduled import and Premier scheduled geocoding can log progress messages by turning this on.' , 'store-locator-le' );
		}
	}
}