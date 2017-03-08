<?php
if ( ! class_exists( 'SLP_Setting_dropdown' ) ) {

	/**
	 * The dropdown setting.
	 */
	class SLP_Setting_dropdown extends SLP_Setting {
		public $empty_ok = false;
		public $selectedVal = '';
		public $uses_slplus = true;

		/**
		 * The dropdown HTML.
		 *
		 * @param string $data
		 * @param string $attributes
		 *
		 * @return string
		 */
		protected function get_content( $data, $attributes ) {
			return
				$this->slplus->Helper->createstring_DropDownMenu(
					array(
						'id'          => $this->name,
						'name'        => $this->name,
						'items'       => $this->custom,
						'onchange'    => $this->onChange,
						'disabled'    => $this->disabled,
						'selectedVal' => $this->selectedVal,
						'empty_ok'    => $this->empty_ok,
					)
				);
		}

	}

}