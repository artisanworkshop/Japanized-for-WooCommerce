<?php
/**
 * Enqueues the scripts and styles for the deactivation popup and adds the placeholder for the modal
 *
 * @package PeachPay
 */

/**
 * Enqueues admin.css
 */
function enqueue_deactivation_style() {
	wp_enqueue_style(
		'admin.css',
		plugin_dir_url( __DIR__ ) . '../css/admin.css',
		array(),
		peachpay_file_version( 'css/admin.css' )
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
		plugin_dir_url( __DIR__ ) . '../js/admin/deactivation.js',
		array(),
		peachpay_file_version( 'js/admin/deactivation.js' ),
		true
	);

	wp_enqueue_script(
		'peachpay-util',
		plugin_dir_url( __DIR__ ) . '../js/util.js',
		array(),
		peachpay_file_version( 'js/util.js' ),
		true
	);

	$options = get_option( 'peachpay_general_options' );

	wp_localize_script(
		'peachpay-admin-feedback',
		'deactivation_php_data',
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
