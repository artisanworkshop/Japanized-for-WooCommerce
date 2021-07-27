<?php
/**
 * Plugin Name: Japanized for WooCommerce
 * Framework Name: Artisan Workshop FrameWork for WooCommerce
 * Framework Version : 2.0.11
 * Author: Artisan Workshop
 * Author URI: https://wc.artws.info/
 * Text Domain: woocommerce-for-japan
 *
 * @category JP4WC_Framework
 * @author Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

return apply_filters(
    'jp4wc_framework_config',
    array(
        'description_check_pattern' => __( 'Please check it if you want to use %s.', 'woocommerce-for-japan' ),
        'description_payment_pattern' => __( 'Please check it if you want to use the payment method of %s.', 'woocommerce-for-japan' ),
        'description_input_pattern' => __( 'Please input %s.', 'woocommerce-for-japan' ),
        'description_select_pattern' => __( 'Please select one from these as %s.', 'woocommerce-for-japan' ),
        'support_notice_01' => __( 'Need support?', 'woocommerce-for-japan' ),
        'support_notice_02' => __( 'If you are having problems with this plugin, talk about them in the <a href="%s" target="_blank" title="Pro Version">Support forum</a>.', 'woocommerce-for-japan' ),
        'support_notice_03' => __( 'If you need professional support, please consider about <a href="%1$s" target="_blank" title="Site Construction Support service">Site Construction Support service</a> or <a href="%2$s" target="_blank" title="Maintenance Support service">Maintenance Support service</a>.', 'woocommerce-for-japan' ),
        'pro_notice_01' => __( 'Pro version', 'woocommerce-for-japan' ),
        'pro_notice_02' => __( 'The pro version is available <a href="%s" target="_blank" title="Support forum">here</a>.', 'woocommerce-for-japan' ),
        'pro_notice_03' => __( 'The pro version includes support for bulletin boards. Please consider purchasing the pro version.', 'woocommerce-for-japan' ),
        'update_notice_01' => __( 'Finished Latest Update, WordPress and WooCommerce?', 'woocommerce-for-japan' ),
        'update_notice_02' => __( 'One the security, latest update is the most important thing. If you need site maintenance support, please consider about <a href="%s" target="_blank" title="Support forum">Site Maintenance Support service</a>.', 'woocommerce-for-japan' ),
        'community_info_01' => __( 'Where is the study group of Woocommerce in Japan?', 'woocommerce-for-japan' ),
        'community_info_02' => __( '<a href="%s" target="_blank" title="Tokyo WooCommerce Meetup">Tokyo WooCommerce Meetup</a>.', 'woocommerce-for-japan' ),
        'community_info_03' => __( '<a href="%s" target="_blank" title="Kansai WooCommerce Meetup">Kansai WooCommerce Meetup</a>.', 'woocommerce-for-japan' ),
        'community_info_04' => __('Join Us!', 'woocommerce-for-japan' ),
        'author_info_01' => __( 'Created by', 'woocommerce-for-japan' ),
        'author_info_02' => __( 'WooCommerce Doc in Japanese', 'woocommerce-for-japan' ),
        'framework_version' => JP4WC_FRAMEWORK_VERSION,
    )
);
