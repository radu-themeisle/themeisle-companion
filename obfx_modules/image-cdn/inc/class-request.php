<?php

namespace OrbitFox;
/**
 * A class for building an Authorization header for http requests.
 *
 */

class Request {
	protected $api_key = null;
	protected $api_url = null;
	protected $auth_header = 'Authorization';
	protected $extra_params = array();
	protected $method = null;

	public function __construct( $url, $method = 'GET', $api_key = '', $extra_params = array() ) {

		// The url for our custom endpoint, which returns network settings.
		$this->url = esc_url( $url );

		// All we really care about here is GET requests.
		$this->method = $method;

		if ( ! empty( $api_key ) ) {
			$this->api_key = $api_key;

			return;
		}
		$connect_data = get_option( 'obfx_connect_data' );

		if ( isset( $connect_data['api_key'] ) ) {
			$this->api_key = $connect_data['api_key'];
		}

	}


	/**
	 * Make an oauth'd http request.
	 *
	 * @return string|object The result of an oauth'd http request.
	 */
	public function get_response() {
		// Grab the url to which we'll be making the request.
		$url = $this->url;

		// If there is a extra, add that as a url var.
		if ( 'GET' === $this->method && ! empty( $this->extra_params ) ) {
			foreach ( $this->extra_params as $key => $val ) {
				$url = add_query_arg( array( $key => $val ), $url );
			}
		}

		// Args for wp_remote_*().
		$args     = array(
			'method'      => $this->method,
			'timeout'     => 45,
			'httpversion' => '1.0',
			'body'        => $this->extra_params,
			'sslverify'   => false,
			'headers'     => array(
				$this->auth_header => 'Bearer ' . $this->api_key,
			),
		);
		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}
		$response = wp_remote_retrieve_body( $response );

		if ( empty( $response ) ) {
			return false;
		}

		$response = json_decode( $response, true );

		if ( ! $response['code'] ) {
			return false;
		}
		if ( intval( $response['code'] ) !== 200 ) {
			return false;
		}

		return $response['data'];
	}

}