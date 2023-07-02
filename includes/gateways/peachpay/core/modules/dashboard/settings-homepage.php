<?php
/**
 * Renders the PeachPay settings homepage.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

/**
 * Renders the settings homepage.
 */
function peachpay_render_settings_homepage() {
	?>
	<div class='dashboard-container'>
		<div class='dashboard'>
			<?php
			peachpay_homepage_section( 'analytics' );
			peachpay_homepage_section( 'payment_methods' );
			peachpay_homepage_section( 'add_ons' );
			?>
		</div>
		<div class="peachpay-notices-container"></div>
	</div>
	<?php
}

/**
 * Renders a settings homepage section that consists of a heading, 'View more' button, and content body.
 *
 * @param string $homepage_section The homepage section to render.
 */
function peachpay_homepage_section( $homepage_section ) {
	?>
	<div class='dashboard-section dashboard-section-<?php echo esc_attr( str_replace( '_', '-', $homepage_section ) ); ?> pp-flex-col pp-gap-24'>
		<?php
		peachpay_homepage_heading( $homepage_section );
		peachpay_homepage_section_content( $homepage_section );
		?>
	</div>
	<?php
}

/**
 * Renders a settings homepage section heading with the section title and the 'View more' button.
 *
 * @param string $homepage_section The homepage section for which to render the heading.
 */
function peachpay_homepage_heading( $homepage_section ) {
	?>
	<div class='pp-flex-row pp-jc-sb'>
		<div class='pp-flex-row dashboard-section-title'>
			<?php
			peachpay_homepage_section_title( $homepage_section );
			?>
		</div>
		<?php
		if ( 'add_ons' !== $homepage_section ) {
			peachpay_homepage_section_view_more( $homepage_section );
		}
		?>
	</div>
	<?php
}

/**
 * Renders a settings homepage section's content.
 *
 * @param string $homepage_section The homepage section for which to render the content body.
 */
function peachpay_homepage_section_content( $homepage_section ) {
	?>
	<div class='dashboard-section-content'>
		<?php
		switch ( $homepage_section ) {
			case 'analytics':
				peachpay_homepage_analytics();
				break;
			case 'payment_methods':
				peachpay_homepage_payment_methods();
				break;
			case 'add_ons':
				peachpay_homepage_add_ons();
				break;
		}
		?>
	</div>
	<?php
}

/**
 * Populates the cards in the analytics section.
 */
function peachpay_homepage_analytics() {
	$order_count_last_month          = peachpay_homepage_order_count_last_month();
	$sales_last_month_strs           = array_map(
		function ( $currency ) {
			$sales_last_month_by_top_3_currencies = peachpay_homepage_sales_last_month_by_top_n_currencies( 3 );
			$total                                = $sales_last_month_by_top_3_currencies[ $currency ];
			$decimal_count                        = (int) get_option( 'woocommerce_price_num_decimals', 2 );
			return array(
				'title' => $currency,
				'value' => peachpay_homepage_currency_symbol_by_currency( $currency ) . peachpay_homepage_price_format( round( $total, peachpay_currency_decimals() ), $decimal_count ),
			);
		},
		array_keys( peachpay_homepage_sales_last_month_by_top_n_currencies( 3 ) )
	);
	$average_order_totals_last_month = array_map(
		function ( $currency ) {
			$average_cart_value_last_month_by_top_3_currencies = peachpay_homepage_average_cart_value_last_month_by_top_n_currencies( 3 );
			$avrage_cart_value                                 = $average_cart_value_last_month_by_top_3_currencies[ $currency ];
			return array(
				'title' => $currency,
				'value' => $avrage_cart_value,
			);
		},
		array_keys( peachpay_homepage_average_cart_value_last_month_by_top_n_currencies( 3 ) )
	);
	$analytics_cards                 = array(
		array(
			'title'     => 'Orders',
			'data'      => $order_count_last_month,
			'timeframe' => 'past 30 days',
		),
		array(
			'title'     => ( 1 < count( $sales_last_month_strs ) ) ? 'Sales in Top Currencies' : 'Sales',
			'data'      => $sales_last_month_strs,
			'timeframe' => 'past 30 days',
		),
		array(
			'title'     => ( 1 < count( $average_order_totals_last_month ) ) ? 'Average Order Values' : 'Average Order Value',
			'data'      => $average_order_totals_last_month,
			'timeframe' => 'past 30 days',
		),
	);
	foreach ( $analytics_cards as $analytics_card ) {
		?>
		<div class='dashboard-section-card'>
			<div class='dashboard-section-card-title'>
				<?php echo esc_html( $analytics_card['title'] ); ?>
			</div>
			<div class='dashboard-section-analytics-data opacity-zero'>
				<?php
				if ( is_array( $analytics_card['data'] ) ) {
					peachpay_homepage_analytics_render_array_data( $analytics_card['data'] );
				} else {
					?>
					<div class='dashboard-section-analytics-single-data'>
						<?php echo esc_html( $analytics_card['data'] ); ?>
					</div>
					<?php
				}
				?>
			</div>
			<div class='dashboard-section-analytics-description'>
				<?php echo esc_html( $analytics_card['timeframe'] ); ?>
			</div>
		</div>
		<?php
	}
}

/**
 * Renders an array of data for an analytics card.
 *
 * @param array $data_array The array of data to display.
 */
function peachpay_homepage_analytics_render_array_data( $data_array ) {
	?>
	<div class='dashboard-section-analytics-array-data-container'>
		<?php
		foreach ( $data_array as $data ) {
			?>
			<div class='pp-flex-col pp-ai-center'>
				<div class='dashboard-section-analytics-array-data-label'>
					<?php echo esc_html( $data['title'] ); ?>
				</div>
				<div class='dashboard-section-analytics-array-data'>
					<?php echo esc_html( $data['value'] ); ?>
				</div>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}

/**
 * Populates the cards in the analytics section.
 */
function peachpay_homepage_payment_methods() {
	$data = array(
		array(
			'title'       => 'Stripe',
			'heap_id'     => 'stripe',
			'hash'        => '#stripe',
			'main_logo'   => PeachPay::get_asset_url( '/img/marks/stripe.svg' ),
			'connection'  => (bool) PeachPay_Stripe_Integration::connected(),
			'description' => 'Stripe will give you the largest selection of global payment methods along with Apple Pay and Google Pay.',
			'sub_logos'   => array_map(
				function ( $gateway ) {
					return $gateway->get_icon_url( 'full' );
				},
				PeachPay_Stripe_Integration::get_payment_gateways()
			),
		),
		array(
			'title'       => 'PayPal',
			'heap_id'     => 'paypal',
			'hash'        => '#paypal',
			'main_logo'   => PeachPay::get_asset_url( '/img/marks/paypal.svg' ),
			'connection'  => (bool) PeachPay_PayPal_Integration::connected(),
			'description' => 'Accept payments from the largest number of countries with PayPal.',
			'sub_logos'   => array_map(
				function ( $gateway ) {
					return $gateway->get_icon_url( 'full' );
				},
				PeachPay_PayPal_Integration::get_payment_gateways()
			),
		),
		array(
			'title'       => 'Square',
			'heap_id'     => 'square',
			'hash'        => '#square',
			'main_logo'   => PeachPay::get_asset_url( '/img/marks/square.svg' ),
			'connection'  => peachpay_square_connected(),
			'description' => 'Connect your Square account to PeachPay to start accepting card payments, Apple Pay, and Google Pay instantly.',
			'sub_logos'   => array_map(
				function ( $gateway ) {
					return $gateway->get_icon_url( 'full' );
				},
				PeachPay_Square_Integration::get_payment_gateways()
			),
		),
		array(
			'title'       => 'GoDaddy Poynt',
			'heap_id'     => 'poynt',
			'hash'        => '#poynt',
			'main_logo'   => PeachPay::get_asset_url( '/img/marks/poynt-logo.svg' ),
			'connection'  => (bool) PeachPay_Poynt_Integration::connected(),
			'description' => 'Connect your GoDaddy Poynt account to start accepting card payments.',
			'sub_logos'   => array_map(
				function ( $gateway ) {
					return $gateway->get_icon_url( 'full' );
				},
				PeachPay_Poynt_Integration::get_payment_gateways()
			),
		),
		array(
			'title'       => 'Authorize.net',
			'heap_id'     => 'authnet',
			'hash'        => '#authnet',
			'main_logo'   => PeachPay::get_asset_url( '/img/marks/authnet.png' ),
			'connection'  => (bool) PeachPay_Authnet_Integration::connected(),
			'description' => 'Connect your Authorize.net account to PeachPay to start accepting card and US bank account payments.',
			'sub_logos'   => array_map(
				function ( $gateway ) {
					return $gateway->get_icon_url( 'full' );
				},
				PeachPay_Authnet_Integration::get_payment_gateways()
			),
		),
		array(
			'title'       => 'Amazon Pay',
			'heap_id'     => 'amazonpay',
			'hash'        => '#amazonpay',
			'main_logo'   => PeachPay::get_asset_url( '/img/marks/amazon-pay-card.svg' ),
			'connection'  => peachpay_amazonpay_enabled(),
			'description' => 'Offer shoppers the convenience of paying with Amazon Pay.',
			'sub_logos'   => null,
		),
		array(
			'title'       => 'Purchase Order',
			'heap_id'     => 'purchase_order',
			'hash'        => '#peachpay',
			'main_logo'   => PeachPay::get_asset_url( '/img/marks/purchase_order.svg' ),
			'connection'  => 'yes' === array_filter(
				PeachPay_Payments_Integration::get_payment_gateways(),
				function ( $gateway ) {
					return 'peachpay_purchase_order' === $gateway->id;
				}
			)[0]->enabled,
			'description' => 'Accept purchase orders through PeachPay.',
			'sub_logos'   => null,
		),
	);
	foreach ( $data as $value ) {
		?>
		<div class='dashboard-section-card'>
			<div class='image-container'>
				<img src='<?php echo esc_attr( $value['main_logo'] ); ?>'/>
			</div>
			<div class='title-button-row'>
				<div class='dashboard-section-card-title'>
					<?php echo esc_html( $value['title'] ); ?>
				</div>
				<a class='dashboard-card-button <?php echo esc_attr( $value['connection'] ? 'button-success-outlined-medium' : 'button-primary-filled-medium default-filled' ); ?>' href='<?php esc_url( PeachPay_Admin::admin_settings_url( 'peachpay', 'payment', '', $value['hash'] ) ); ?>'
					<?php
					if ( $value['connection'] ) {
						echo esc_attr( 'enabled' );
					}
					?>
					data-heap='<?php echo esc_html( $value['connection'] ? 'home_view_' : 'home_enable_' ) . esc_html( $value['heap_id'] ); ?>'
				>
					<div></div>
					<div><?php echo esc_html( $value['connection'] ? 'Enabled' : 'Enable' ); ?></div>
					<div></div>
				</a>
			</div>
			<div class='dashboard-section-card-description'>
				<?php echo esc_html( $value['description'] ); ?>
			</div>
			<?php
			if ( $value['sub_logos'] ) {
				?>
				<div class='sub-logos'>
					<?php
					foreach ( $value['sub_logos'] as $src ) {
						?>
						<img src='<?php echo esc_attr( $src ); ?>'/>
						<?php
					}
					if ( 4 < count( $value['sub_logos'] ) ) {
						?>
						<div class='sub-logos-toggle'>See more</div>
						<?php
					}
					?>
				</div>
			<?php } ?>
		</div>
		<?php
	}
}

/**
 * Populates the cards in the add-ons section.
 */
function peachpay_homepage_add_ons() {
	// Add 2 line by Shohei Tanaka at 2023/07/02
	require_once PEACHPAY_ABSPATH . 'core/abstract/class-peachpay-admin-tab.php';
	require_once PEACHPAY_ABSPATH . 'core/modules/bot-protection/class-peachpay-bot-protection-settings.php';

	$data = array(
		array(
			'title'       => 'Express Checkout',
			'heap_id'     => 'express_checkout',
			'main_image'  => peachpay_url( '/core/admin/assets/img/add-ons/checkout_window.svg' ),
			'connection'  => peachpay_express_checkout_enabled(),
			'description' => 'Allow shoppers to buy from anywhere, not just the checkout page!',
			'url'         => PeachPay_Admin::admin_settings_url( 'peachpay', 'express_checkout', 'branding', '', false ),
		),
		array(
			'title'       => 'Currency Switcher',
			'heap_id'     => 'currency_switcher',
			'main_image'  => peachpay_url( '/core/admin/assets/img/add-ons/currency_switcher.svg' ),
			'connection'  => peachpay_get_settings_option( 'peachpay_currency_options', 'enabled' ),
			'description' => 'Sell in over 135 currencies with a single click, or customize it to fit your needs.',
			'url'         => PeachPay_Admin::admin_settings_url( 'peachpay', 'currency', '', '', false ),
		),
		array(
			'title'       => 'Field Editor',
			'heap_id'     => 'field_editor',
			'main_image'  => peachpay_url( '/core/admin/assets/img/add-ons/field_editor.svg' ),
			'connection'  => 1,
			'description' => 'Add, edit, or remove checkout fields. Choose from all types of fields, including text, multiple choice, drop downs, and more.',
			'url'         => PeachPay_Admin::admin_settings_url( 'peachpay', 'field', 'billing', '', false ),
		),
		array(
			'title'       => 'Recommended Products',
			'heap_id'     => 'recommended_products',
			'main_image'  => peachpay_url( '/core/admin/assets/img/add-ons/recommended_products.svg' ),
			'connection'  => peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_enable' ),
			'description' => 'Show upsells to boost your store’s revenue.',
			'url'         => PeachPay_Admin::admin_settings_url( 'peachpay', 'related_products', '', '', false ),
		),
		array(
			'title'       => __( 'Bot Protection', 'peachpay-for-woocommerce' ),
			'main_image'  => peachpay_url( '/core/admin/assets/img/add-ons/bot.svg' ),
			'connection'  => 'yes' === PeachPay_Bot_Protection_Settings::get_setting( 'enabled' ),
			'description' => __( 'Block bots from spamming purchases by enabling reCAPTCHA v3 technology.', 'peachpay-for-woocommerce' ),
			'url'         => PeachPay_Admin::admin_settings_url( 'peachpay', 'bot_protection', 'settings', '', false ),
		),
	);
	foreach ( $data as $value ) {
		?>
		<div class='dashboard-section-card'>
			<div class='image-container'>
				<img src='<?php echo esc_attr( $value['main_image'] ); ?>'/>
			</div>
			<div class='pp-flex-row pp-jc-sb'>
				<div class='dashboard-section-card-title'>
					<?php echo esc_html( $value['title'] ); ?>
				</div>
				<a class='dashboard-card-button <?php echo esc_attr( $value['connection'] ? 'button-success-outlined-medium' : 'button-primary-filled-medium default-filled' ); ?>' href='<?php echo esc_url( $value['url'] ); ?>'
					<?php
					if ( $value['connection'] ) {
						echo esc_attr( 'enabled' );
					}
					?>
					data-heap='<?php echo esc_html( $value['connection'] ? 'home_view_' : 'home_enable_' ) . esc_html( $value['heap_id'] ); ?>'
				>
					<div></div>
					<div><?php echo esc_html( $value['connection'] ? 'Enabled' : 'Enable' ); ?></div>
					<div></div>
				</a>
			</div>
			<div class='dashboard-section-card-description'>
				<?php echo esc_html( $value['description'] ); ?>
			</div>
		</div>
		<?php
	}
}

/**
 * Generates escaped and translated text for the homepage section title.
 * This is a workaround for esc_html_e only accepting string literals.
 *
 * @param string $homepage_section The homepage section.
 */
function peachpay_homepage_section_title( $homepage_section ) {
	switch ( $homepage_section ) {
		case 'analytics':
			echo esc_html_e( 'Analytics', 'peachpay-for-woocommerce' );
			break;
		case 'payment_methods':
			echo esc_html_e( 'Payment methods', 'peachpay-for-woocommerce' );
			break;
		case 'add_ons':
			echo esc_html_e( 'Add-ons', 'peachpay-for-woocommerce' );
			break;
	}
}

/**
 * Renders the 'View more' button for a homepage section.
 *
 * @param string $homepage_section The homepage section.
 */
function peachpay_homepage_section_view_more( $homepage_section ) {
	$page    = '';
	$tab     = '';
	$section = '';
	switch ( $homepage_section ) {
		case 'analytics':
			$page    = 'peachpay';
			$section = 'analytics';
			$tab     = 'payment_methods';
			break;
		case 'payment_methods':
			$page = 'peachpay';
			$tab  = 'payment';
			break;
	}
	?>
	<a class='pp-homepage-view-more pp-flex-row pp-gap-4 pp-ai-center' data-heap='<?php echo 'home_view_' . esc_html( $homepage_section ); ?>' href='<?php esc_url( PeachPay_Admin::admin_settings_url( $page, $tab, $section ) ); ?>'>
		<div class='pp-flex-row'>View more</div>
		<div class='pp-flex-row pp-chevron-right'></div>
	</a>
	<?php
}

/**
 * Returns last month's processing/completed orders.
 */
function peachpay_homepage_orders_last_month() {
	$one_month_ago     = ( new DateTime() )->sub( DateInterval::createFromDateString( '1 month' ) );
	$orders_last_month = wc_get_orders(
		array(
			'status'       => array_keys( wc_get_order_statuses() ),
			'date_created' => '>=' . $one_month_ago->getTimestamp(),
			'limit'        => -1,
			'status'       => array( 'wc-completed', 'wc-processing' ),
		)
	);
	return $orders_last_month;
}

/**
 * Returns last month's processing/completed orders, filtered by the given currency.
 *
 * @param string $currency The currency by which to filter the orders last month.
 */
function peachpay_homepage_orders_last_month_by_currency( $currency ) {
	$one_month_ago                = ( new DateTime() )->sub( DateInterval::createFromDateString( '1 month' ) );
	$orders_last_month_by_curency = wc_get_orders(
		array(
			'status'       => array_keys( wc_get_order_statuses() ),
			'date_created' => '>=' . $one_month_ago->getTimestamp(),
			'limit'        => -1,
			'status'       => array( 'wc-completed', 'wc-processing' ),
			'currency'     => $currency,
		)
	);
	return $orders_last_month_by_curency;
}

/**
 * Returns the number of orders in the last month.
 */
function peachpay_homepage_order_count_last_month() {
	return count( peachpay_homepage_orders_last_month() );
}

/**
 * Returns the number of orders in the last month, filtered by the given currency.
 *
 * @param string $currency The currency by which to filter the orders last month.
 */
function peachpay_homepage_order_count_last_month_by_currency( $currency ) {
	return count( peachpay_homepage_orders_last_month_by_currency( $currency ) );
}

/**
 * Returns last month's sales in the top n currencies.
 *
 * @param int $n Number of items to return.
 */
function peachpay_homepage_sales_last_month_by_top_n_currencies( $n ) {
	if ( ! peachpay_homepage_order_count_last_month() ) {
		return array( peachpay_currency_code() => 0 );
	}
	$orders_last_month = peachpay_homepage_orders_last_month();
	$sales             = array();
	foreach ( $orders_last_month as $order ) {
		if ( array_key_exists( $order->get_currency(), $sales ) ) {
			$sales[ $order->get_currency() ] += $order->get_total();
		} else {
			$sales[ $order->get_currency() ] = $order->get_total();
		}
	}
	$sales_in_top_n_currencies = array();
	foreach ( $sales as $currency => $total ) {
		// Add current data to array if $sales_in_top_n_currencies hasn't reached maximum of n items
		if ( $n > count( $sales_in_top_n_currencies ) ) {
			$sales_in_top_n_currencies[ $currency ] = $total;
		} else {
			$currency_with_min_sale = $currency;
			$min_sale               = $total;
			// Look for currency with the minimum sale
			for ( $i = 0; $i < 3; $i++ ) {
				if ( array_values( $sales_in_top_n_currencies )[ $i ] < $min_sale ) {
					$currency_with_min_sale = array_keys( $sales_in_top_n_currencies )[ $i ];
					$min_sale               = array_values( $sales_in_top_n_currencies )[ $i ];
				} elseif ( ( array_values( $sales_in_top_n_currencies )[ $i ] === $min_sale ) && ( peachpay_currency_code() === $currency_with_min_sale ) ) {
					$currency_with_min_sale = array_keys( $sales_in_top_n_currencies )[ $i ];
					$min_sale               = $sales_in_top_n_currencies[ $i ];
				}
			}
			// Swap new item into $sales_in_top_n_currencies
			if ( $currency_with_min_sale !== $currency ) {
				unset( $sales_in_top_n_currencies[ $currency_with_min_sale ] );
				$sales_in_top_n_currencies[ $currency ] = $total;
			}
		}
	}
	asort( $sales_in_top_n_currencies );
	return array_reverse( $sales_in_top_n_currencies, true );
}

/**
 * Returns the currency symbol for the given currency.
 *
 * @param string $currency The currency code.
 */
function peachpay_homepage_currency_symbol_by_currency( $currency ) {
	$wc_currency_symbols = get_woocommerce_currency_symbols();
	return $wc_currency_symbols[ $currency ];
}

/**
 * Returns the price string zero-padded and thusands-separated.
 *
 * @param string $value          The price as a string.
 * @param int    $decimal_count  Number of decimal digits.
 */
function peachpay_homepage_price_format( $value, $decimal_count ) {
	$decimal_separator   = wc_get_price_decimal_separator();
	$thousands_separator = wc_get_price_thousand_separator();
	if ( 0 === $decimal_count ) {
		return number_format( (int) rtrim( $value, $decimal_separator ), 0, $decimal_separator, $thousands_separator );
	}
	$split_at_decimal = explode( '.', $value, 2 );
	if ( 1 === count( $split_at_decimal ) ) {
		return number_format( (int) $value, 0, $decimal_separator, $thousands_separator ) . $decimal_separator . str_repeat( '0', $decimal_count );
	}
	$remaining_zeros = $decimal_count - strlen( $split_at_decimal[1] );
	return number_format( (int) $split_at_decimal[0], 0, $decimal_separator, $thousands_separator ) . $decimal_separator . $split_at_decimal[1] . str_repeat( '0', $remaining_zeros );
}

/**
 * Returns the average cart value last month.
 *
 * @param int $n The number of items to return.
 */
function peachpay_homepage_average_cart_value_last_month_by_top_n_currencies( $n ) {
	$average_cart_value_last_month_by_top_n_currencies = array();

	$decimal_count                        = (int) get_option( 'woocommerce_price_num_decimals', 2 );
	$sales_last_month_by_top_n_currencies = peachpay_homepage_sales_last_month_by_top_n_currencies( $n );

	foreach ( $sales_last_month_by_top_n_currencies as $currency => $total ) {
		$order_count_last_month                   = peachpay_homepage_order_count_last_month_by_currency( $currency );
		$sales_last_month                         = (float) $total;
		$average_order_total_last_month           = $order_count_last_month ? $sales_last_month / $order_count_last_month : 0;
		$average_order_total_last_month_trimmed   = round( $average_order_total_last_month, $decimal_count );
		$average_order_total_last_month_formatted = peachpay_homepage_price_format( "$average_order_total_last_month_trimmed", $decimal_count );
		$currency_symbol                          = peachpay_homepage_currency_symbol_by_currency( $currency );

		$average_cart_value_last_month_by_top_n_currencies[ $currency ] = $currency_symbol . $average_order_total_last_month_formatted;
	}

	return $average_cart_value_last_month_by_top_n_currencies;
}
