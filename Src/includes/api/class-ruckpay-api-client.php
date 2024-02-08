<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class RuckPay_Api_Client {
	const API_URL = 'https://api.ruckpay.com';

	private $private_key;

	public function __construct( $private_key ) {
		$this->private_key = $private_key;
	}

	protected function do_get_request( $path ) {
		$curl = curl_init();

		curl_setopt_array( $curl, array(
			CURLOPT_URL            => static::API_URL . $path,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'GET',
			CURLOPT_HTTPHEADER     => array(
				'Accept: application/json',
				'Content-Type: application/json',
				'X-API-KEY: ' . $this->private_key
			),
		) );

		$response  = curl_exec( $curl );
		$http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

		curl_close( $curl );

		return [
			'code' => $http_code,
			'body' => $response
		];
	}
}
