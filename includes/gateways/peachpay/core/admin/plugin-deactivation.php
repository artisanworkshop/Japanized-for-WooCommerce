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
 * Enqueues admin.css
 */
function enqueue_deactivation_style() {
	wp_enqueue_style(
		'admin.css',
		peachpay_url( 'core/admin/assets/css/admin.css' ),
		array(),
		peachpay_file_version( 'core/admin/assets/css/admin.css' )
	);
}

add_action( 'admin_enqueue_scripts', 'enqueue_deactivation_style' );

/**
 * Enqueues deactivation.js and util.js and adds the feedback modal
 */
function enqueue_deactivation_script() {
	add_action( 'admin_footer', 'add_feedback_modal' );
	wp_enqueue_script(
		'peachpay-admin-feedback',
		peachpay_url( 'core/admin/assets/js/deactivation.js' ),
		array(),
		peachpay_file_version( 'core/admin/assets/js/deactivation.js' ),
		true
	);

	wp_enqueue_script(
		'peachpay-util',
		peachpay_url( 'core/admin/assets/js/util.js' ),
		array(),
		peachpay_file_version( 'core/admin/assets/js/util.js' ),
		true
	);

	$options = get_option( 'peachpay_general_options' );

	wp_localize_script(
		'peachpay-admin-feedback',
		'deactivation_peachpay_data',
		array( 'test_mode' => isset( $options['test_mode'] ) ? $options['test_mode'] : null )
	);
}

/**
 * Adds the div that will contain the deactivation form modal
 */
function add_feedback_modal() {
	?>
		<div id = "ppModal" class = "ppModal"></div>
	<?php
}

add_action( 'admin_enqueue_scripts', 'enqueue_deactivation_script' );
