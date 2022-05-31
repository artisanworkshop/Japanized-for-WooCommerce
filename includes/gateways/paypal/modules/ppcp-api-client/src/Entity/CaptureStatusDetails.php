<?php
/**
 * The CaptureStatusDetails object.
 *
 * @see https://developer.paypal.com/docs/api/payments/v2/#definition-capture_status_details
 *
 * @package WooCommerce\PayPalCommerce\ApiClient\Entity
 */

declare(strict_types=1);

namespace WooCommerce\PayPalCommerce\ApiClient\Entity;

/**
 * Class CaptureStatusDetails
 */
class CaptureStatusDetails {

	const BUYER_COMPLAINT                             = 'BUYER_COMPLAINT';
	const CHARGEBACK                                  = 'CHARGEBACK';
	const ECHECK                                      = 'ECHECK';
	const INTERNATIONAL_WITHDRAWAL                    = 'INTERNATIONAL_WITHDRAWAL';
	const OTHER                                       = 'OTHER';
	const PENDING_REVIEW                              = 'PENDING_REVIEW';
	const RECEIVING_PREFERENCE_MANDATES_MANUAL_ACTION = 'RECEIVING_PREFERENCE_MANDATES_MANUAL_ACTION';
	const REFUNDED                                    = 'REFUNDED';
	const TRANSACTION_APPROVED_AWAITING_FUNDING       = 'TRANSACTION_APPROVED_AWAITING_FUNDING';
	const UNILATERAL                                  = 'UNILATERAL';
	const VERIFICATION_REQUIRED                       = 'VERIFICATION_REQUIRED';

	/**
	 * The reason.
	 *
	 * @var string
	 */
	private $reason;

	/**
	 * CaptureStatusDetails constructor.
	 *
	 * @param string $reason The reason explaining capture status.
	 */
	public function __construct( string $reason ) {
		$this->reason = $reason;
	}

	/**
	 * Compares the current reason with a given one.
	 *
	 * @param string $reason The reason to compare with.
	 *
	 * @return bool
	 */
	public function is( string $reason ): bool {
		return $this->reason === $reason;
	}

	/**
	 * Returns the reason explaining capture status.
	 * One of CaptureStatusDetails constants.
	 *
	 * @return string
	 */
	public function reason(): string {
		return $this->reason;
	}

	/**
	 * Returns the human-readable reason text explaining capture status.
	 *
	 * @return string
	 */
	public function text(): string {
		switch ( $this->reason ) {
			case self::BUYER_COMPLAINT:
				return __( 'The payer initiated a dispute for this captured payment with PayPal.', 'woocommerce-for-japan' );
			case self::CHARGEBACK:
				return __( 'The captured funds were reversed in response to the payer disputing this captured payment with the issuer of the financial instrument used to pay for this captured payment.', 'woocommerce-for-japan' );
			case self::ECHECK:
				return __( 'The payer paid by an eCheck that has not yet cleared.', 'woocommerce-for-japan' );
			case self::INTERNATIONAL_WITHDRAWAL:
				return __( 'Visit your online account. In your Account Overview, accept and deny this payment.', 'woocommerce-for-japan' );
			case self::OTHER:
				return __( 'No additional specific reason can be provided. For more information about this captured payment, visit your account online or contact PayPal.', 'woocommerce-for-japan' );
			case self::PENDING_REVIEW:
				return __( 'The captured payment is pending manual review.', 'woocommerce-for-japan' );
			case self::RECEIVING_PREFERENCE_MANDATES_MANUAL_ACTION:
				return __( 'The payee has not yet set up appropriate receiving preferences for their account. For more information about how to accept or deny this payment, visit your account online. This reason is typically offered in scenarios such as when the currency of the captured payment is different from the primary holding currency of the payee.', 'woocommerce-for-japan' );
			case self::REFUNDED:
				return __( 'The captured funds were refunded.', 'woocommerce-for-japan' );
			case self::TRANSACTION_APPROVED_AWAITING_FUNDING:
				return __( 'The payer must send the funds for this captured payment. This code generally appears for manual EFTs.', 'woocommerce-for-japan' );
			case self::UNILATERAL:
				return __( 'The payee does not have a PayPal account.', 'woocommerce-for-japan' );
			case self::VERIFICATION_REQUIRED:
				return __( 'The payee\'s PayPal account is not verified.', 'woocommerce-for-japan' );
			default:
				return $this->reason;
		}
	}
}
