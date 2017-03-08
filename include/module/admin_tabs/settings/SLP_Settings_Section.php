<?php

if ( ! class_exists( 'SLP_Settings_Section' ) ) {
	require_once( SLPLUS_PLUGINDIR . 'include/module/admin_tabs/settings/SLP_Settings_Group.php');

	/**
	 * Sections are collections of groups.
	 *
	 * @package   SLPlus\Settings\Section
	 * @author    Lance Cleveland <lance@charlestonsw.com>
	 * @copyright 2013 - 2016 Charleston Software Associates, LLC
	 *
	 * @property boolean                 $auto
	 * @property string                  $closing_html
	 * @property string                  $description
	 * @property string                  $div_id
	 * @property string                  $first          True if the first rendered section on the panel.
	 * @property SLP_Settings_Group[]    $groups
	 * @property boolean                 $innerdiv
	 * @property boolean                 $is_topmenu
	 * @property string                  $name
	 * @property string                  $opening_html
	 * @property string                  $slug           The normalized section slug.
	 */
	class SLP_Settings_Section extends SLPlus_BaseClass_Object {
		public $auto = true;
		public $closing_html = '';
		private $current_div_group = '';
		public $description = '';
		public $div_id;
		public $first = false;
		public $groups;
		public $group_slug;
		public $innerdiv = true;
		public $is_topmenu = false;
		public $name;
		public $opening_html = '';
		public $slug;
		public $SLP_Settings;

		/**
		 * Add an item to a section.
		 *
		 * @param array $params
		 */
		function add_item( $params ) {
			if ( empty( $params['group'] ) ) {
				$params['group'] = 'Settings';
			}
			$params['group_slug'] = isset( $params['group_slug'] ) ? $params['group_slug'] : strtolower( str_replace( ' ', '_', $params['group'] ) );

			$this->add_group( $params );

			$group_slug = $params['group_slug'];
			unset( $params['group_slug'] );

			$this->groups[ $group_slug ]->add_item( $params );
		}

		/**
		 * Add a group to the section.
		 *
		 * @param   array $params
		 */
		function add_group( $params ) {
			if ( ! isset( $this->groups[ $params['group_slug'] ] ) ) {
				$params['slug']   = $params['group_slug'];
				$params['header'] = isset( $params['header'] ) ? $params['header'] : ( isset( $params['group'] ) ? $params['group'] : '' );
				$params['intro']  = isset( $params['intro'] ) ? $params['intro'] : ( isset( $this->description ) ? $this->description : '' );
				$params['SLP_Settings'] = $this->SLP_Settings;

				$this->groups[ $params['group_slug'] ] = new SLP_Settings_Group( $params );

				$this->description = '';
			}
		}

		/**
		 * Render a section panel.
		 *
		 * Panels are rendered in the order they are put in the stack, FIFO.
		 */
		function display() {
			$this->header();
			if ( isset( $this->groups ) ) {
				foreach ( $this->groups as $group ) {
					if ( ! empty( $group->div_group ) && ( $group->div_group != $this->current_div_group ) ) {
						if ( ! empty( $this->current_div_group ) ) {
							echo '</div>';
						}
						echo "\n<div class='{$group->div_group}'>";
						$this->current_div_group = $group->div_group;
					}
					$group->render_Group();
				}
				if ( ! empty( $this->current_div_group ) ) {
					echo '</div>';
				}
			}
			$this->footer();
		}

		/**
		 * Return a named array of our most desired attributes.
		 *
		 * @return array
		 */
		public function get_params() {
			return array(
				'slug' => $this->slug,
				'name' => $this->name,
			);
		}

		/**
		 * Render a section header.
		 */
		function header() {
			$friendlyName = strtolower( strtr( $this->name, ' ', '_' ) );
			$friendlyDiv  = ( isset( $this->div_id ) ? $this->div_id : $friendlyName );
			$groupClass   = $this->is_topmenu ? '' : 'group';

			echo '<div ' .
			     "id='wpcsl-option-{$friendlyDiv}' " .
			     "class='{$groupClass} subtab_{$friendlyDiv} subtab settings' " .
			     ">";

			print $this->opening_html;

			if ( $this->innerdiv ) {
				echo "<div class='inside section meta-box-sortables'>";
				if ( ! empty( $this->description ) ) {
					print "<div class='section_description'>";
				}
			}

			if ( ! empty( $this->description ) ) {
				echo $this->description;
			}

			if ( $this->innerdiv ) {
				if ( ! empty( $this->description ) ) {
					echo '</div>';
				}
			}
		}

		/**
		 * Should the section be show (display:block) now?
		 *
		 * @return boolean
		 */
		function show_now() {
			return ( $this->first || $this->is_topmenu );
		}

		/**
		 * Render a section footer.
		 */
		function footer() {
			if ( $this->innerdiv ) {
				echo '</div>';
			}
			print $this->closing_html;
			echo '</div>';
		}

	}

}