<?php
if ( ! class_exists( 'SLP_Setting_input' ) ) {

	/**
	 * The input setting.
	 */
	class SLP_Setting_input extends SLP_Setting {

		/**
		 * The input HTML.
		 * 
		 * @param string $data
		 * @param string $attributes
		 *
		 * @return string
		 */
		protected function get_content( $data, $attributes ) {
			return "<input type='text' id='{$this->id}' name='{$this->name}' value='{$this->display_value}' {$data} {$attributes}>";
		}
	}

}