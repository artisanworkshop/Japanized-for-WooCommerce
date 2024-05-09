<?php
/**
 * JP4WC Admin
 *
 * @class    JP4WC_Admin
 * @package  JP4WC\Admin
 * @version  2.6.0
 */

 if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * JP4WC_Admin class.
 */
class JP4WC_Admin {

    /**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'current_screen', array( $this, 'conditional_includes' ), 1 );
    }

	/**
	 * Include any classes we need within admin.
	 */
    public function includes() {
        require_once JP4WC_INCLUDES_PATH . 'admin/class-jp4wc-admin-screen.php';
        require_once JP4WC_INCLUDES_PATH . 'admin/class-jp4wc-admin-product-meta.php';
    }

	/**
	 * Include admin files conditionally.
	 */
	public function conditional_includes() {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		switch ( $screen->id ) {
			case 'dashboard':
			case 'dashboard-network':
				include __DIR__ . '/class-jp4wc-admin-php-notice.php';
				break;
        }
    }
}
return new JP4WC_Admin();

