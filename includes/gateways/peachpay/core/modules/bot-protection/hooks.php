<?php
/**
 * Bot protection Hooks.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

add_filter( 'peachpay_register_feature', 'peachpay_bot_protection_feature_flag', 10, 1 );

add_action( 'woocommerce_checkout_process', 'peachpay_captcha_validation' );
