<?php
/**
 * Plugin Name: RuckPay Payments for WooCommerce
 * Plugin URI: https://www.ruckpay.com
 * Description: Accept and process all your payments with a single solution anywhere in the world. Furthermore, thanks to RuckPay’s payment solution you will improve your customers’ experience and your acceptance rate thanks to fast and clear online payments.
 * Version: 1.0.2
 * Author: RuckPay
 * Author URI: https://github.com/RuckPay/SDK-WooCommerce
 * Requires at least: 6.4.2
 * Tested up to: 6.4.2
 * WC requires at least: 8.2
 * WC tested up to: 8.2
 * Requires PHP: 7.0
 * Text Domain: ruckpay
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Exit if accessed directly.
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * WC RuckPay Payment gateway plugin class.
 *
 * @class WC_RuckPay_Payments
 */
class WC_RuckPay_Payments {

	/* Configuration constants */
	const MODE = 'mode';
	const TEST_KEY = 'test_key';
	const TEST_SECRET = 'test_secret';
	const LIVE_KEY = 'live_key';
	const LIVE_SECRET = 'live_secret';
	const CHECKOUT_PAGE = 'checkout_page';

	/* Mode constants */
	const MODE_TEST = 'test';
	const MODE_LIVE = 'live';

	public static function plugin_activation() {
		// Checks is cURL is installed
		if ( ! function_exists( 'curl_version' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( 'Sorry, this plugin requires the cURL PHP extension to be installed.',
				'plugin-dependency-error',
				array( 'back_link' => true ) );
		}

		// Checks is WooCommerce is installed
		if ( ! in_array( 'woocommerce/woocommerce.php',
			apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( 'Sorry, this plugin requires WooCommerce to be installed.',
				'plugin-dependency-error',
				array( 'back_link' => true ) );
		}

		$options = get_option( 'ruckpay_options' );
		if ( empty( $options[ self::CHECKOUT_PAGE ] ) || ! get_post( $options[ self::CHECKOUT_PAGE ] ) ) {
			$checkout_page = wp_insert_post( [
				'post_title'     => 'RuckPay Checkout',
				'post_content'   => '[ruckpay_payment_iframe]',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_author'    => 1,
				'comment_status' => 'closed',
				'ping_status'    => 'closed'
			] );

			if ( ! $checkout_page ) {
				deactivate_plugins( plugin_basename( __FILE__ ) );
				wp_die( 'Unable to create checkout page.', 'plugin-dependency-error', array( 'back_link' => true ) );
			}

			$options[ self::CHECKOUT_PAGE ] = $checkout_page;
			update_option( 'ruckpay_options', $options );
		}
	}

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {
		add_action( 'before_woocommerce_init', function () {
			if ( class_exists( FeaturesUtil::class ) ) {
				FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__ );
			}
		} );

		add_filter( 'load_textdomain_mofile', [ __CLASS__, 'load_translations' ], 10, 2 );
		load_plugin_textdomain( 'ruckpay', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		add_filter( 'woocommerce_payment_gateways', [ __CLASS__, 'add_gateways' ] );
		add_filter( 'plugin_action_links_woocommerce-gateway-ruckpay/woocommerce-gateway-ruckpay.php',
			[ __CLASS__, 'add_settings_link' ] );

		add_action( 'plugins_loaded', [ __CLASS__, 'includes' ], 0 );
		add_action( 'admin_init', [ __CLASS__, 'init_admin' ] );
		add_action( 'admin_menu', [ __CLASS__, 'ruckpay_options_page' ] );
		add_action( 'woocommerce_blocks_loaded',
			[ __CLASS__, 'woocommerce_gateway_ruckpay_woocommerce_block_support' ] );

		add_shortcode( 'ruckpay_payment_iframe', [ __CLASS__, 'ruckpay_payment_iframe' ] );
	}

	public static function load_translations( $mofile, $domain ) {
		if ( 'ruckpay' === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
			$locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
			$mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
		}

		return $mofile;
	}

	public static function add_settings_link( $links ) {
		$url = esc_url( add_query_arg(
			'page',
			'ruckpay',
			get_admin_url() . 'options-general.php'
		) );

		$settings_link = "<a href='$url'>" . __( 'Settings' ) . '</a>';

		array_unshift(
			$links,
			$settings_link
		);

		return $links;
	}

	public static function init_admin() {
		// Register settings.
		register_setting( 'ruckpay', 'ruckpay_options',
			[
				'type'        => 'array',
				'description' => __( 'RuckPay Settings', 'ruckpay' ),
				'default'     => [
					self::MODE          => self::MODE_TEST,
					self::TEST_KEY      => '',
					self::TEST_SECRET   => '',
					self::LIVE_KEY      => '',
					self::LIVE_SECRET   => '',
					self::CHECKOUT_PAGE => ''
				]
			]
		);

		// Register a new section in the "ruckpay" page.
		add_settings_section(
			'ruckpay_settings_section',
			__( 'RuckPay\'s Configuration', 'ruckpay' ),
			[ __CLASS__, 'ruckpay_section_settings_callback' ],
			'ruckpay'
		);

		// Register a new field in the "ruckpay_settings_section" section, inside the "ruckpay" page.
		add_settings_field(
			'ruckpay_field_mode',
			// Use $args' label_for to populate the id inside the callback.
			__( 'Mode', 'ruckpay' ),
			[ __CLASS__, 'ruckpay_field_mode_callback' ],
			'ruckpay',
			'ruckpay_settings_section',
			[
				'label_for'           => 'ruckpay_mode',
				'class'               => 'ruckpay_row',
				'ruckpay_custom_data' => 'custom'
			]
		);

		add_settings_field(
			'ruckpay_field_test_key',
			// Use $args' label_for to populate the id inside the callback.
			__( 'Test Key', 'ruckpay' ),
			[ __CLASS__, 'ruckpay_field_test_key_callback' ],
			'ruckpay',
			'ruckpay_settings_section',
			[
				'label_for'           => 'ruckpay_test_key',
				'class'               => 'ruckpay_row',
				'ruckpay_custom_data' => 'custom'
			]
		);

		add_settings_field(
			'ruckpay_field_test_secret',
			// Use $args' label_for to populate the id inside the callback.
			__( 'Test Secret', 'ruckpay' ),
			[ __CLASS__, 'ruckpay_field_test_secret_callback' ],
			'ruckpay',
			'ruckpay_settings_section',
			[
				'label_for'           => 'ruckpay_test_secret',
				'class'               => 'ruckpay_row',
				'ruckpay_custom_data' => 'custom'
			]
		);

		add_settings_field(
			'ruckpay_field_live_key',
			// Use $args' label_for to populate the id inside the callback.
			__( 'Live Key', 'ruckpay' ),
			[ __CLASS__, 'ruckpay_field_live_key_callback' ],
			'ruckpay',
			'ruckpay_settings_section',
			[
				'label_for'           => 'ruckpay_live_key',
				'class'               => 'ruckpay_row',
				'ruckpay_custom_data' => 'custom'
			]
		);

		add_settings_field(
			'ruckpay_field_live_secret',
			// Use $args' label_for to populate the id inside the callback.
			__( 'Live Secret', 'ruckpay' ),
			[ __CLASS__, 'ruckpay_field_live_secret_callback' ],
			'ruckpay',
			'ruckpay_settings_section',
			[
				'label_for'           => 'ruckpay_live_secret',
				'class'               => 'ruckpay_row',
				'ruckpay_custom_data' => 'custom'
			]
		);

		add_settings_field(
			'ruckpay_field_checkout_page',
			// Use $args' label_for to populate the id inside the callback.
			__( 'Checkout page', 'ruckpay' ),
			[ __CLASS__, 'ruckpay_field_checkout_page_callback' ],
			'ruckpay',
			'ruckpay_settings_section',
			[
				'label_for'           => 'ruckpay_checkout_page',
				'class'               => 'ruckpay_row',
				'ruckpay_custom_data' => 'custom'
			]
		);
	}

	public static function ruckpay_section_settings_callback() {
		echo '<p>' . __( 'These settings are necessary to make the RuckPay\'s payment gateway work.',
				'ruckpay' ) . '</p>';
	}

	public static function ruckpay_field_mode_callback() {
		$options = get_option( 'ruckpay_options' );

		$isTest = true;
		if ( isset( $options[ self::MODE ] ) ) {
			$isTest = $options[ self::MODE ] === self::MODE_TEST;
		}

		echo '<select name="ruckpay_options[' . self::MODE . ']" id="ruckpay_mode">'
		     . '<option value="' . self::MODE_TEST . '" ' . ( $isTest ? 'selected="selected"' : '' ) . '>' . __( 'Test',
				'ruckpay' ) . '</option>'
		     . '<option value="' . self::MODE_LIVE . '" ' . ( ! $isTest ? 'selected="selected"' : '' ) . '>' . __( 'Live',
				'ruckpay' ) . '</option>'
		     . '</select>';
	}

	public static function ruckpay_field_test_key_callback() {
		$options = get_option( 'ruckpay_options' );

		$key = '';
		if ( isset( $options[ self::TEST_KEY ] ) ) {
			$key = $options[ self::TEST_KEY ];
		}

		echo '<input type="text" name="ruckpay_options[' . self::TEST_KEY . ']" id="ruckpay_test_key" value="' . $key . '" class="regular-text" />';
	}

	public static function ruckpay_field_test_secret_callback() {
		$options = get_option( 'ruckpay_options' );

		$secret = '';
		if ( isset( $options[ self::TEST_SECRET ] ) ) {
			$secret = $options[ self::TEST_SECRET ];
		}

		echo '<input type="text" name="ruckpay_options[' . self::TEST_SECRET . ']" id="ruckpay_test_secret" value="' . $secret . '" class="regular-text" />';
	}

	public static function ruckpay_field_live_key_callback() {
		$options = get_option( 'ruckpay_options' );

		$key = '';
		if ( isset( $options[ self::LIVE_KEY ] ) ) {
			$key = $options[ self::LIVE_KEY ];
		}

		echo '<input type="text" name="ruckpay_options[' . self::LIVE_KEY . ']" id="ruckpay_live_key" value="' . $key . '" class="regular-text" />';
	}

	public static function ruckpay_field_live_secret_callback() {
		$options = get_option( 'ruckpay_options' );

		$secret = '';
		if ( isset( $options[ self::LIVE_SECRET ] ) ) {
			$secret = $options[ self::LIVE_SECRET ];
		}

		echo '<input type="text" name="ruckpay_options[' . self::LIVE_SECRET . ']" id="ruckpay_live_secret" value="' . $secret . '" class="regular-text" />';
	}

	public static function ruckpay_options_page() {
		add_options_page(
			'RuckPay',
			'RuckPay',
			'manage_options',
			'ruckpay',
			[ __CLASS__, 'ruckpay_options_page_html' ]
		);
	}

	/**
	 * Top level menu callback function
	 */
	public static function ruckpay_options_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		settings_errors( 'ruckpay_messages' );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1><hr>';

		echo '<div style="text-align: left; margin-bottom: 20px;">';
		echo '<img src="' . plugins_url( 'resources/img/logo-complete.png',
				__FILE__ ) . '" alt="RuckPay" style="width: 300px; height: auto;" />';
		echo '</div>';

		echo '<form action="options.php" method="post">';
		settings_fields( 'ruckpay' );
		do_settings_sections( 'ruckpay' );
		submit_button( __( 'Save' ) );
		echo '</div>';
	}

	private static function proceed_payment( $external_reference ) {
		$order = wc_get_order( WC()->session->get( 'order_awaiting_payment' ) );
		if ( ! $order ) {
			return;
		}

		require_once __DIR__ . '/includes/api/class-ruckpay-transactions-api-client.php';

		$options = get_option( 'ruckpay_options' );

		$client = new RuckPay_Transaction_Api_Client(
			$options[ self::MODE ] === self::MODE_TEST
				? $options[ self::TEST_SECRET ]
				: $options[ self::LIVE_SECRET ]
		);

		try {
			$transaction_data = $client->get_transaction_data( $external_reference );

			if ( ! self::is_valid_transaction( $transaction_data, $order, $external_reference ) ) {
				$order->update_status( 'failed' );

				return $order->get_checkout_payment_url();
			}

			$order->update_meta_data( 'ruckpay_external_reference', $external_reference );
			$order->save_meta_data();
			$order->payment_complete();
			wc_reduce_stock_levels( $order->get_id() );
			WC()->cart->empty_cart();

			$order->update_status( 'completed' );

			return $order->get_checkout_order_received_url();
		} catch ( RuckPay_Api_Exception $e ) {
			$order->update_status( 'failed' );

			return $order->get_checkout_payment_url();
		}

		exit;
	}

	private static function is_valid_transaction( $transaction_data, $order, $external_reference ) {
		$options = get_option( 'ruckpay_options' );

		return $transaction_data['transaction_id'] === $external_reference
		       && $transaction_data['reference'] === $order->get_meta( 'ruckpay_internal_reference' )
		       && $transaction_data['live'] === ( $options[ self::MODE ] === self::MODE_LIVE )
		       && $transaction_data['errorcode'] === 'NONE'
		       && $transaction_data['status'] === 'OK'
		       && isset( $transaction_data['amount']['currency'] )
		       && $transaction_data['amount']['currency'] === $order->get_currency()
		       && isset( $transaction_data['amount']['value'] )
		       && $transaction_data['amount']['value'] * 100 === $order->get_total() * 100;
	}

	public static function ruckpay_field_checkout_page_callback() {
		$pages   = get_pages();
		$options = get_option( 'ruckpay_options' );

		$selected_page = $options['checkout_page'];

		echo '<select name="ruckpay_options[' . self::CHECKOUT_PAGE . ']" id="ruckpay_checkout_page">';

		echo '<option value="">' . __( 'Select a page', 'ruckpay' ) . '</option>';

		foreach ( $pages as $page ) {
			echo '<option value="' . $page->ID . '" ' . ( $page->ID == $selected_page ? 'selected="selected"' : '' ) . '>' . $page->post_title . '</option>';
		}

		echo '</select>';
	}

	/**
	 * Add the RuckPay Payment gateways to the list of available gateways.
	 *
	 * @param array
	 */
	public static function add_gateways( $gateways ) {
		$options = get_option( 'woocommerce_ruckpay_settings', [] );

		if ( isset( $options['hide_for_non_admin_users'] ) ) {
			$hide_for_non_admin_users = $options['hide_for_non_admin_users'];
		} else {
			$hide_for_non_admin_users = 'no';
		}

		if ( ( 'yes' === $hide_for_non_admin_users && current_user_can( 'manage_options' ) ) || 'no' === $hide_for_non_admin_users ) {
			$gateways[] = 'WC_Gateway_RuckPay_Card';
		}

		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes() {
		require_once 'includes/class-wc-gateway-ruckpay-card.php';
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	public static function ruckpay_payment_iframe() {
		if ( isset( $_POST['external_reference'] ) ) {
			$newUrl = self::proceed_payment( $_POST['external_reference'] );
			if ( ! headers_sent() ) {
				wp_redirect( $newUrl );
			} else {
				echo '<script type="text/javascript">';
				echo 'window.location.href="' . $newUrl . '";';
				echo '</script>';
				echo '<noscript>';
				echo '<meta http-equiv="refresh" content="0;url=' . $newUrl . '" />';
				echo '</noscript>';
			}

			exit;
		}

		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}

		// Get current order
		$order = wc_get_order( WC()->session->get( 'order_awaiting_payment' ) );
		if ( ! $order ) {
			return;
		}

		// Get order amount
		$order_amount = $order->get_total();
		if ( ! $order_amount ) {
			return;
		}

		// Get order currency
		$order_currency = $order->get_currency();
		if ( ! $order_currency ) {
			return;
		}

		require_once __DIR__ . '/includes/class-ruckpay-transaction.php';
		$internal_reference = ( new RuckPay_Transaction( $order ) )->create_internal_reference_if_not_exists();

		$html = '';

		// Order summary
		// $html .= '<div style="text-align: center; margin-bottom: 20px;">';
		// $html .= '<img src="' . plugins_url( 'resources/img/logo-complete.png', __FILE__ ) . '" alt="RuckPay" style="width: 200px; height: auto; display:inline;" />';
		// $html .= '</div>';

		// Display order amount and transaction reference
		$html .= '<div id="ruckpay-checkout-summary">';
		$html .= '<table>';
		$html .= '<tr class="woocommerce-table__line-item order_item">';
		$html .= '<td class="woocommerce-table__product-name product-name">';
		$html .= '<strong>' . __( 'Order amount', 'ruckpay' ) . ' : </strong>';
		$html .= '</td>';
		$html .= '<td class="woocommerce-table__product-total product-total">';
		$html .= '<span class="woocommerce-Price-amount amount">' . $order_amount . ' </span>';
		$html .= '<span class="woocommerce-Price-currencySymbol">' . $order_currency . '</span>';
		$html .= '<span id="ruckpay_order_amount"></span>';
		$html .= '</span>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '<tr class="woocommerce-table__line-item order_item">';
		$html .= '<td class="woocommerce-table__product-name product-name">';
		$html .= '<strong>' . __( 'Transaction reference', 'ruckpay' ) . ' : </strong>';
		$html .= '</td>';
		$html .= '<td class="woocommerce-table__product-total product-total">';
		$html .= '<span id="ruckpay_transaction_reference">' . $internal_reference . '</span>';
		$html .= '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		$html .= '</div>';

		$html .= '<p class="alert alert-danger ruckpay-error" style="display: none"></p>';

		wp_enqueue_script(
			'ruckpay-lib',
			'https://cdn.ruckpay.com/lib/js/ruckpay.js'
		);

		wp_enqueue_style(
			'ruckpay-lib',
			'https://cdn.ruckpay.com/lib/js/ruckpay.css'
		);

		wp_enqueue_style(
			'ruckpay-style',
			WC_RuckPay_Payments::plugin_url() . '/resources/css/checkout.css'
		);

		wp_enqueue_script(
			'wc-ruckpay-payments-checkout',
			WC_RuckPay_Payments::plugin_url() . '/resources/js/frontend/checkout.js',
			[ 'jquery' ]
		);

		$html .= '<div id="ruckpay_iframe_area"></div>';

		$data = [];

		$options = get_option( 'ruckpay_options' );

		$data['settings'] = array_intersect_key(
			$options,
			array_flip( [
				WC_RuckPay_Payments::MODE,
				WC_RuckPay_Payments::TEST_KEY,
				WC_RuckPay_Payments::LIVE_KEY
			] )
		);

		$data['settings']['checkout_page_url'] = get_permalink( $options['checkout_page'] );

		$data['order'] = [
			'id'       => $internal_reference,
			'amount'   => $order_amount,
			'currency' => $order_currency,
			'billing'  => [
				'first_name' => $order->get_billing_first_name(),
				'last_name'  => $order->get_billing_last_name(),
				'address'    => $order->get_billing_address_1(),
				'city'       => $order->get_billing_city(),
				'country'    => $order->get_billing_country(),
				'zip'        => $order->get_billing_postcode()
			],
			'shipping' => [
				'first_name' => $order->get_shipping_first_name(),
				'last_name'  => $order->get_shipping_last_name(),
				'address'    => $order->get_shipping_address_1(),
				'city'       => $order->get_shipping_city(),
				'country'    => $order->get_shipping_country(),
				'zip'        => $order->get_shipping_postcode()
			]
		];

		$data['locale'] = get_locale();

		wp_localize_script(
			'wc-ruckpay-payments-checkout',
			'ruckpay_payment_data',
			$data
		);

		// Cancel link
		$html .= '<div class="ruckpay-buttons-area">';
		$html .= '<button id="submit_payment_button" class="button alt wp-element-button"></button>';
		$html .= '<a id="cancel_payment_link" href="' . wc_get_checkout_url() . '" class="button alt" style="text-decoration:none;">' . __( 'Cancel', 'ruckpay' ) . '</a>';
		$html .= '</div>';



		return $html;
	}

	public static function woocommerce_gateway_ruckpay_woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once __DIR__ . '/includes/blocks/class-wc-gateway-ruckpay-blocks-support.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Gateway_RuckPay_Blocks_Support() );
				}
			);
		}
	}
}

register_activation_hook( __FILE__, [ 'WC_RuckPay_Payments', 'plugin_activation' ] );
WC_RuckPay_Payments::init();