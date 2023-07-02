<?php
/**
 * PeachPay utility functions
 *
 * @package PeachPay
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gets updated script fragments.
 *
 * @param array $fragments .
 */
function peachpay_native_checkout_data_fragment( $fragments ) {
	$fragments['script#peachpay-native-checkout-js-extra'] = '<script id="peachpay-native-checkout-js-extra">var peachpay_checkout_data = ' . wp_json_encode( peachpay()->native_checkout_data() ) . ';</script>';
	return $fragments;
}

/**
 * Stores deactivation feedback results.
 */
function peachpay_handle_deactivation_feedback() {
	if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['security'] ) ), 'peachpay-deactivation-feedback' ) ) {
		return wp_send_json(
			array(
				'success' => false,
				'message' => 'Invalid nonce. Please refresh the page and try again.',
			)
		);
	}

	$deactivation_reason      = isset( $_POST['deactivation_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['deactivation_reason'] ) ) : null;
	$deactivation_explanation = isset( $_POST['deactivation_explanation'] ) ? sanitize_text_field( wp_unslash( $_POST['deactivation_explanation'] ) ) : null;

	$feedback = array(
		'deactivation_reason' => $deactivation_reason,
	);

	if ( $deactivation_explanation ) {
		$feedback['deactivation_explanation'] = $deactivation_explanation;
	}

	update_option( 'peachpay_deactivation_feedback', $feedback );

	wp_send_json_success();
}

/**
 * Gets if the gateway has a service fee.
 *
 * @param string $gateway_id The gateway Id.
 */
function peachpay_gateway_has_service_fee( $gateway_id ) {
	if ( peachpay_starts_with( $gateway_id, 'peachpay_stripe_' ) ) {
		return true;
	}

	if ( peachpay_starts_with( $gateway_id, 'peachpay_square_' ) ) {
		return true;
	}

	if ( peachpay_starts_with( $gateway_id, 'peachpay_paypal_' ) ) {
		return true;
	}

	return false;
}

/**
 * Gets the PeachPay service fee label.
 *
 * Note: we append a zero-width joiner (&zwj;) to the label to allow for unique identification of the fee line item.
 * This is currently used to append a tooltip after the fee name.
 *
 * @return string
 */
function peachpay_get_service_fee_label() {
	return __( 'Service fee', 'peachpay-for-woocommerce' ) . '‍';
}

/**
 * Adds the PeachPay service fee to the cart.
 *
 * @param WC_Cart $cart The cart object.
 */
function peachpay_add_service_fee( $cart ) {
	if ( ! PeachPay::service_fee_enabled() ) {
		return;
	}

	if ( ! WC() || ! isset( WC()->session ) || ! WC()->session->get( 'chosen_payment_method' ) ) {
		return;
	}

	$current_payment_gateway = WC()->session->get( 'chosen_payment_method' );
	if ( ! peachpay_gateway_has_service_fee( $current_payment_gateway ) ) {
		return;
	}

	// Calculate the fee amount as percentage of the cart (subtotal + shipping total) - discounts
	$fee_amount = ( ( $cart->get_subtotal() + $cart->get_shipping_total() ) - $cart->get_discount_total() ) * PeachPay::service_fee_percentage();

	if ( $fee_amount < 0.01 ) {
		return;
	}

	$cart->add_fee( peachpay_get_service_fee_label(), round( $fee_amount, 2 ), false );
}

/**
 * Adds the PeachPay service fee to the order dashboard view.
 *
 * @param WC_Order $order The order object.
 */
function peachpay_display_service_fee_tooltip( $order ) {
	if ( did_action( 'woocommerce_admin_order_data_after_billing_address' ) > 1 ) {
		return;
	}

	// translators: %s is the service fee percentage for the given order.
	$tooltip_message = sprintf( __( 'PeachPay charges a %s%% service fee to the customer. As a merchant, you don’t pay anything extra.', 'peachpay-for-woocommerce' ), PeachPay_Order_Data::get_peachpay( $order, 'service_fee_percentage' ) * 100 );
	?>
	<script>
		document.addEventListener("DOMContentLoaded", function(event) {
			for (const feeLabel of Array.from(document.querySelectorAll('tr.fee td.name div.view'))) {
				if (feeLabel.innerHTML.trim() !== '<?php echo peachpay_get_service_fee_label();//PHPCS:ignore ?>') {
					continue;
				}

				feeLabel.innerHTML += '<?php echo wc_help_tip( $tooltip_message ); //PHPCS:ignore ?>';
			}
		})
	</script>
	<?php
}


/**
 * Synchronizes the service fee configuration with the PeachPay API.
 *
 * @param array $plugin_capabilities The plugin capabilities.
 */
function peachpay_sync_service_fee_configuration( $plugin_capabilities ) {

	if ( ! isset( $plugin_capabilities['service_fee']['connected'] ) || ! $plugin_capabilities['service_fee']['connected'] ) {
		update_option( 'peachpay_service_fee_enabled', 'no' );
		return;
	}

	$service_fee_percentage = isset( $plugin_capabilities['service_fee']['account']['fee_percentage'] ) ? $plugin_capabilities['service_fee']['account']['fee_percentage'] : 0.015;

	update_option( 'peachpay_service_fee_enabled', 'yes' );
	update_option( 'peachpay_service_fee_percentage', $service_fee_percentage );
}
