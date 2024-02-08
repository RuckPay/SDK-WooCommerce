<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class WC_Gateway_RuckPay extends WC_Payment_Gateway {

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->icon     = apply_filters( 'woocommerce_ruckpay_gateway_icon', '' );
		$this->supports = [
			'products'
		];

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title                    = $this->getTitle();
		$this->description              = $this->getDescription();
		$this->instructions             = $this->get_option( 'instructions', $this->description );
		$this->hide_for_non_admin_users = $this->get_option( 'hide_for_non_admin_users' );
		$this->enabled                  = $this->get_option( 'enabled' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = [
			'enabled'                  => [
				'title'   => __( 'Enable/Disable', 'ruckpay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable RuckPay Payments', 'ruckpay' ),
				'default' => 'yes',
			],
			'hide_for_non_admin_users' => [
				'title'   => __( 'Tests in production', 'ruckpay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Hide at checkout for non-admin users', 'ruckpay' ),
				'default' => 'no',
			]
		];
	}

	abstract protected function getTitle();

	abstract protected function getDescription();

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );
		$order->update_status( 'awaiting-payment');
		WC()->session->set( 'order_awaiting_payment', $order_id );

		$settings          = get_option( 'ruckpay_options' );
		$checkout_page_id  = $settings[ WC_RuckPay_Payments::CHECKOUT_PAGE ];
		$checkout_page_url = get_permalink( $checkout_page_id );

		// Redirect
		return [
			'result'   => 'success',
			'redirect' => $checkout_page_url
		];
	}

	public function can_refund_order( $order ) {
		return false;
	}

	public function is_available() {
		// includes hide_for_non_admin_users setting
		return parent::is_available() && ( current_user_can('administrator') || $this->get_option( 'hide_for_non_admin_users' ) === 'no' );
	}
}
