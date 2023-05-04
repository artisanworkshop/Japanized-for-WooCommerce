<?php
/**
 * PeachPay PayPal Advanced settings.
 *
 * @package PeachPay/PayPal/Admin
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . '/core/abstract/class-peachpay-admin-tab.php';

/**
 * PeachPay advanced PayPal settings.
 */
final class PeachPay_PayPal_Advanced extends PeachPay_Admin_Tab {

	/**
	 * The id to reference the stored settings with.
	 *
	 * @var string
	 */
	public $id = 'paypal_advanced';

	/**
	 * Gets the section url key.
	 */
	public function get_section() {
		return 'paypal';
	}

	/**
	 * Gets the tab url key.
	 */
	public function get_tab() {
		return 'advanced';
	}

	/**
	 * Gets the tab title.
	 */
	public function get_title() {
		return __( 'PayPal advanced settings', 'peachpay-for-woocommerce' );
	}

	/**
	 * Gets the tab title.
	 */
	public function get_description() {
		return __( 'Configure additional options for PayPal through PeachPay.', 'peachpay-for-woocommerce' );
	}


	/**
	 * Include dependencies here.
	 */
	protected function includes() {}

	/**
	 * Register form fields here. This is optional but required if you want to display settings.
	 */
	protected function register_form_fields() {
		return array(
			'store_name'             => array(
				'type'        => 'text',
				'title'       => __( 'Store name', 'peachpay-for-woocommerce' ),
				'description' => __( 'The name of the store displayed in the PayPal window.', 'peachpay-for-woocommerce' ),
				'default'     => get_bloginfo( 'name' ),
			),
			'refund_on_cancel'       => array(
				'type'        => 'checkbox',
				'title'       => __( 'Refund on cancel', 'peachpay-for-woocommerce' ),
				'description' => __( 'Automatically refund the payment when the order status is changed to cancelled.', 'peachpay-for-woocommerce' ),
				'default'     => 'no',
			),
			'itemized_order_details' => array(
				'type'        => 'checkbox',
				'title'       => __( 'Order details (experimental)', 'peachpay-for-woocommerce' ),
				'label'       => __( 'Show itemized order details in the PayPal window', 'peachpay-for-woocommerce' ),
				'description' => __( 'Show line items in the PayPal window. This setting is not compatible with the WooCommerce tax setting "Prices entered with tax: Yes, I will enter prices inclusive of tax".', 'peachpay-for-woocommerce' ),
				'default'     => 'no',
			),
		);
	}

	/**
	 * Renders the Admin page.
	 */
	public function do_admin_view() {
		parent::do_admin_view()
		?>
			<div>
			<?php
				$gateway_list = PeachPay_PayPal_Integration::get_payment_gateways();
				require PeachPay::get_plugin_path() . '/core/admin/views/html-gateways.php';
			?>
			</div>
		<?php
	}
}
