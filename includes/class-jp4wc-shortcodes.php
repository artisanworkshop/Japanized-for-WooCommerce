<?php
/**
 * Japanized for WooCommerce
 * Shortcodes
 *
 * @version     2.2.17
 * @category    Shortcodes
 * @author      Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * WooCommerce Shortcodes class.
 */
class JP4WC_Shortcodes{

    /**
     * __construct function.
     *
     * @access public
     * @return void
     */
    function __construct() {
        $this->init();
    }
    /**
     * Init shortcodes.
     */
    public static function init(){
        $shortcodes = array(
            'jp4wc_law' => __CLASS__ . '::jp4wc_law',
        );

        foreach ($shortcodes as $shortcode => $function) {
            add_shortcode(apply_filters("{$shortcode}_jp4wc_shortcode_tag", $shortcode), $function);
        }
    }

    /**
     * Cart page shortcode.
     *
     * @return string
     */
    public static function jp4wc_law(){
        $prefix =  'wc4jp-';
        $law_options = array(
            'law-shop-name',
            'law-company-name',
            'law-owner-name',
            'law-location',
            'law-contact',
            'law-price',
            'law-payment',
            'law-purchase',
            'law-delivery',
            'law-cost',
            'law-return',
            'law-special',
	        'law-tel',
        );
        $laws_array = array(
            __( 'Shop Name', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[0]),
            __( 'Sales company name (company name)', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[1]),
            __( 'Owner Name', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[2]),
            __( 'Location', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[3]),
            __( 'Contact', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[4]),
            __( 'Telephone', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[12]),
            __( 'Selling price', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[5]),
            __( 'Payment method', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[6]),
            __( 'Product purchase method', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[7]),
            __( 'Product delivery time', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[8]),
            __( 'Costs other than product charges', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[9]),
            __( 'Returns / Cancellations', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[10]),
            __( 'Special conditions', 'woocommerce-for-japan' ) => get_option($prefix.$law_options[11]),
        );
        $content = '<table class="wp-block-table is-style-stripes">
    <tbody>';
        $allowed_html = array(
            'a' => array( 'href' => array (), 'target' => array(), ),
            'br' => array(),
            'strong' => array(),
            'b' => array(),
        );
        foreach($laws_array as $key => $value){
            if($value){
                $content .= '        <tr><th>'.esc_attr( $key ).'</th><td>'.wp_kses( $value, $allowed_html ).'</td></tr>'."\n";
            }elseif($key != __( 'Special conditions', 'woocommerce-for-japan' ) && $key != __( 'Telephone', 'woocommerce-for-japan' )){
                $no_content[] = $key;
            }
        }
        $content .= '    </tbody>
</table>';
        if( isset( $no_content ) ){
            $count = count($no_content);
            $i = 0;
            $no_content_names = '';
            foreach ($no_content as $key => $value){
                $i++;
                if($count === $i){
                    $no_content_names .= $value;
                }else{
                    $no_content_names .= $value.__( ' and ', 'woocommerce-for-japan' );
                }
            }
            $content = '<p>'.__( 'The following items of "Notation based on the Specified Commercial Transactions Law" have not been entered. Please enter the item of "Notation based on Specified Commercial Transactions Law" from the management screen.', 'woocommerce-for-japan' );
            $content .= '<br />'.esc_attr( $no_content_names ).'</p>';
        }
        return $content;
    }
}
new JP4WC_Shortcodes();