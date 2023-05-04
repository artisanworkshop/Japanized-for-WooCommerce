<?php
/**
 * PeachPay gateway table.
 *
 * @var array $gateway_list A list of gateway instances to render.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

foreach ( $gateway_list as $gateway ) : ?>
	<div class="gateway">
		<div class="info flex-col gap-12 w-100">
			<div class="list-item-heading gap-12">
				<?php
				// PHPCS:ignore
				echo $gateway->get_icon();
				?>
				<h4><?php echo esc_html( $gateway->title ); ?></h4>
				<div class="general-status <?php echo esc_attr( ( 'yes' === $gateway->enabled && ! $gateway->needs_setup() ) ? 'active' : 'inactive' ); ?> flex-row gap-8">
					<div class="active">Active</div>
					<div class="inactive">Inactive</div>
				</div>
			</div>
			<div class="location-status <?php echo esc_attr( ( 'yes' === $gateway->enabled && ! $gateway->needs_setup() ) ? '' : 'hide' ); ?>">
				<div class="location <?php echo 'checkout_page_only' !== $gateway->get_option( 'active_locations' ) ? 'active' : ''; ?>">
					Express Checkout
				</div>
				<div class="location <?php echo 'express_checkout_only' !== $gateway->get_option( 'active_locations' ) ? 'active' : ''; ?>">
					Checkout page
				</div>
			</div>
			<div class="description">
				<?php if ( $gateway->needs_setup() ) { ?>
					<span>
						<?php echo esc_html( $gateway->method_description ); ?>
					</span>
				<?php } else { ?>
					<div class="details">
						<?php
						$columns = array(
							array(
								'label'          => 'Currency availability',
								'key'            => $gateway->id . '_currency',
								'data'           => implode( ', ', $gateway->get_supported_currencies() ),
								'no-data'        => 0 === count( $gateway->get_supported_currencies() ),
								'no-restriction' => ! is_array( $gateway->get_supported_currencies() ),
							),
							array(
								'label'          => 'Country availability',
								'key'            => $gateway->id . '_country',
								'data'           => implode( ', ', $gateway->get_supported_countries() ),
								'no-data'        => 0 === count( $gateway->get_supported_countries() ),
								'no-restriction' => ! is_array( $gateway->get_supported_countries() ),
							),
							array(
								'label'          => 'Minimum charge',
								'key'            => $gateway->id . '_min',
								'data'           => $gateway->get_minimum_charge(),
								'no-data'        => false,
								'no-restriction' => ! is_numeric( $gateway->get_minimum_charge() ),
							),
							array(
								'label'          => 'Maximum charge',
								'key'            => $gateway->id . '_max',
								'data'           => $gateway->get_maximum_charge(),
								'no-data'        => false,
								'no-restriction' => INF === $gateway->get_maximum_charge(),
							),
						);
						?>
						<?php foreach ( $columns as $column ) { ?>
							<div class="flex-col gap-4">
								<h4><?php echo esc_html( $column['label'] ); ?></h4>
								<input type="checkbox" class="see-more-state hide" id="<?php echo esc_attr( $column['key'] ); ?>-list"/>
								<div class="see-more-wrap">
									<p class="see-more-target">
										<?php
										if ( $column['no-restriction'] ) {
											echo esc_html_e( 'Not restricted', 'peachpay-for-woocommerce' );
										} elseif ( $column['no-data'] ) {
											echo esc_html_e( 'Not available', 'peachpay-for-woocommerce' );
										} else {
											echo esc_html( $column['data'] );
										}
										?>
									</p>
									<div class="fade-bottom hide"></div>
								</div>
								<label for="<?php echo esc_attr( $column['key'] ); ?>-list" class="see-more-trigger hide"></label>
							</div>
						<?php } ?>
					</div>
				<?php } ?>
			</div>
		</div>
		<div class="buttons-container flex-col <?php echo esc_attr( $gateway->needs_setup() ? 'needs-setup' : ( ( 'yes' === $gateway->enabled ) ? 'enabled' : 'disabled' ) ); ?>">
			<a class="setup-button" data-heap="<?php echo 'setup_' . esc_html( $gateway->id ); ?>" href="<?php echo esc_url( $gateway->get_settings_url() ); ?>">
				<?php
				esc_html_e( 'Set up', 'peachpay-for-woocommerce' );
				?>
				<span class="arrow-top-right"></span>
			</a>
			<a class="manage-button" data-heap="<?php echo 'manage_' . esc_html( $gateway->id ); ?>" href="<?php echo esc_url( $gateway->get_settings_url() ); ?>">
				<?php
				esc_html_e( 'Manage', 'peachpay-for-woocommerce' );
				?>
				<span class="arrow-top-right"></span>
			</a>
			<div class="activate-button" data-heap="<?php echo 'activate_' . esc_html( $gateway->id ); ?>" data-id="<?php echo esc_html( $gateway->id ); ?>" tabindex="0">
				<?php
				esc_html_e( 'Activate', 'peachpay-for-woocommerce' );
				?>
				<span class="spinner"></span>
			</div>
			<div class="deactivate-button" data-heap="<?php echo 'deactivate_' . esc_html( $gateway->id ); ?>" data-id="<?php echo esc_html( $gateway->id ); ?>" tabindex="0">
				<?php
				esc_html_e( 'Deactivate', 'peachpay-for-woocommerce' );
				?>
				<span class="spinner"></span>
			</div>
		</div>
	</div>
<?php endforeach; ?>
