<?php
/**
 * Loads active modules.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/modules/field-editor/field-editor.php';
require_once PEACHPAY_ABSPATH . 'core/modules/recommended-products/pp-related-products.php';
require_once PEACHPAY_ABSPATH . 'core/modules/currency-switcher/currency-convert.php';
require_once PEACHPAY_ABSPATH . 'core/modules/product-links/product-links.php';

do_action( 'peachpay_setup_module' );
