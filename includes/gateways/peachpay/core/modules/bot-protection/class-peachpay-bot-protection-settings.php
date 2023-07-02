<?php
/**
 * PeachPay Bot protection Settings page.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * PeachPay Bot protection Settings page.
 */
final class PeachPay_Bot_Protection_Settings extends PeachPay_Admin_Tab {

	/**
	 * The id to reference the stored settings with.
	 *
	 * @var string
	 */
	public $id = 'bot_protection';

	/**
	 * Gets the section url key.
	 */
	public function get_section() {
		return 'settings';
	}

	/**
	 * Gets the tab url key.
	 */
	public function get_tab() {
		return 'bot_protection';
	}

	/**
	 * Gets the tab title.
	 */
	public function get_title() {
		return __( 'Bot protection', 'peachpay-for-woocommerce' );
	}

	/**
	 * Gets the tab title.
	 */
	public function get_description() {
		return __( 'Protect your WooCommerce checkout from bots, fraudulent credit cards, and automated purchases using Google reCAPTCHA technology.', 'peachpay-for-woocommerce' );
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
			'enabled'    => array(
				'type'        => 'checkbox',
				'title'       => 'Enable',
				'label'       => ' ',
				'description' => __( 'Enable Google reCAPTCHA v3. This will prevent payments from going through if a user is determined to be a bot. reCAPTCHA v3 works behind the scenes and does not display a puzzle on the checkout.', 'peachpay-for-woocommerce' ),
				'default'     => 'no',
				'class'       => 'toggle',
			),
			'site_key'   => array(
				'title' => __( 'Site key', 'peachpay-for-woocommerce' ),
				'type'  => 'text',
			),
			'secret_key' => array(
				'title' => __( 'Secret key', 'peachpay-for-woocommerce' ),
				'type'  => 'text',
			),
		);
	}

	/**
	 * Renders the Admin page.
	 */
	public function do_admin_view() {
		?>
		<h1>
			<?php
			echo esc_html( $this->get_title() );
			?>
		</h1>
		<p>
			<?php echo esc_html( $this->get_description() ); ?>
		</p>
		<p>
			<?php esc_html_e( 'If you don’t already have a key, you can ', 'peachpay-for-woocommerce' ); ?><a href="https://www.google.com/recaptcha/admin/create" target="_blank"><?php esc_html_e( 'get a reCAPTCHA v3 key for free', 'peachpay-for-woocommerce' ); ?></a>
		</p>
			<?php parent::do_admin_view(); ?>
		<?php
	}
}
