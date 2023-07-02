<?php
/**
 * PeachPay Account Data Settings page.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * PeachPay Account Data Setting page.
 */
final class PeachPay_Account_Data extends PeachPay_Admin_Tab {

	/**
	 * The id to reference the stored settings with.
	 *
	 * @var string
	 */
	public $id = 'account_data';

	/**
	 * Gets the section url key.
	 */
	public function get_section() {
		return 'account';
	}

	/**
	 * Gets the tab url key.
	 */
	public function get_tab() {
		return 'data';
	}

	/**
	 * Gets the tab title.
	 */
	public function get_title() {
		return __( 'PeachPay data', 'peachpay-for-woocommerce' );
	}

	/**
	 * Gets the tab title.
	 */
	public function get_description() {
		return __( 'Configure PeachPay account settings.', 'peachpay-for-woocommerce' );
	}


	/**
	 * Include dependencies here.
	 */
	protected function includes() {}

	/**
	 * Register form fields here. This is optional but required if you want to display settings.
	 */
	protected function register_form_fields() {
		return array(
			'data_retention' => array(
				'type'        => 'checkbox',
				'label'       => __( 'Remove data on uninstall', 'peachpay-for-woocommerce' ),
				'description' => __( 'PeachPay settings and data will be removed if the plugin is uninstalled.', 'peachpay-for-woocommerce' ),
				'default'     => 'no',
				'class'       => 'toggle',
			),
		);
	}

	/**
	 * Renders the Admin page.
	 */
	public function do_admin_view() {
		?>
		<h1>
			<?php echo esc_html( $this->get_title() ); ?>
		</h1>
		<?php parent::do_admin_view(); ?>
		<?php

		settings_fields( 'peachpay_account_data_admin_settings' );
	}

	// TODO Remove enqueue_admin_scripts function and assets/css/account.css when https://www.notion.so/peachpay/Tabs-within-payment-gateway-settings-cdcd8c8efbd14fa68795750ff307472b task is completed.
	/**
	 * Attach to enqueue scripts hook.
	 */
	public function enqueue_admin_scripts() {
		PeachPay::enqueue_style(
			'peachpay-account-styles',
			'core/admin/assets/css/account.css',
			array()
		);
	}
}

if ( 'yes' === PeachPay_Account_Data::get_setting( 'data_retention' ) ) {
	update_option( 'peachpay_data_retention', 'yes' );
} elseif ( 'no' === PeachPay_Account_Data::get_setting( 'data_retention' ) ) {
	update_option( 'peachpay_data_retention', 'no' );
}
