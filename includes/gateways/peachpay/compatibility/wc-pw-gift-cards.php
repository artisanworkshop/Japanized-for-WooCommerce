<?php
/**
 * Support for the PW Woocommerce Gift Cards Plugin.
 * Plugin: https://www.pimwick.com/gift-cards/
 * Default Plugin Keys:
 *      pw-woocommerce-gift-cards/pw-gift-cards.php
 *      pw-gift-cards/pw-gift-cards.php
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Initializes support for PW Gift cards with PeachPay.
 */
function peachpay_pwgc_init() {
	add_filter( 'peachpay_script_data', 'peachpay_pwgc_add_script_data', 10, 1 );
	add_filter( 'peachpay_cart_applied_gift_cards', 'peachpay_pwgc_applied_gift_cards', 10, 1 );
	add_filter( 'peachpay_cart_applied_gift_card', 'peachpay_pwgc_gift_cards_card', 10, 2 );
}
add_action( 'peachpay_init_compatibility', 'peachpay_pwgc_init' );

/**
 * Adds meta data to the peachpay script data object.
 *
 * @param array $script_data The existing script data.
 */
function peachpay_pwgc_add_script_data( $script_data ) {
	$script_data['pw_gift_cards_apply_nonce']               = wp_create_nonce( 'pw-gift-cards-apply-gift-card' );
	$script_data['pw_gift_cards_remove_nonce']              = wp_create_nonce( 'pw-gift-cards-remove-card' );
	$script_data['cart_applied_gift_cards']                 = peachpay_cart_applied_gift_cards();
	$script_data['plugin_pw_woocommerce_gift_cards_active'] = true;
	return $script_data;
}

/**
 * Gets gift cards that are currently applied to the cart.
 */
function peachpay_pwgc_applied_gift_cards() {
	$cards = array();

	$session_data = (array) WC()->session->get( PWGC_SESSION_KEY );
	if ( ! isset( $session_data['gift_cards'] ) ) {
		return $cards;
	}

	foreach ( $session_data['gift_cards'] as $card_number => $balance ) {
		array_push(
			$cards,
			array(
				'card_number' => $card_number,
				'balance'     => $balance,
			)
		);
	}

	return $cards;
}

/**
 * Gets the current gift card balance object.
 *
 * @param string $gift_card Gift card object.
 * @param string $card_number The gift card number in the format of "xxxx-xxxx-xxxx-xxxx".
 */
function peachpay_pwgc_gift_cards_card( $gift_card, $card_number ) {
	$balance = ( new PW_Gift_Card( $card_number ) )->get_balance();

	if ( ! $balance ) {
		return new WP_Error( 'no_gift_card', 'Invalid gift card number', array( 'status' => 404 ) );
	}

	return array(
		'card_number' => $card_number,
		'balance'     => $balance,
	);
}

/**
 * Builds a record of applied gift cards toward a given cart.
 *
 * @param array   $record Existing coupons recorded.
 * @param WC_Cart $cart A given cart to check for applied gift cards.
 */
function peachpay_pwgc_record( $record, $cart ) {
	if ( isset( $cart->pwgc_calculated_total ) ) {
		$session_data = (array) WC()->session->get( PWGC_SESSION_KEY );

		$total = $cart->pwgc_total_gift_cards_redeemed;
		foreach ( $session_data['gift_cards']  as $card_number => $amount ) {
			$applied_amount         = peachpay_pwgc_gift_card_applied_amount( $card_number, $total );
			$total                 -= $applied_amount;
			$record[ $card_number ] = floatval( $applied_amount );
		}
	}
	return $record;
}
add_filter( 'peachpay_cart_applied_gift_cards_record', 'peachpay_pwgc_record', 10, 2 );

/**
 * Calculates the gift card applied amount.
 *
 * @param string $card_number The card to find the applied amount.
 * @param float  $total The current cart total.
 */
function peachpay_pwgc_gift_card_applied_amount( $card_number, $total ) {
	$pw_gift_card = new PW_Gift_Card( $card_number );
	$balance      = $pw_gift_card->get_balance();

	if ( $balance <= 0 ) {
		return 0;
	}

	if ( $balance > $total ) {
		return $total;
	}

	return $total - ( $total - $balance );
}
