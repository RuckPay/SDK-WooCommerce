<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-wc-gateway-ruckpay.php';

class WC_Gateway_RuckPay_Card extends WC_Gateway_RuckPay {
	public function __construct() {
		$this->id                 = 'ruckpay_card';
		$this->has_fields         = false;
		$this->method_title       = _x( 'RuckPay Bank Card', 'RuckPay', 'ruckpay' );
		$this->method_description = __( 'Allows Bank Card payment with RuckPay', 'ruckpay' );

		parent::__construct();
	}

	protected function getTitle() {
		return __('Bank Card (with RuckPay)', 'ruckpay');
	}

	protected function getDescription() {
		return __('Pay with your Bank Card (with RuckPay).', 'ruckpay');
	}
}