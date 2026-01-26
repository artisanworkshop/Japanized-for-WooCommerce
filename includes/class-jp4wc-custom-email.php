<?php
/**
 * Japanized for WooCommerce
 *
 * @package     JP4WC
 * @version     2.2.19
 * @category    Email Customize for Japan
 * @author      Artisan Workshop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * JP4WC_Custom_Email class.
 *
 * Handles custom email templates for Japanese localization.
 */
class JP4WC_Custom_Email {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_filter( 'wc_get_template', array( $this, 'jp4wc_custom_email_template' ), 11, 2 );
	}

	/**
	 * Look in this plugin for WooCommerce template overrides.
	 *
	 * For example, if you want to override woocommerce/templates/emails/customer-processing-order.php, you
	 * can place the modified template in <plugindir>/templates/custom-email/customer-processing-order.php
	 *
	 * @param string $located is the currently located template, if any was found so far.
	 * @param string $template_name is the name of the template (ex: cart/cart.php).
	 * @return string $located is the newly located template if one was found, otherwise
	 *                         it is the previously found template.
	 */
	public function jp4wc_custom_email_template( $located, $template_name ) {
		if ( get_option( 'wc4jp-custom-email-customer-name' ) ) {
			$change_template_array = array(
				// HTML templates.
				'emails/customer-completed-order.php',
				'emails/customer-invoice.php',
				'emails/customer-note.php',
				'emails/customer-on-hold-order.php',
				'emails/customer-processing-order.php',
				'emails/customer-refunded-order.php',
				// Plain Text.
				'emails/plain/customer-completed-order.php',
				'emails/plain/customer-invoice.php',
				'emails/plain/customer-note.php',
				'emails/plain/customer-on-hold-order.php',
				'emails/plain/customer-processing-order.php',
				'emails/plain/customer-refunded-order.php',
			);
			foreach ( $change_template_array as $custom_located ) {
				if ( $template_name === $custom_located ) {
					return JP4WC_ABSPATH . 'templates/' . $custom_located;
				}
			}
		}
		return $located;
	}
}
// Address Fields Class load.
new JP4WC_Custom_Email();
