<?php
if ( ! class_exists( 'SLP_Internet_Helper' ) ) {
	/**
	 * Class SLP_Internet_Helper
	 */
	class SLP_Internet_Helper {

		/**
		 * Fetch a URL, check the JSON is valid, and decode it if so.
		 *
		 * @param   string          $url
		 *
		 * @return WP_Error|array   Return an error or a decoded JSON array
		 */
		public function get_and_validate_json_response( $url ) {
			$json = wp_remote_get( $url );

			// Wrong...
			if ( is_wp_error( $json ) ) {
				return $json;
			}
			if ( ! is_array( $json ) ) {
				return new WP_Error( 'response_not_array' );
			}
			if ( empty( $json[ 'body'] ) ) {
				return new WP_Error( 'response_body_empty' );
			}

			// We did not get a 200 response
			$server_code = wp_remote_retrieve_response_code( $json );
			if ( $server_code !== 200 ) {
				return new WP_Error( 'not_200' , '' , $server_code );
			}

			// So Far, So Good...
			$json_response = json_decode( $json['body'] );
			if ( empty( $json_response ) ) {
				return new WP_Error( 'json_empty_inside' );
			}

			return $json_response;
		}

	}

	/**
	 * Make use - creates as a singleton attached to slplus->object['Internet_Helper']
	 *
	 * @var SLPlus  $slplus
	 */
	global $slplus;
	if ( is_a( $slplus , 'SLPlus' ) ) {
		$slplus->add_object( new SLP_Internet_Helper() );
	}
}