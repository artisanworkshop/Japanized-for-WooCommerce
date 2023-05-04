<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://peachpay.app
 * @since      1.0.0
 *
 * @package    Woo_Related_Products
 * @subpackage Woo_Related_Products/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Related_Products
 * @subpackage Woo_Related_Products/public
 * @author     EBOXNET.com <info@eboxnet.com>
 */
class Peachpay_Related_Products_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Related_Products_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Related_Products_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-related-products-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Related_Products_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Related_Products_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		if ( peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_slider' ) ) {
			wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-related-products-public.js', array( 'jquery' ), $this->version, false );

			/*
			* owl-slider
			*/
			wp_register_script( 'owl-carousel', plugin_dir_url( __FILE__ ) . 'owl-carousel/owl.carousel.min.js', '', 1, true );
			wp_enqueue_script( 'owl-carousel' );
			wp_enqueue_style( 'owl-carousel-stylesheet', plugin_dir_url( __FILE__ ) . 'owl-carousel/owl.carousel.css', '', 1 );

		}
	}


	/**
	 * Remove default related products.
	 *
	 * @since    1.0.0
	 */
	public function disable_woocommerce_related() {

		/**
		 * This function disables default woocommerce related products.
		 */

		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

		if ( function_exists( 'wc_remove_related_products' ) ) {
			add_filter( 'woocommerce_related_products_args', 'wc_remove_related_products', 10 );
		}

	}
	/**
	 * Remove default related products.
	 *
	 * @since    1.0.0
	 */
	public function related_settings_check() {

		/**
		 * Removed the value check to reduce functions from 2 to 1 still things to do in here
		 * there is only one function now so theres no reason to check things, just adding the action
		 */

	}

}
