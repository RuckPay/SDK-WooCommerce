<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RuckPay_Transaction {
	private $order;

	public function __construct( $order ) {
		$this->order = $order;
	}

	public function create_internal_reference_if_not_exists() {
		$internal_reference = $this->order->get_meta( 'ruckpay_internal_reference' );

		if ( empty( $internal_reference ) ) {
			$internal_reference = wp_generate_uuid4();
			$this->order->update_meta_data( 'ruckpay_internal_reference', $internal_reference );
			$this->order->save_meta_data();
		}

		return $internal_reference;
	}

	public function get_references() {
		$this->create_internal_reference_if_not_exists();

		return [
			'internal' => $this->order->get_meta( 'ruckpay_internal_reference' ),
			'external' => $this->order->get_meta( 'ruckpay_external_reference' )
		];
	}
}
