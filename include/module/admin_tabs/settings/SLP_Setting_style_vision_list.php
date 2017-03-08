<?php
if ( ! class_exists( 'SLP_Setting_vision_list' ) ) {

	require_once( 'SLP_Setting_vision_list.php' );

	/**
	 * The style vision list.
	 */
	class SLP_Setting_style_vision_list extends SLP_Setting_vision_list {
		public $uses_slplus = true;

		/**
		 * Get our plugin styles.
		 */
		protected function get_items() {
			require_once( SLPLUS_PLUGINDIR . 'include/module/style/SLP_Style_Manager.php' );
			$this->data['premier'] = $this->slplus->AddOns->is_premier_subscription_valid() ? '1' : '0';
			$this->fetch_style_galleries();
		}

		/**
		 * Get the Style Galleries from The SLP Server
		 */
		private function fetch_style_galleries() {
			$error_text = array(
				'json_empty_inside' => array(
					'title'       => __( 'Jason Is Empty Inside', 'store-locator-le' ),
					'description' => __( 'We got a reply but there was nothing inside.', 'store-locator-le' )
				),
				'not_200' => array(
					'title'       => __( 'All Was Not OK', 'store-locator-le' ),
					'description' => __( 'The style server says that line has been disconnected.', 'store-locator-le' )
				),
				'response_body_empty' => array(
					'title'       => __( 'Nothing But Hot Air', 'store-locator-le' ),
					'description' => __( 'We received a response from the style server but it was blank.', 'store-locator-le' )
				),
				'response_not_array' => array(
						'title'       => __( 'Well THAT Was Unexpected', 'store-locator-le' ),
						'description' => __( 'We received an unexpected response from the style server.', 'store-locator-le' )
				),
				);

			$this->slplus->Style_Manager->page_size = $this->items_to_load_at_start;
			$response = $this->slplus->Style_Manager->fetch_style();

			// Houston, we have a problem...
			if ( is_wp_error( $response ) ) {
				$code = $response->get_error_code();
				$this->items[] = new SLP_Setting_item( array(
					'title'       => $error_text[ $code ][ 'title'       ],
					'description' => $error_text[ $code ][ 'description' ],
				) );
				return;
			}

			// Continue on my wayward son...
			$this->put_json_response_into_vision_list( $response );
		}

		/**
		 * Put the JSON response into a proper vision list.
		 */
		private function put_json_response_into_vision_list( $json_response ) {

			foreach ( $json_response as $post ) {

				$access_level = isset( $post->custom_fields->access_level ) ? $post->custom_fields->access_level[0] : '';
				$this->items[] = new SLP_Setting_item( array(
					'title' => $post->title->rendered ,
					'description' => $post->content->rendered,
					'has_actions' => $this->has_actions( $access_level ),
					'data' => array(
						 'post_id' => $post->id,
						),
					'classes' => $this->more_class( $access_level ),
					) );
				$this->slplus->Style_Manager->cache_style( $post );
			}
		}

		/**
		 * Add access level to item class.
		 * 
		 * @param string $access_level
		 *
		 * @return array
		 */
		private function more_class( $access_level ) {
			$classes = array();
			if ( ! empty( $access_level )  ) {
				$classes[] = $access_level;
			}
			return $classes;
		}

		/**
		 * Add theme actions.
		 *
		 * @param string $access_level
		 *
		 * @return bool
		 */
		private function has_actions( $access_level ) {
			return empty( $access_level ) || $this->slplus->AddOns->is_premier_subscription_valid();
		}
	}
}