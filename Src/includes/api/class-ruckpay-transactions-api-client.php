<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-ruckpay-api-client.php';
require_once __DIR__ . '/class-ruckpay-api-exception.php';

class RuckPay_Transaction_Api_Client extends RuckPay_Api_Client {
	public function get_transaction_data( string $transaction_id ): array {
		$response = $this->do_get_request( '/transactions/' . $transaction_id );

		if ( 200 !== $response['code'] ) {
			throw new RuckPay_Api_Exception( 'Invalid response code', $response['code'], $response['body'] );
		}

		if (null === $body = json_decode( $response['body'], true )) {
			throw new RuckPay_Api_Exception( 'Invalid response body', $response['code'], $response['body'] );
		}

		return $body;
	}
}
