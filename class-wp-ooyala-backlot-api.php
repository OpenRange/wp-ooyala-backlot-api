<?php
/*
Copyright 2013   Range Systems, Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * This class enables you to communicate with Ooyala's API v2.
 */
class WP_Ooyala_Backlot_API {
  /**
	 * Holds the supported HTTP methods
	 *
	 * @var array
	 */
	private static $methods = array( 'GET', 'POST', 'DELETE', 'PUT', 'PATCH' );


	/**
	 * Holds the secret key, that can be found in the developers tab from
	 * Backlot (http://ooyala.com/backlot/web).
	 *
	 * @var string
	 */
	public $secret_key;

	/**
	 * Holds the api key, that can be found in the developers tab from Backlot
	 * (http://ooyala.com/backlot/web).
	 *
	 * @var string
	 */
	public $api_key;

	/**
	 * Holds the base URL where requests are going to be made to. Defaults to
	 * https://api.ooyala.com.
	 *
	 * @var string
	 */
	public $base_url;

	/**
	 * Holds the cache base URL where requests are going to be made to.
	 * Defaults to http://cdn.api.ooyala.com.
	 *
	 * @var string
	 */
	public $cache_base_url;

	/**
	 * Holds the expiration window. This value is added to the current time. It
	 * should be in seconds, and represent the time that the request is valid.
	 *
	 * @var int
	 */
	public $expiration_window;

	/**
	 * Holds the round up time.
	 *
	 * @var int
	 */
	public $round_up_time;

	/**
	 * Constructor. Takes the secret key, api key and args.
	 * 
	 * @param string $api_key Backlot's API key.
	 * @param string $secret_key Backlot's secret key.
	 * @param array  $args Optional. Extra options to override the base_url,
	 *                               cache_base_url and expiration_window. 
	 *                               These should be specified as the values 
	 *                               from the keys in this array.
	 */
	function __construct( $api_key, $secret_key, $args = array() ) {
		$defaults = array(
			'base_url'          => 'https://api.ooyala.com',
			'cache_base_url'    => 'http://cdn.api.ooyala.com',
			'expiration_window' => 15,
			'round_up_time'     => 300
		);
		$args = wp_parse_args( $args, $defaults );

		$this->api_key           = $api_key;
		$this->secret_key        = $secret_key;
		$this->base_url          = $args['base_url'];
		$this->cache_base_url    = $args['cache_base_url'];
		$this->expiration_window = $args['expiration_window'];
		$this->round_up_time     = $args['round_up_time'];
	}

	/**
	 * Generates a GET request to the Ooyala API.
	 * @param string $path   The path of the resource from the request.
	 * @param array  $params The associative array with GET parameters.
	 *                        Defaults to array().
	 * @return string the response body.
	 * @throws WP_Error if an error occurs.
	 */
	public function get( $path, $params = array() ) {
		return $this->request( 'GET', $path, $params );
	}

	/**
	 * Generates a POST request to the Ooyala API.
	 * @param string $path   The path of the resource from the request.
	 * @param array  $body   The POST data to send. Defaults to array().
	 * @param array  $params The associative array with GET parameters. 
	 *                       Defaults to array().
	 * @return string the response body.
	 * @throws WP_Error if an error occurs.
	 */
	public function post( $path, $body = array(), $params = array() ) {
		$body = empty( $body ) ? '' : json_encode( $body );
		return $this->send_request( 'POST', $path, $params, $body );
	}

	/**
	 * Generates a PUT request to the Ooyala API.
	 * @param string $path   The path of the resource from the request.
	 * @param array  $body   The POST data to send. Defaults to array().
	 * @param array  $params The associative array with GET parameters. 
	 *                       Defaults to array().
	 * @return string the response body.
	 * @throws WP_Error if an error occurs.
	 */
	public function put( $path, $body = array(), $params = array() ) {
		$body = empty( $body ) ? '' : json_encode( $body );
		return $this->send_request( 'POST', $path, $params, $body );
	}

	/**
	 * Generates a PATCH request to the Ooyala API.
	 * @param string $path   The path of the resource from the request.
	 * @param array  $body   The POST data to send. Defaults to array().
	 * @param array  $params The associative array with GET parameters. 
	 *                       Defaults to array().
	 * @return string the response body.
	 * @throws WP_Error if an error occurs.
	 */
	public function put( $path, $body = array(), $params = array() ) {
		$body = empty( $body ) ? '' : json_encode( $body );
		return $this->send_request( 'PATCH', $path, $params, $body );
	}
	/**
	 * Generates the signature for a request. If the method is GET, then it does
	 * not need to add the body of the request to the signature. On the other
	 * hand, if it's either a POST, PUT or PATCH, the request body should be a
	 * JSON serialized object into a String. The resulting signature should be
	 * added as a GET parameter to the request.
	 *
	 * @param string $method Either GET, DELETE POST, PUT or PATCH.
	 * @param string $path   The path of the resource from the request.
	 * @param array  $params An associative array that contains GET params.
	 * @param string $body   The contents of the request body. Used when doing
	 *                       a POST, PATCH or PUT requests. Defaults to ''.
	 *
	 * @return string The signature that shiuld be added as a query parameter to
	 *                the URI of the request.
	 */
	public function generate_signature ( $method, $path, $params, $body = '' ) {
		$signature  = $this->secret_key . strtoupper( $method );
		$signature .= $path;
		ksort( $params );

		foreach( $params as $key => $value )
			$signature .= "$key=$value";

		$signature .= $body;

		$signature = base64_encode( hash( 'sha256', $signature, true ) );
		$signature = urlencode( substr( $signature, 0, 43 ) );
		return rtrim( $signature, '=' );
	}

	/**
	 * Builds the URL for a request, appends the query parameters.
	 *
	 * @param string $method Either GET, POST, PUT, DELETE or PATCH.
	 * @param string $path   The absolute path for the URL to build.
	 * @param array  $params An associative array with the parameters to add to 
	 *                       the URL. Defaults to array().
	 *
	 * @return string The built URL.
	 */
	public function build_url( $method, $path, $params = array() ) {
		$url = 'GET' ? $this->cache_base_url : $this->base_url;
		$url .= $path;
		$url = add_query_arg( $params, $url );

		return $url;
	}

	/**
	 * Sends a request to a given path using the passed HTTP Method.
	 *
	 * @param string $method Either GET, POST, PUT, DELETE or PATCH.
	 * @param string $path   The relative path of the request.
	 * @param array  $params An associative array with the parameters to add 
	 *                       to the URL. Defaults to array().
	 * @param string $body   The body of the request, used when doing a POST, 
	 *                       PUT or PATCH request. Defaults to ''.
	 *
	 * @return array The JSON parsed response if it was success or WP_Error.
	 */
	public function request( $method, $path, $params = array(), $body = '' ) {
		if( '/v2/' != substr( $path, 0, 4 ) )
			$path = '/v2/' . $path;

		$method = strtoupper( $method );
		if ( ! in_array( $method, self::$methods ) )
			return new WP_Error( 'unsupported', 'Method not supported.' );

		$params = $this->sanitize_params( $params );
		$params['signature'] = $this->generate_signature( $method, $path, $params, $body );
		$url = $this->build_url( $method, $path, $params );

		$response = wp_remote_request( $url, $params );
		if ( '200' == wp_remote_retrieve_response_code( $response ) )
			return json_decode( wp_remote_retrieve_body( $response ) );

		return new WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_response_message( $response ) );
	}

	private function sanitize_params( $params ) {
		foreach( $params as $key => $value )
			$params[$key] = urlencode( $value );

		if ( ! array_key_exists( 'expires', $params ) )
			$expiration  = time() + $this->expiration_window;
            $params['expires'] = $expiration + $this->round_up_time - ( $expiration % $this->round_up_time );

        if ( !array_key_exists( 'api_key', $params ) )
            $params['api_key'] = $this->api_key;

        return $params;
	}
}
