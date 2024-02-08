<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * RuckPay Payments Blocks integration
 */
class WC_Gateway_RuckPay_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_RuckPay
	 */
	protected $gateway = null;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'ruckpay_card';

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	public function initialize() {
		$this->gateway = new WC_Gateway_RuckPay_Card();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/frontend/block.js';
		$script_asset_path = WC_RuckPay_Payments::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require( $script_asset_path )
			: [
				'dependencies' => [],
				'version'      => '1.0.0'
			];
		$script_url        = WC_RuckPay_Payments::plugin_url() . $script_path;

		wp_register_script(
			'wc-ruckpay-payments',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-ruckpay-payments-blocks',
				'ruckpay',
				WC_RuckPay_Payments::plugin_abspath() . 'languages/' );
		}

		return [ 'wc-ruckpay-payments' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$options = get_option( 'ruckpay_options' );

		return array_intersect_key(
			       $options,
			       array_flip( [
				       WC_RuckPay_Payments::CHECKOUT_PAGE
			       ] )
		       ) + [
			       'title'       => $this->gateway->get_title(),
			       'description' => $this->gateway->get_description(),
			       'supports'    => $this->gateway->supports
		       ];
	}
}