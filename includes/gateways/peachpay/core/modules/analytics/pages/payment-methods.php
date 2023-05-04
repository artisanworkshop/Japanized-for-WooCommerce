<?php
/**
 * Handles payment methods section of PeachPay's analytics admin panel
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

require_once PEACHPAY_ABSPATH . 'core/modules/analytics/assets/php/class-color-mapping.php';

/**
 * Renders the analytics page.
 */
function peachpay_analytics_payment_methods_html() {
	// Don't show the PeachPay settings to users who are not allowed to view
	// administration screens: https://wordpress.org/support/article/roles-and-capabilities/#read.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Currency converter setup.
	$base              = get_option( 'woocommerce_currency' );
	$converted_to_base = "Converted to $base";
	$converter         = wp_remote_get( peachpay_api_url() . "api/v1/getAllCurrency?from={$base}" );
	if ( is_wp_error( $converter ) || wp_remote_retrieve_response_code( $converter ) === 400 ) {
		$converter = array();
	} else {
		$converter = json_decode( $converter['body'], true )['rates'];
	}

	// X axis setup.
	$month_dict    = array();
	$volume_labels = array();
	$week_dict     = array();
	$order_labels  = array();

	// Monthly interval.
	$now          = ( new DateTime() )->add( DateInterval::createFromDateString( '1 month' ) );
	$one_year_ago = ( new DateTime() )->sub( DateInterval::createFromDateString( '11 months' ) );
	$period       = new DatePeriod( $one_year_ago, DateInterval::createFromDateString( '1 month' ), $now );
	$counter      = 0;
	foreach ( $period as $date ) {
		$month_dict[ $date->format( 'n' ) ] = $counter;
		$volume_labels[]                    = $date->format( 'F' );
		$counter++;
	}

	// Weekly interval.
	$now          = ( new DateTime() )->add( DateInterval::createFromDateString( '1 weeks' ) );
	$one_year_ago = ( new DateTime() )->sub( DateInterval::createFromDateString( '51 weeks' ) );
	$period       = new DatePeriod( $one_year_ago, DateInterval::createFromDateString( '1 week' ), $now );
	$counter      = 0;
	foreach ( $period as $date ) {
		$week_dict[ $date->format( 'W' ) ] = $counter;
		$order_labels[]                    = $date->format( 'M j, Y' );
		$counter++;
	}

	// Order query.
	$orders = wc_get_orders(
		array(
			'status'       => array_keys( wc_get_order_statuses() ),
			'date_created' => '>=' . $one_year_ago->getTimestamp(),
			'limit'        => -1,
			'orderby'      => 'payment_method',
			'order'        => 'ASC',
		)
	);

	// Get preliminary info.
	$methods       = array();
	$currencies    = array();
	$color_mapping = new Color_Mapping();

	$offset = 0;
	foreach ( $orders as $order ) {
		// Eject orders without a payment method title.
		if ( ! method_exists( $order, 'get_payment_method_title' ) ) {
			unset( $orders[ $offset ] );
			++$offset;
			continue;
		}

		$payment_method_title = $order->get_payment_method_title();
		if ( '' === $payment_method_title ) {
			$payment_method_title = 'Unset';
		}
		if ( ! in_array( $payment_method_title, $methods, true ) ) {
			$methods[ $payment_method_title ] = array();
		}
		if ( ! in_array( $order->get_currency(), $currencies, true ) ) {
			$currencies[ $order->get_currency() ] = array();
		}
		$color_mapping->add_mapping( $payment_method_title );
		++$offset;
	}

	ksort( $currencies );

	if ( count( $currencies ) > 1 ) {
		$currencies = array_merge( array( $converted_to_base => array() ), $currencies );
	}

	// Dataset initialization.
	$pie_order_data   = array();
	$pie_order_colors = array();
	$line_order_data  = array();

	$pie_volume_data   = $currencies;
	$pie_volume_colors = $currencies;
	$bar_volume_data   = $currencies;

	$pie_volume_data[ $converted_to_base ]   = $methods;
	$pie_volume_colors[ $converted_to_base ] = $methods;
	$bar_volume_data[ $converted_to_base ]   = $methods;

	$skipped_currency_data = array();

	// Order loop.
	foreach ( $orders as $order ) {
		$payment_method_title = $order->get_payment_method_title();
		if ( '' === $payment_method_title ) {
			$payment_method_title = 'Unset';
		}

		// Payment type order pie chart dataset.
		if ( ! isset( $pie_order_data[ $payment_method_title ] ) ) {
			$pie_order_data[ $payment_method_title ]   = 0;
			$pie_order_colors[ $payment_method_title ] = $color_mapping->get_mapped_color( $payment_method_title );
		}
		$pie_order_data[ $payment_method_title ] += 1;

		// Payment type order line chart dataset.
		if ( ! isset( $line_order_data[ $payment_method_title ] ) ) {
			$line_order_data[ $payment_method_title ] = array(
				'label'           => $payment_method_title,
				'data'            => array_fill( 0, 52, 0 ),
				'borderColor'     => $color_mapping->get_mapped_color( $payment_method_title ),
				'backgroundColor' => $color_mapping->get_mapped_color( $payment_method_title ),
			);
		}
		$line_order_data[ $payment_method_title ]['data'][ $week_dict[ $order->get_date_created()->format( 'W' ) ] ] += 1;

		// Volume currency setup.
		if ( array() === $pie_volume_data[ $order->get_currency() ] ) {
			$pie_volume_data[ $order->get_currency() ]   = $methods;
			$pie_volume_colors[ $order->get_currency() ] = $methods;
			$bar_volume_data[ $order->get_currency() ]   = $methods;
		}

		// Payment type volume pie chart dataset.
		if ( array() === $pie_volume_data[ $order->get_currency() ][ $payment_method_title ] ) {
			$pie_volume_data[ $order->get_currency() ][ $payment_method_title ]   = 0;
			$pie_volume_colors[ $order->get_currency() ][ $payment_method_title ] = $color_mapping->get_mapped_color( $payment_method_title );
		}
		$pie_volume_data[ $order->get_currency() ][ $payment_method_title ] += $order->get_total();

		// Payment type volume bar chart dataset.
		if ( array() === $bar_volume_data[ $order->get_currency() ][ $payment_method_title ] ) {
			$bar_volume_data[ $order->get_currency() ][ $payment_method_title ] = array(
				'label'           => $payment_method_title,
				'data'            => array_fill( 0, 12, 0 ),
				'backgroundColor' => $color_mapping->get_mapped_color( $payment_method_title ),
			);
		}
		$bar_volume_data[ $order->get_currency() ][ $payment_method_title ]['data'][ $month_dict[ $order->get_date_created()->format( 'n' ) ] ] += $order->get_total();

		// Volume conversion setup.
		if ( array() === $pie_volume_data[ $converted_to_base ][ $payment_method_title ] ) {
			$pie_volume_data[ $converted_to_base ][ $payment_method_title ]   = 0;
			$pie_volume_colors[ $converted_to_base ][ $payment_method_title ] = $color_mapping->get_mapped_color( $payment_method_title );
			$bar_volume_data[ $converted_to_base ][ $payment_method_title ]   = array(
				'label'           => $payment_method_title,
				'data'            => array_fill( 0, 12, 0 ),
				'backgroundColor' => $color_mapping->get_mapped_color( $payment_method_title ),
			);
		}

		if ( isset( $converter[ $order->get_currency() ] ) ) {
			$converted_value = $order->get_total() / $converter[ $order->get_currency() ];
			$pie_volume_data[ $converted_to_base ][ $payment_method_title ] += $converted_value;
			$bar_volume_data[ $converted_to_base ][ $payment_method_title ]['data'][ $month_dict[ $order->get_date_created()->format( 'n' ) ] ] += $converted_value;
		} else {
			if ( ! isset( $skipped_currency_data[ $order->get_currency() ] ) ) {
				$skipped_currency_data[ $order->get_currency() ] = 0;
			}
			$skipped_currency_data[ $order->get_currency() ] += $order->get_total();
		}

		ksort( $skipped_currency_data );
	}
	?>

	<div class='pp-analytics-payment-methods-container'>
		<div class='pp-analytics-payment-methods-row'>
			<div class='pp-analytics-payment-methods-thin-graph'>
				<h1 class='pp-analytics-graph-title'><?php esc_html_e( 'Number of orders', 'peachpay-for-woocommerce' ); ?></h1>
				<canvas id='pp_analytics_payment_type_order_pie_chart'></canvas>
			</div>
			<div class='pp-analytics-payment-methods-wide-graph'>
				<div class='pp-analytics-graph-header'>
					<h1 class='pp-analytics-graph-title'><?php esc_html_e( 'Number of orders | Last 52 weeks', 'peachpay-for-woocommerce' ); ?></h1>
				</div>
				<canvas id='pp_analytics_payment_type_order_line_chart'></canvas>
			</div>
		</div>
		<div class='pp-analytics-payment-methods-row'>
			<div class='pp-analytics-payment-methods-thin-graph'>
				<h1 class='pp-analytics-graph-title'><?php esc_html_e( 'Transaction volume', 'peachpay-for-woocommerce' ); ?></h1>
				<canvas id='pp_analytics_payment_type_volume_pie_chart'></canvas>
			</div>
			<div class='pp-analytics-payment-methods-wide-graph'>
				<div class='pp-analytics-graph-header'>
					<h1 class='pp-analytics-graph-title'><?php esc_html_e( 'Transaction volume | Last 12 months', 'peachpay-for-woocommerce' ); ?></h1>
					<div class='pp-analytics-currency-container'>
						<select id='pp_analytics_currency_selector' onchange='updateCurrencySelector()'>
							<?php
							foreach ( $currencies as $currency => $array ) {
								echo "<option value='" . esc_html( $currency ) . "'>" . esc_html( $currency ) . '</option>';
							}
							?>
						</select>
						<span id='pp_analytics_skipped_currencies_tooltip' class='pp-popup-mousemove-trigger'>
							<?php esc_html( include PEACHPAY_ABSPATH . 'core/modules/analytics/assets/svg/info.svg' ); ?>
							<span class='pp-popup pp-popup-above pp-tooltip-popup'>
								<h4 class='pp-analytics-tooltip-title'><?php esc_html_e( 'Unknown skipped currency volumes', 'peachpay-for-woocommerce' ); ?></h4>
								<pre id='pp_analytics_skipped_currencies' class='pp-analytics-tooltip-content'>
									<?php echo esc_html( trim( substr( wp_json_encode( $skipped_currency_data, JSON_PRETTY_PRINT ), 1, -1 ) ) ); ?>
								</pre>
							</span>
						</span>
					</div>
					<div class='pp-analytics-graph-subtitle'>
						<div class='pp-analytics-graph-mode-text'>
							<?php esc_html_e( 'View', 'peachpay-for-woocommerce' ); ?>: <span id='pp_analytics_bar_mode_text'></span>
						</div>
						<button class='button pp-analytics-button' onClick='updateBarStackedOption()'><?php esc_html_e( 'Toggle view', 'peachpay-for-woocommerce' ); ?></button>
					</div>
				</div>
				<canvas id='pp_analytics_payment_type_volume_bar_chart'></canvas>
			</div>
		</div>
	</div>

	<div id='pp-analytics-tutorial-modal' class='pp-analytics-modal'>
		<div class='pp-analytics-modal-content'>
			<div class='pp-analytics-modal-title'>
				<h1 class='pp-analytics-modal-h1'><?php esc_html_e( 'Welcome to payment method analytics!', 'peachpay-for-woocommerce' ); ?></h1>
				<span id='pp-analytics-modal-close' class='pp-analytics-modal-close'>&times;</span>
			</div>
			<div>
				<p><?php esc_html_e( 'The payment method titles in the legends can be toggled to specify which to show in the graph.', 'peachpay-for-woocommerce' ); ?></p>
				<p><?php esc_html_e( 'The toggle view buttons change the graphs between side by side and stacked modes.', 'peachpay-for-woocommerce' ); ?></p>
				<p>
				<?php
					esc_html_e(
						'The converted option for the volume graphs converts all currencies that PeachPay supports
						to the store\'s base currency and leaves out other currencies. You can view the
						currencies that have been left out by hovering on the info icon.',
						'peachpay-for-woocommerce'
					);
				?>
				</p>
			</div>
		</div>
	</div>

	<script>
		let base_currency = <?php echo wp_json_encode( $converted_to_base ); ?>;
		let order_labels = <?php echo wp_json_encode( $order_labels ); ?>;
		let line_order_data = <?php echo wp_json_encode( $line_order_data ); ?>;
		let pie_order_data = <?php echo wp_json_encode( $pie_order_data ); ?>;
		let pie_order_colors = <?php echo wp_json_encode( $pie_order_colors ); ?>;

		let volume_labels = <?php echo wp_json_encode( $volume_labels ); ?>;
		let pie_volume_data = <?php echo wp_json_encode( $pie_volume_data ); ?>;
		let pie_volume_colors = <?php echo wp_json_encode( $pie_volume_colors ); ?>;
		let bar_volume_data = <?php echo wp_json_encode( $bar_volume_data ); ?>;

		const order_pie_chart_context = document.getElementById('pp_analytics_payment_type_order_pie_chart');
		const order_pie_chart = new Chart(order_pie_chart_context, {
			type: 'pie',
			data: {
				labels: Object.keys( pie_order_data ),
				datasets: [{
					data: Object.values( pie_order_data ),
					backgroundColor: Object.values( pie_order_colors ),
				}],
			},
			options: {
				plugins: {
					legend: {
						position: 'bottom',
						align: 'start',
						labels: {
							boxWidth: 15,
						}
					},
				},
			}
		});

		const order_line_chart_context = document.getElementById('pp_analytics_payment_type_order_line_chart');
		const order_line_chart = new Chart(order_line_chart_context, {
			type: 'line',
			data: {
				labels: order_labels,
				datasets: Object.values(line_order_data),
			},
			options: {
				aspectRatio: 2.5,
				plugins: {
					legend: {
						position: 'bottom',
						align: 'end',
						labels: {
							boxWidth: 15,
						}
					}
				},
				scales: {
					y: {
						stacked: false,
					},
				}
			}
		});

		const volume_pie_chart_context = document.getElementById('pp_analytics_payment_type_volume_pie_chart');
		const volume_pie_chart = new Chart(volume_pie_chart_context, {
			type: 'pie',
			data: {
				labels: Object.keys(pie_volume_data[base_currency]),
				datasets: [{
					data: Object.values(pie_volume_data[base_currency]),
					backgroundColor: Object.values(pie_volume_colors[base_currency]),
				}],
			},
			options: {
				plugins: {
					legend: {
						position: 'bottom',
						align: 'start',
						labels: {
							boxWidth: 15,
						}
					},
				}
			}
		});

		const volume_bar_chart_context = document.getElementById('pp_analytics_payment_type_volume_bar_chart');
		const volume_bar_chart = new Chart(volume_bar_chart_context, {
			type: 'bar',
			data: {
				labels: volume_labels,
				datasets: Object.values(bar_volume_data[base_currency]),
			},
			options: {
				scales: {
					x: {
						stacked: true,
					},
					y: {
						stacked: true,
					},
				},
				aspectRatio: 2.5,
				plugins: {
					legend: {
						position: 'bottom',
						align: 'end',
						labels: {
							boxWidth: 15,
						}
					}
				}
			}
		});

		document.getElementById('pp_analytics_bar_mode_text').innerHTML = volume_bar_chart.options.scales.y.stacked ? 'Stacked' : 'Side by side';

		function updateBarStackedOption() {
			volume_bar_chart.options.scales.y.stacked = !volume_bar_chart.options.scales.y.stacked;
			volume_bar_chart.options.scales.x.stacked = !volume_bar_chart.options.scales.x.stacked;
			volume_bar_chart.update();
			document.getElementById('pp_analytics_bar_mode_text').innerHTML = volume_bar_chart.options.scales.y.stacked ? 'Stacked' : 'Side by side';
		}

		function updateCurrencySelector() {
			let currency = document.getElementById('pp_analytics_currency_selector').value;
			volume_pie_chart.data.labels = Object.keys(pie_volume_data[ currency ]);
			volume_pie_chart.data.datasets[0].data = Object.values(pie_volume_data[ currency ]);
			volume_pie_chart.data.datasets[0].backgroundColor = Object.values(pie_volume_colors[ currency ]);
			volume_pie_chart.update();

			volume_bar_chart.data.datasets = Object.values(bar_volume_data[ currency ]);
			volume_bar_chart.update();

			if(currency.substring(0, 9) === 'Converted' && document.getElementById('pp_analytics_skipped_currencies').innerHTML !== '') {
				document.getElementById('pp_analytics_skipped_currencies_tooltip').style.display = 'flex';
			} else {
				document.getElementById('pp_analytics_skipped_currencies_tooltip').style.display = 'none';
			}
		}

		if(document.getElementById('pp_analytics_skipped_currencies').innerHTML === '') {
			document.getElementById('pp_analytics_skipped_currencies_tooltip').style.display = 'none';
		}

		let modal = document.getElementById('pp-analytics-tutorial-modal');
		let close = document.getElementById('pp-analytics-modal-close');

		if(window.localStorage.getItem('pp-analytics-payment-methods-modal-shown')) {
			modal.style.display = 'none';
		}

		close.onclick = function() {
			modal.style.display = 'none';
			window.localStorage.setItem('pp-analytics-payment-methods-modal-shown', true);
		}

		window.onclick = function() {
			if(event.target == modal) {
				modal.style.display = 'none';
				window.localStorage.setItem('pp-analytics-payment-methods-modal-shown', true);
			}
		}
	</script>

	<?php
}
