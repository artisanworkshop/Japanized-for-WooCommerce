<?php
/**
 * Handles Apple Pay domain registration
 *
 * @package peachpay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * PeachPay class to check and handle domain registration with apple pay
 */
class PeachPay_Stripe_Apple_Pay {

	const DOMAIN_ASSOCIATION_FILE_NAME = 'apple-developer-merchantid-domain-association';
	const DOMAIN_ASSOCIATION_FILE_DIR  = '.well-known';

	/**
	 * Array containing information about
	 * apple pay settings.
	 *
	 * @var array
	 */
	public $apple_pay_settings;

	/**
	 * Whether the apple pay domain is set.
	 *
	 * @var bool
	 */
	public $apple_pay_domain_set;

	/**
	 * Current domain name.
	 *
	 * @var bool
	 */
	private $domain_name;

	/**
	 * Constructor function for Stripe Apple Pay registration.
	 */
	public function __construct() {
		add_action( 'init', [$this, 'add_domain_rewrite_rule'] ); // phpcs:ignore
		add_action( 'admin_init', [$this, 'check_domain_on_domain_change'] ); // phpcs:ignore
		add_action( 'admin_init', [$this, 'update_domain_association_file']); // phpcs:ignore

		add_filter( 'query_vars', [$this, 'whitelist_domain_association_query_param'], 10, 1 ); // phpcs:ignore
		add_action( 'parse_request', [$this, 'parse_domain_association_request'], 10, 1 ); // phpcs:ignore

		$this->apple_pay_settings   = get_option( 'peachpay_apple_pay_settings', [] ); // phpcs:ignore
		$this->domain_name          = str_replace( [ 'https://', 'http://' ], '', get_site_url() ); // phpcs:ignore
		$this->apple_pay_domain_set = 'yes' === $this->get_option( 'apple_pay_domain_set', 'no' );

	}

	/**
	 * Returns a value from Stripe Settings.
	 *
	 * @param string $setting Setting to check.
	 * @param string $default Default value.
	 * @return string $value
	 */
	public function get_option( $setting = '', $default = '' ) {
		if ( empty( $this->apple_pay_settings ) ) {
			return $default;
		}

		if ( ! empty( $this->apple_pay_settings[ $setting ] ) ) {
			return $this->apple_pay_settings[ $setting ];
		}

		return $default;
	}

	/**
	 * Adds the domain rewrite rule for fetching the association file.
	 */
	public function add_domain_rewrite_rule() {
		$regex    = '^\\' . self::DOMAIN_ASSOCIATION_FILE_DIR . '\/' . self::DOMAIN_ASSOCIATION_FILE_NAME . '$';
		$redirect = 'index.php?' . self::DOMAIN_ASSOCIATION_FILE_NAME . '=1';

		add_rewrite_rule( $regex, $redirect, 'top' );
	}

	/**
	 * Handles getting the query vars when fetching association file.
	 *
	 * @param array $query_vars Query vars passed into url.
	 */
	public function whitelist_domain_association_query_param( $query_vars ) {
		$query_vars[] = self::DOMAIN_ASSOCIATION_FILE_NAME;
		return $query_vars;
	}

	/**
	 * Handles serving the domain association file.
	 *
	 * @param object $wp Request object.
	 */
	public function parse_domain_association_request( $wp ) {
		if (
			! isset( $wp->query_vars[ self::DOMAIN_ASSOCIATION_FILE_NAME ] ) ||
			'1' !== $wp->query_vars[ self::DOMAIN_ASSOCIATION_FILE_NAME ]
		) {
			return;
		}

		$path = PEACHPAY_ABSPATH . '/' . self::DOMAIN_ASSOCIATION_FILE_DIR . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME;
		header( 'Content-Type: text/plain;charset=utf-8' );
		echo esc_html( file_get_contents( $path ) ); // phpcs:ignore
		exit;
	}

	/**
	 * Sends Apple Pay registration if a domain name change is detected.
	 */
	public function check_domain_on_domain_change() {
		if ( strcmp( $this->domain_name, $this->get_option( 'apple_pay_verified_domain' ) ) !== 0 ) {
			$this->verify_domain_if_configured();
		}
	}

	/**
	 * Processes the Apple Pay domain verification.
	 */
	public function verify_domain_if_configured() {

		flush_rewrite_rules();

		// This method exists incase permalinks are set to Plain and a fallback is needed.
		$this->update_domain_association_file();

		$this->register_domain();

	}

	/**
	 * Handles creation of the fallback domain assocation file.
	 */
	public function update_domain_association_file() {
		if ( $this->verify_hosted_domain_association_file() ) {
			return;
		}

		$this->copy_domain_association_file();

	}

	/**
	 * Verifies that the domain association file matches
	 * the file from the plugin directory.
	 *
	 * @return bool $correct
	 */
	private function verify_hosted_domain_association_file() {
		try {
			$fullpath = untrailingslashit( PEACHPAY_ABSPATH ) . '/' . self::DOMAIN_ASSOCIATION_FILE_DIR . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME;
			if ( ! file_exists( $fullpath ) ) {
				return false;
			}
			$new_contents    = file_get_contents( PEACHPAY_ABSPATH . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME ); // phpcs:ignore
			$local_contents  = file_get_contents( $fullpath ); // phpcs:ignore

			$url             = get_site_url() . '/' . self::DOMAIN_ASSOCIATION_FILE_DIR . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME;
			$response        = wp_remote_get( $url );
			$remote_contents = wp_remote_retrieve_body( $response );

			return $local_contents === $new_contents || $remote_contents === $new_contents;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Copies and overwrites the domain assocation file.
	 */
	private function copy_domain_association_file() {
		$well_known_dir = untrailingslashit( PEACHPAY_ABSPATH ) . '/' . self::DOMAIN_ASSOCIATION_FILE_DIR;
		$fullpath       = $well_known_dir . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME;

		try {
			if ( ! file_exists( $well_known_dir ) ) {
				mkdir( $well_known_dir, 0755 );
			}

			copy( PEACHPAY_ABSPATH . '/' . self::DOMAIN_ASSOCIATION_FILE_NAME, $fullpath );
			return;
		} catch ( Exception $e ) {
			return;
		}
	}

	/**
	 * Processes the Apply Pay domain verification.
	 *
	 * @return bool Verification succeeded
	 */
	public function register_domain() {
		try {
			$this->domain_registration_request();

			$this->apple_pay_settings['apple_pay_verified_domain'] = $this->domain_name;
			$this->apple_pay_settings['apple_pay_domain_set']      = 'yes';
			$this->apple_pay_domain_set                            = true;

			update_option( 'peachpay_apple_pay_settings', $this->apple_pay_settings );

			return true;

		} catch ( Exception $e ) {
			$this->apple_pay_settings['apple_pay_verified_domain'] = $this->domain_name;
			$this->apple_pay_settings['apple_pay_domain_set']      = 'no';
			$this->apple_pay_domain_set                            = false;

			update_option( 'peachpay_apple_pay_settings', $this->apple_pay_settings );

			return false;
		}
	}

	/**
	 * Makes the request to register the domain.
	 *
	 * @throws Exception If there's an error registering domain.
	 */
	private function domain_registration_request() {
		// This only works with the live mode key so always target the production api-server.
		$endpoint = 'https://prod.peachpay.app/api/v1/stripe/apple-pay/merchant/register';

		$data = array(
			'session' => array(
				'merchant_url'    => get_site_url(),
				'merchant_domain' => $this->domain_name,
				'stripe'          => array(
					'connect_id' => get_option( 'peachpay_connected_stripe_account', array( 'id' => '' ) )['id'],
				),
			),

		);

		$response = wp_remote_post(
			$endpoint,
			array(
				'body'    => $data,
				'timeout' => 60,
			)
		);

		if ( ! peachpay_response_ok( $response ) ) {
			throw new Exception( 'Unable to register domain' );
		}
	}
}

new PeachPay_Stripe_Apple_Pay();
