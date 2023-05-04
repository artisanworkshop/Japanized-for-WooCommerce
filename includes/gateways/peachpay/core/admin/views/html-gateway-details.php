<?php
/**
 * PeachPay Gateway details view.
 *
 * @var PeachPay_Payment_Gateway $gateway The gateway instance to display details of.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

?>
<div id="peachpay-gateway-details" class="settings-container">
	<h1>
	<?php
	echo esc_html( $gateway->method_title );
	//phpcs:ignore
	echo $gateway->get_icon(true);
	?>
	</h1>
	<p><?php echo esc_html( $gateway->method_description ); ?></p>
	<hr>
	<div class="row">
		<div class="col-3">
			<dl>
				<dt><?php esc_html_e( 'Currency availability', 'peachpay-for-woocommerce' ); ?></dt>
				<dd>
					<?php
					$currencies = $gateway->get_supported_currencies();

					if ( is_array( $currencies ) ) {
						if ( count( $currencies ) > 0 ) {
							?>
							<input type="checkbox" class="sm-state-currency see-more-state hide" id="currency-list"/>
							<div class="see-more-wrap">
								<div class="sm-target-currency see-more-target">
								<?php echo esc_html( implode( ', ', $currencies ) ); ?>
								</div>
								<div class="fade-currency fade-bottom hide"></div>
							</div>
							<label for="currency-list" class="sm-trigger-currency see-more-trigger hide"></label>
							<?php
						} else {
							esc_html_e( 'No supported currencies', 'peachpay-for-woocommerce' );
						}
					} else {
						esc_html_e( 'Not restricted', 'peachpay-for-woocommerce' );
					}
					?>
				</dd>
				<dt><?php esc_html_e( 'Country availability', 'peachpay-for-woocommerce' ); ?></dt>
				<dd>
					<?php
					$countries = $gateway->get_supported_countries();

					if ( is_array( $countries ) ) {
						if ( count( $countries ) > 0 ) {
							?>
							<input type="checkbox" class="sm-state-country see-more-state hide" id="country-list"/>
							<div class="see-more-wrap">
								<div class="sm-target-country see-more-target">
								<?php echo esc_html( implode( ', ', $countries ) ); ?>
								</div>
								<div class="fade-country fade-bottom hide"></div>
							</div>
							<label for="country-list" class="sm-trigger-country see-more-trigger hide"></label>
							<?php
						} else {
							esc_html_e( 'No supported countries', 'peachpay-for-woocommerce' );
						}
					} else {
						esc_html_e( 'Not restricted', 'peachpay-for-woocommerce' );
					}
					?>
				</dd>
			</dl>
		</div>
		<div class="col-3">
			<dl>
				<dt><?php esc_html_e( 'Minimum charge', 'peachpay-for-woocommerce' ); ?></dt>
				<dd>
					<?php
					$minimum = $gateway->get_minimum_charge();
					if ( is_numeric( $minimum ) ) {
                        // PHPCS:ignore
                        echo wc_price( $minimum, array( 'currency' => $gateway->min_max_currency ) );
					} else {
						esc_html_e( 'Not restricted', 'peachpay-for-woocommerce' );
					}
					?>
				</dd>
				<dt><?php esc_html_e( 'Maximum charge', 'peachpay-for-woocommerce' ); ?></dt>
				<dd>
					<?php
					$maximum = $gateway->get_maximum_charge();
					if ( INF !== $maximum ) {
                        // PHPCS:ignore
                        echo wc_price( $maximum, array( 'currency' => $gateway->min_max_currency ) );
					} else {
						esc_html_e( 'Not restricted', 'peachpay-for-woocommerce' );
					}
					?>
				</dd>
			</dl>
		</div>
		<div class="col-3">
			<dl>
				<dt><?php esc_html_e( 'Recurring payments', 'peachpay-for-woocommerce' ); ?></dt>
				<dd>
					<?php
					if ( $gateway->supports( 'subscriptions' ) ) {
                        // PHPCS:ignore
						esc_html_e( 'Yes', 'peachpay-for-woocommerce' );
					} else {
						esc_html_e( 'No', 'peachpay-for-woocommerce' );
					}
					?>
				</dd>
				<dt><?php esc_html_e( 'Refunds', 'peachpay-for-woocommerce' ); ?></dt>
				<dd>
					<?php
					if ( $gateway->supports( 'refunds' ) ) {
                        // PHPCS:ignore
						esc_html_e( 'Yes', 'peachpay-for-woocommerce' );
					} else {
						esc_html_e( 'No', 'peachpay-for-woocommerce' );
					}
					?>
				</dd>
			</dl>
		</div>
		<div class="col-3">
			<dl>
				<dt><?php esc_html_e( 'Payment family', 'peachpay-for-woocommerce' ); ?></dt>
				<dd><?php echo esc_html( $gateway->payment_method_family ); ?></dd>
			</dl>
		</div>
	</div>
</div>
