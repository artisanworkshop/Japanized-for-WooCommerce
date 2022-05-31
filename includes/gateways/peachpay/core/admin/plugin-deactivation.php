<?php
/**
 * Enqueues the scripts and styles for the deactivation popup and adds the placeholder for the modal
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues CSS styles for the plugin deactivation feedback pop up.
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_deactivation_style( $hook ) {
	if ( 'plugins.php' !== $hook ) {
		return;
	}
	wp_enqueue_style(
		'peachpay-deactivation-feedback',
		peachpay_url( 'core/admin/assets/css/deactivation-feedback.css' ),
		array(),
		peachpay_file_version( 'core/admin/assets/css/deactivation-feedback.css' )
	);
}

add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_deactivation_style' );

/**
 * Enqueues scripts for the plugin deactivation feedback pop up modal as well
 * as root element for the pop up modal HTML.
 *
 * @param string $hook Page level hook.
 */
function peachpay_enqueue_deactivation_script( $hook ) {
	if ( 'plugins.php' !== $hook ) {
		return;
	}

	add_action( 'admin_footer', 'peachpay_add_feedback_modal' );

	wp_enqueue_script(
		'peachpay-deactivation-feedback',
		peachpay_url( 'core/admin/assets/js/deactivation-feedback.js' ),
		array(),
		peachpay_file_version( 'core/admin/assets/js/deactivation-feedback.js' ),
		true
	);

	wp_localize_script(
		'peachpay-deactivation-feedback',
		'deactivation_peachpay_data',
		array(
			'test_mode' => isset( $options['test_mode'] ) ? $options['test_mode'] : null,
		)
	);

	wp_enqueue_script(
		'peachpay-util',
		peachpay_url( 'core/admin/assets/js/util.js' ),
		array(),
		peachpay_file_version( 'core/admin/assets/js/util.js' ),
		true
	);

	$options = get_option( 'peachpay_general_options' );
}
add_action( 'admin_enqueue_scripts', 'peachpay_enqueue_deactivation_script' );

/**
 * Adds the div that will contain the deactivation form modal
 */
function peachpay_add_feedback_modal() {
	?>
		<div id="ppModal" class="ppModal"></div>
	<?php
}
