<?php
/**
 * PeachPay Extension Admin Trait.
 *
 * @package PeachPay/Admin
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;


/**
 * Base for creating a PeachPay admin tab page.
 */
abstract class PeachPay_Admin_Tab extends WC_Settings_API {

	/**
	 * The id to store the admin settings with. (This should be unique).
	 *
	 * @var string
	 */
	public $id = '';

	/**
	 * Initializes the settings tab.
	 *
	 * @param boolean $settings_only Ensures rendering logic is not initialized when the class is being instantiated only for settings use.
	 *
	 * @throws Exception If the $id field is not set.
	 */
	final public function __construct( $settings_only = false ) {
		$this->plugin_id = 'peachpay_';
		if ( ! $this->id ) {
			throw new Exception( 'Developer Error: Tab id must be defined.' );
		}
		$this->id = $this->id . '_admin_settings';

		$this->form_fields = $this->register_form_fields();
		$this->init_settings();

		if ( ! $this->is_active() || $settings_only ) {
			return;
		}

		$this->hooks();
		$this->includes();
	}

	/**
	 * Initialize actions and filters . This should not be attempted to be overridden . Any custom hooks
	 * should be defined in a hooks . php file and loaded in in $this->includes() of the parent integration file .
	 * */
	private function hooks() {
		$tab_view = $this;

		add_action(
			'admin_enqueue_scripts',
			function() use ( $tab_view ) {
				$tab_view->enqueue_admin_scripts();
			}
		);
	}

	/**
	 * Register your form fields in this class.
	 */
	protected function register_form_fields() {
		return array();
	}

	/**
	 * Load extension specific public scripts here.
	 */
	protected function enqueue_admin_scripts() { }

	/**
	 * Gets the tab key.
	 */
	abstract public function get_tab();

	/**
	 * Gets the tab key.
	 */
	abstract public function get_section();

	/**
	 * Gets the title of the tab.
	 */
	abstract public function get_title();

	/**
	 * Gets the description of the tab.
	 */
	abstract public function get_description();


	/**
	 * Initialize classes and functions. This is probably the best place to
	 * load utility functions and admin settings related code.
	 */
	abstract protected function includes();

	/**
	 * Renders the Actual admin tab.
	 */
	public function do_admin_view() {
		?>
		<form method="POST" action="" enctype="multipart/form-data">
			<table class="form-table">
				<?php $this->generate_settings_html( $this->get_form_fields(), true ); ?>
			</table>
			<p class="submit">
				<button name="save" class="button-primary pp-button-primary"" type="submit" value="<?php esc_attr_e( 'Save changes', 'peachpay-for-woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'peachpay-for-woocommerce' ); ?></button>
				<?php wp_nonce_field( 'peachpay-settings' ); ?>
			</p>
		</form>
		<?php
	}

	/**
	 * Indicates if URL is visiting this tab.
	 */
	public function is_active() {
		// PHPCS:disable WordPress.Security.NonceVerification.Recommended
		$page_key    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		$section_key = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '';
		$tab_key     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
		// PHPCS:enable

		return is_admin() && 'peachpay' === $page_key && $this->get_section() === $section_key && $this->get_tab() === $tab_key;
	}

	/**
	 * Gets the URL of a tabbed view in this section.
	 */
	public static function get_url() {
		$settings_instance = new static( true );

		return admin_url( 'admin.php?page=peachpay&section=' . $settings_instance->get_section() . '&tab=' . $settings_instance->get_tab() );
	}

	/**
	 * Gets the desired settings value.
	 *
	 * @param string $setting The key of the setting to retrieve a value for.
	 */
	public static function get_setting( $setting = '' ) {
		$settings_instance = new static( true );
		return $settings_instance->get_option( $setting );
	}
}
