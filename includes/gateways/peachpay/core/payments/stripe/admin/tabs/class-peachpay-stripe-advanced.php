<?php
/**
 * PeachPay Stripe Advanced settings.
 *
 * @package PeachPay/Stripe/Admin
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . '/core/abstract/class-peachpay-admin-tab.php';

/**
 * PeachPay advanced Stripe settings.
 */
final class PeachPay_Stripe_Advanced extends PeachPay_Admin_Tab {
	/**
	 * The id to reference the stored settings with.
	 *
	 * @var string
	 */
	public $id = 'stripe_advanced';

	/**
	 * Gets the section url key.
	 */
	public function get_section() {
		return 'stripe';
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
		return __( 'Stripe advanced settings', 'peachpay-for-woocommerce' );
	}

	/**
	 * Gets the tab title.
	 */
	public function get_description() {
		return __( 'Configure additional options for Stripe through PeachPay.', 'peachpay-for-woocommerce' );
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
			'refund_on_cancel'     => array(
				'type'        => 'checkbox',
				'title'       => __( 'Refund on cancel', 'peachpay-for-woocommerce' ),
				'description' => __( 'Automatically refund the payment when the order status is changed to cancelled.', 'peachpay-for-woocommerce' ),
				'default'     => 'no',
			),
			'dispute_created'      => array(
				'type'        => 'checkbox',
				'title'       => __( 'Dispute created', 'peachpay-for-woocommerce' ),
				'description' => __( 'If enabled, the plugin will listen for the <strong>charge.dispute.created</strong> webhook event and set the order\'s status to cancelled by default.', 'peachpay-for-woocommerce' ),
				'default'     => 'no',
			),
			'dispute_order_status' => array(
				'type'        => 'select',
				'title'       => __( 'Disputed created order status', 'peachpay-for-woocommerce' ),
				'description' => __( 'The status assigned to an order when a dispute is created.', 'peachpay-for-woocommerce' ),
				'options'     => array(
					'on-hold'   => __( 'On hold', 'peachpay-for-woocommerce' ),
					'cancelled' => __( 'Cancelled', 'peachpay-for-woocommerce' ),
					'refunded'  => __( 'Refunded', 'peachpay-for-woocommerce' ),
				),
				'default'     => 'cancelled',
			),
			'dispute_closed'       => array(
				'type'        => 'checkbox',
				'title'       => __( 'Dispute closed', 'peachpay-for-woocommerce' ),
				'description' => __( 'If enabled, the plugin will listen for the <strong>charge.dispute.closed</strong> webhook event and set the order\'s status back to the status before the dispute was opened, if the dispute is won. If the dispute is lost, it will set the order status to Failed.', 'peachpay-for-woocommerce' ),
				'default'     => 'no',
			),
			'statement_descriptor' => array(
				'type'        => 'text',
				'title'       => __( 'Statement descriptor', 'peachpay-for-woocommerce' ),
				'description' => __( 'Set a custom statement descriptor that will appear on your customer\'s card or bank statements. If left blank, it will default to what you have set in Stripe for card payments.', 'peachpay-for-woocommerce' ),
				'default'     => '',
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
				$gateway_list = PeachPay_Stripe_Integration::get_payment_gateways();
				require PeachPay::get_plugin_path() . '/core/admin/views/html-gateways.php';
			?>
			</div>
		<?php
		settings_fields( 'peachpay_stripe_advanced_admin_settings' );
	}
}
