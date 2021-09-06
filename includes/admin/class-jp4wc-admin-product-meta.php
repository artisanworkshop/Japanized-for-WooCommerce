<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.2.17
 * @package 	Product Meta
 * @author 		ArtisanWorkshop
 */
use \ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12 as Framework;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class JP4WC_Admin_Product_Meta {

    /**
     * Is meta boxes saved once?
     *
     * @var boolean
     * @since 2.2
     */
    private static $saved_product_meta = false;

    /**
     * Japanized for WooCommerce Framework.
     *
     * @var object
     */
	public $jp4wc_plugin;
	public $prefix;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->jp4wc_plugin = new Framework\JP4WC_Plugin();
		$this->prefix =  'wc4jp-';
        if ( class_exists( 'WC_Subscriptions' ) ){
            // Add subscription pricing fields on edit product page
            add_action( 'woocommerce_subscriptions_product_options_pricing', array( $this, 'jp4wc_subscription_pricing_fields' ) );
//            add_action( 'woocommerce_variable_subscription_pricing', array( $this, 'jp4wc_variable_subscription_pricing_fields'), 3 );
            // Save subscription meta only when a subscription product is saved, can't run on the "'woocommerce_process_product_meta_' . $product_type" action because we need to override some WC defaults
            add_action( 'save_post', array( $this, 'jp4wc_save_subscription_meta' ), 11 );
//            add_action( 'woocommerce_save_product_variation', array( $this, 'jp4wc_save_product_variation' ), 20, 2 );
        }
    }

    /**
     * Output the subscription specific pricing fields on the "Edit Product" admin page.
     */
    public function jp4wc_subscription_pricing_fields(){
        global $post;
        $price_string = get_post_meta( $post->ID, '_subscription_price_string', true );
        $price_string_tooltip = __( 'Change the price display in the product display of subscription.', 'woocommerce-for-japan' );

        do_action('before_jp4wc_subscription_pricing_fields', $post);

        // Subscription Price String
        ?><p class="form-field _subscription_price_string_fields _subscription_price_string_field">
        <label for="_subscription_price_string">
            <?php
            echo esc_html__( 'Subscription price string', 'woocommerce-for-japan' );
            ?>
        </label>
        <span class="wrap">
            <input type="text" id="_subscription_price_string" name="_subscription_price_string" class="wc_input_text" placeholder="<?php echo esc_attr_x( 'e.g. 5.90 per month', 'example price string', 'woocommerce-for-japan' ); ?>" value="<?php echo esc_attr( $price_string ); ?>" />
        </span>
        <?php echo wcs_help_tip( $price_string_tooltip ); ?>
        </p><?php

        do_action('after_jp4wc_subscription_pricing_fields', $post);
    }

    /**
     * Output the subscription specific pricing fields on the "Edit Product" admin page.
     *
     * @param int $loop
     * @param
     * @param int $variation
     * @since 2.2
     */
    public function jp4wc_variable_subscription_pricing_fields( int $loop, $variation_data, int $variation){
        $variation_product = wc_get_product( $variation );
        $variation_product_string = get_post_meta( $variation_product->ID, '_subscription_price_string', true );

        do_action('before_jp4wc_variable_subscription_pricing_fields' , $loop, $variation_data, $variation);

        ?><div class="variable_subscription_pricing_string  show_if_variable-subscription">
        <label for="variable_subscription_price[<?php echo esc_attr( $loop ); ?>]">
            <?php
            echo esc_html__( 'Subscription price string', 'woocommerce-for-japan' );
            ?>
        </label>
        <input type="text" class="wc_input_price_string wc_input_subscription_price_string" name="variable_subscription_price_string[<?php echo esc_attr( $loop ); ?>]" value="<?php echo esc_attr( $variation_product_string ); ?>" placeholder="<?php echo esc_attr_x( 'e.g. 5.90 per month', 'example price string', 'woocommerce-for-japan' ); ?>">
        </div>
        <?php
        do_action('after_jp4wc_variable_subscription_pricing_fields' , $loop, $variation_data, $variation);
    }

    /**
     * Save meta data for simple subscription product type when the "Edit Product" form is submitted.
     *
     * @param int post_id
     * @return array Array of Product types & their labels, including the Subscription product type.
     * @since 2.2
     */
    public static function jp4wc_save_subscription_meta( $post_id ) {

        if ( empty( $_POST['_wcsnonce'] ) || ! wp_verify_nonce( $_POST['_wcsnonce'], 'wcs_subscription_meta' ) || false === self::is_subscription_product_save_request( $post_id, apply_filters( 'woocommerce_subscription_product_types', array( WC_Subscriptions::$name ) ) ) ) {
            return;
        }

        $subscription_price_string = isset( $_REQUEST['_subscription_price_string'] ) ? wc_clean( $_REQUEST['_subscription_price_string'] ) : '';
        update_post_meta( $post_id, '_subscription_price_string', $subscription_price_string );

        // To prevent running this function on multiple save_post triggered events per update. Similar to JP4WC_Admin_Product_Meta:$saved_meta_boxes implementation.
        self::$saved_product_meta = true;
    }

    /**
     * Save meta data for variable subscription product type when the "Edit Product" form is submitted.
     *
     * @param int $variation_id
     * @param int $index
     * return void
     * @since 2.2
     */
    public static function jp4wc_save_product_variation( int $variation_id, int $index ){
        if ( ! WC_Subscriptions_Product::is_subscription( $variation_id ) || empty( $_POST['_wcsnonce_save_variations'] ) || ! wp_verify_nonce( $_POST['_wcsnonce_save_variations'], 'wcs_subscription_variations' ) ) {
            return;
        }
        if ( isset( $_POST['variable_subscription_price_string'][ $index ] ) ) {
            $subscription_price_string = wc_format_decimal( $_POST['variable_subscription_price_string'][ $index ] );
            update_post_meta( $variation_id, '_subscription_price_string', $subscription_price_string );
        }
    }

    /**
     * Check if subscription product meta data should be saved for the current request.
     *
     * @param int $post_id
     * @param array Array of product types.
     * @return bool
     * @since 2.2
     */
    private static function is_subscription_product_save_request( int $post_id, $product_types ) {

        if ( self::$saved_product_meta ) {
            $is_subscription_product_save_request = false;
        } elseif ( empty( $_POST['_wcsnonce'] ) || ! wp_verify_nonce( $_POST['_wcsnonce'], 'wcs_subscription_meta' ) ) {
            $is_subscription_product_save_request = false;
        } elseif ( ! isset( $_POST['product-type'] ) || ! in_array( $_POST['product-type'], $product_types ) ) {
            $is_subscription_product_save_request = false;
        } elseif ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
            $is_subscription_product_save_request = false;
        } else {
            $is_subscription_product_save_request = true;
        }

        return apply_filters( 'wcs_admin_is_subscription_product_save_request', $is_subscription_product_save_request, $post_id, $product_types );
    }
}

new JP4WC_Admin_Product_Meta();