<?php
/**
 * phpcs:ignoreFile
 *
 * Defines expectations for the migrations in migrate-settings-after-reorg.php
 */

/**
 * Returns all settings options from the previous version that are migrated in migrate-settings-after-reorg.php and tested in settingsOptionsTest.php.
 */
function peachpay_get_pre_migration_options( $fill ) {
    $payment_options_keys         = array(
        'test_mode',
        'refund_on_cancel',
        'make_pp_the_only_checkout',
        'paypal',
        'paypal_auto_convert',
        'peachpay_authnet_test_login_id',
        'peachpay_authnet_test_transaction_key',
        'peachpay_authnet_test_signature_key',
        'peachpay_authnet_login_id',
        'peachpay_authnet_transaction_key',
        'peachpay_authnet_signature_key',
        'authnet_enable',
        'amazonpay_enable',
    );
    $related_products_keys        = array(
        'display_woocommerce_linked_products',
        'peachpay_product_relation',
        'peachpay_rp_nproducts',
        'peachpay_exclude_id',
        'peachpay_recommended_products_manual',
        'peachpay_rp_mini_slider',
        'peachpay_rp_mini_slider_header',
        'peachpay_related_enable',
        'peachpay_related_slider',
        'peachpay_related_nproducts',
        'peachpay_related_title',
    );
    $general_options_keys         = array(
        'language',
        'enable_order_notes',
        'data_retention',
        'merchant_logo',
        'enable_store_support_message',
        'support_message_type',
        'support_message',
        'display_product_images',
        'enable_quantity_changer',
        'enable_virtual_product_fields',
        'address_autocomplete',
        'use_wc_country_locale'
    );
    $button_options_keys          = array(
        'peachpay_button_text',
        'button_color',
        'button_text_color',
        'button_border_radius',
        'button_icon',
        'button_effect',
        'button_display_payment_method_icons',
        'disable_default_font_css',
        'display_on_product_page',
        'button_width_product_page',
        'product_button_alignment',
        'product_button_mobile_position',
        'product_button_position',
        'cart_page_enabled',
        'button_width_cart_page',
        'cart_button_alignment',
        'checkout_page_enabled',
        'button_width_checkout_page',
        'display_checkout_outline',
        'checkout_header_text',
        'checkout_subtext_text',
        'mini_cart_enabled',
        'floating_button_enabled',
        'floating_button_icon',
        'floating_button_size',
        'floating_button_icon_size',
        'floating_button_alignment',
        'floating_button_bottom_gap',
        'floating_button_side_gap',
        'button_shadow_enabled'
    );
    $ocu_options_keys             = array(
        'peachpay_one_click_upsell_enable',
        'peachpay_one_click_upsell_flow',
        'peachpay_one_click_upsell_display_all',
        'peachpay_display_one_click_upsell',
        'peachpay_one_click_upsell_products',
        'peachpay_one_click_upsell_primary_header',
        'peachpay_one_click_upsell_secondary_header',
        'peachpay_one_click_upsell_custom_description',
        'peachpay_one_click_upsell_accept_button_text',
        'peachpay_one_click_upsell_decline_button_text'
    );
    $advanced_options_keys        = array(
        'custom_button_class',
        'custom_button_css',
        'custom_checkout_js',
        'custom_target_product_page',
        'custom_target_cart_page',
        'custom_target_checkout_page'
    );

    $all_options = array(
        'peachpay_payment_options'          => array_fill_keys( $payment_options_keys, $fill ),
        'peachpay_related_products_options' => array_fill_keys( $related_products_keys, $fill ),
        'peachpay_general_options'          => array_fill_keys( $general_options_keys, $fill ),
        'peachpay_button_options'           => array_fill_keys( $button_options_keys, $fill ),
        'peachpay_one_click_upsell_options' => array_fill_keys( $ocu_options_keys, $fill ),
        'peachpay_advanced_options'         => array_fill_keys( $advanced_options_keys, $fill )
    );

    return $all_options;
}

function peachpay_get_expected_payment_keys() {
    return array(
        'test_mode',
        'refund_on_cancel',
        'make_pp_the_only_checkout',
        'paypal',
        'paypal_auto_convert',
        'peachpay_authnet_test_login_id',
        'peachpay_authnet_test_transaction_key',
        'peachpay_authnet_test_signature_key',
        'peachpay_authnet_login_id',
        'peachpay_authnet_transaction_key',
        'peachpay_authnet_signature_key',
        'authnet_enable',
        'amazonpay_enable',
        'data_retention'
    );
}

function peachpay_get_expected_recommended_products_keys() {
    return array(
        'peachpay_related_enable',
        'peachpay_related_slider',
        'peachpay_related_nproducts',
        'peachpay_related_title'
    );
}

function peachpay_get_expected_express_checkout_branding_keys() {
    return array(
        'language',
        'merchant_logo',
        'button_color',
        'button_text_color');
}

function peachpay_get_expected_ec_window_keys() {
    return array(
        'make_pp_the_only_checkout',
        'display_product_images',
        'enable_quantity_changer',
        'enable_virtual_product_fields',
        'address_autocomplete',
        'use_wc_country_locale',
        'enable_store_support_message',
        'support_message_type',
        'support_message',
        'enable_order_notes',
        'button_shadow_enabled'
    );
}

function peachpay_get_expected_ec_product_recommendations_keys() {
    return array(
        'display_woocommerce_linked_products',
        'peachpay_rp_nproducts',
        'peachpay_exclude_id',
        'peachpay_rp_mini_slider',
        'peachpay_rp_mini_slider_header',
        'peachpay_product_relation',
        'peachpay_recommended_products_manual',
        'peachpay_one_click_upsell_display_all',
        'peachpay_display_one_click_upsell',
        'peachpay_one_click_upsell_flow',
        'peachpay_one_click_upsell_products',
        'peachpay_one_click_upsell_primary_header',
        'peachpay_one_click_upsell_secondary_header',
        'peachpay_one_click_upsell_custom_description',
        'peachpay_one_click_upsell_accept_button_text',
        'peachpay_one_click_upsell_decline_button_text',
        'peachpay_one_click_upsell_enable'
    );
}

function peachpay_get_expected_ec_button_keys() {
    return array(
        'product_button_position',
        'product_button_mobile_position',
        'button_icon',
        'floating_button_icon',
        'button_border_radius',
        'peachpay_button_text',
        'button_effect',
        'product_button_alignment',
        'cart_button_alignment',
        'floating_button_alignment',
        'floating_button_bottom_gap',
        'floating_button_side_gap',
        'floating_button_size',
        'floating_button_icon_size',
        'cart_page_enabled',
        'checkout_page_enabled',
        'mini_cart_enabled',
        'floating_button_enabled',
        'button_display_payment_method_icons',
        'disable_default_font_css',
        'display_on_product_page',
        'display_checkout_outline',
        'checkout_header_text',
        'checkout_subtext_text',
        'button_width_product_page',
        'button_width_cart_page',
        'button_width_checkout_page'
    );
}

function peachpay_get_expected_ec_advanced_keys() {
    return array(
        'custom_button_class',
        'custom_button_css',
        'custom_checkout_js',
        'custom_target_product_page',
        'custom_target_cart_page',
        'custom_target_checkout_page'
    );
}

/**
 * Defines which PeachPay options have default values on a fresh install.
 */
function peachpay_options_with_defaults() {
    return array(
        'peachpay_express_checkout_button' => array(
            'display_on_product_page',
            'cart_page_enabled',
            'checkout_page_enabled',
            'mini_cart_enabled',
            'floating_button_enabled',
            'product_button_alignment',
            'product_button_mobile_position',
            'product_button_position',
            'cart_button_alignment',
            'floating_button_icon',
            'floating_button_alignment',
        ),
        'peachpay_payment_options' => array(
            'data_retention',
        ),
    );
}

/**
 * Defines the expected default options on a fresh install.
 */
function peachpay_get_expected_options_on_fresh_install() {
    $expectations = array(
        'peachpay_express_checkout_button' => array(
            'display_on_product_page'        => 0,
            'cart_page_enabled'              => 0,
            'checkout_page_enabled'          => 0,
            'mini_cart_enabled'              => 0,
            'floating_button_enabled'        => 0,
            'product_button_alignment'       => 'left',
            'product_button_mobile_position' => 'default',
            'product_button_position'        => 'beforebegin',
            'cart_button_alignment'          => 'full',
            'floating_button_icon'           => 'shopping_cart',
            'floating_button_alignment'      => 'right',
        ),
        'peachpay_payment_options' => array(
            'data_retention' => 1,
        ),
    );
    return $expectations;
}
