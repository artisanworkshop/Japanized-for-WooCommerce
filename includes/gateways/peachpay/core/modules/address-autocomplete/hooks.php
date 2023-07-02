<?php
/**
 * PeachPay address autocomplete Hooks.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

// add autcomplete feature data
add_filter( 'peachpay_register_feature', 'peachpay_address_autocomplete_feature_flag', 10, 1 );
