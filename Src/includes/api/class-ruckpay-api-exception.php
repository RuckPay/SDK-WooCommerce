<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RuckPay_Api_Exception extends \RuntimeException {
	private $response;

	public function __construct( $message, $code, $response ) {
		parent::__construct( $message, $code );

		$this->response = $response;
	}

	public function getResponse() {
		return $this->response;
	}
}
