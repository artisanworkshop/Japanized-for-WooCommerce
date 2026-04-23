<?php
/**
 * Japanized for WooCommerce
 *
 * @package     JP4WC
 * @version     2.9.7
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
		add_filter( 'woocommerce_order_get_billing_first_name', array( $this, 'jp4wc_email_get_billing_full_name' ), 10, 2 );
	}

	/**
	 * Get billing full name for email templates.
	 *
	 * Combines last name and first name for Japanese localization in email templates.
	 *
	 * @param string        $first_name The billing first name.
	 * @param WC_Order|null $order The order object.
	 * @return string The combined full name or empty string if not in email context.
	 */
	public function jp4wc_email_get_billing_full_name( $first_name, $order = null ) {
		if ( get_option( 'wc4jp-custom-email-customer-name' ) ) {
			// Check if we are in an email context.
			if ( $this->is_email_context() ) {
				// Get last name from order object.
				if ( $order && is_a( $order, 'WC_Order' ) ) {
					$last_name = $order->get_billing_last_name();
					return $last_name . ' ' . $first_name;
				}
			}
		}
		return $first_name;
	}

	/**
	 * Check if we are in an email sending context.
	 *
	 * @return bool True if in email context, false otherwise.
	 */
	private function is_email_context() {
		// Primary check: WC_Email::get_content() sets $sending = true before rendering.
		// This covers both classic PHP templates and the WC 10.7+ BlockEmailRenderer path.
		if ( function_exists( 'WC' ) && WC()->mailer() ) {
			foreach ( WC()->mailer()->get_emails() as $email ) {
				if ( $email->sending ) {
					return true;
				}
			}
		}

		// Fallback: check if WC_Email is anywhere in the call stack.
		if ( function_exists( 'debug_backtrace' ) ) {
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 20 ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
			foreach ( $backtrace as $trace ) {
				if ( isset( $trace['class'] ) && false !== strpos( $trace['class'], 'WC_Email' ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
// Address Fields Class load.
new JP4WC_Custom_Email();
