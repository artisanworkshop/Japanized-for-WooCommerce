<?php
/**
 * Japanized for WooCommerce
 *
 * @version     2.2.17
 * @category    WooCommerce Subscriptions for Japan
 * @author      Artisan Workshop
 */
use \ArtisanWorkshop\WooCommerce\PluginFramework\v2_0_12 as Framework;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class JP4WC_Subscriptions{

    /**
     * Japanized for WooCommerce Framework.
     *
     * @var object
     */
    public $jp4wc_plugin;

    /**
     * Constructor
     */
    public function __construct() {
        $this->jp4wc_plugin = new Framework\JP4WC_Plugin();
        $this->prefix =  'wc4jp-';
        // Add subscription pricing fields on edit product page
        add_filter( 'woocommerce_subscriptions_product_price_string', array( $this, 'jp4wc_subscription_price_string' ), 10, 2 );
    }

    /**
     * Display of price in product display of subscription
     *
     * @param string $subscription_string
     * @param $product
     * @return mixed
     */
    public function jp4wc_subscription_price_string( string $subscription_string, $product){
        $price_string = get_post_meta( $product->get_id(), '_subscription_price_string', true );
        if($price_string){
            return $price_string;
        }
        return $subscription_string;
    }
}

if ( class_exists( 'WC_Subscriptions' ) ) {
    new JP4WC_Subscriptions();
}