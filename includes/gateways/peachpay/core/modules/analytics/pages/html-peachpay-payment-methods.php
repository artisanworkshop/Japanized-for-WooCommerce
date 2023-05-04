<?php
/**
 * PeachPay payment methods page.
 *
 * @var $order_count
 * @var $volume_count
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;
?>
<div class='pp-analytics-payment-methods-container'>
	<div id='pp-analytics-header' class='pp-analytics-payment-methods-row' style='justify-content: space-between;'>
		<h1>
			<div class='pp-analytics-icon payments-icon'></div>
			Payments
		</h1>
	</div>
	<div class='pp-analytics-payment-methods-matrix'>
		<div class='pp-analytics-payment-methods-row' id='pp-analytics-header-options'>
			<span class='pp-analytics-statistic-button-flex active' id='pp-analytics-total-order-stats' style='width:50%'
				connect-graph-id='pp-analytics-orders'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<h1 class='pp-analytics-statistic'><?php echo esc_html( $total_orders ); ?></h1>
				<div class='pp-analytics-graph-title'><?php esc_html_e( 'orders', 'peachpay-for-woocommerce' ); ?></div>
			</span>
			<span class='pp-analytics-statistic-button-flex' id='pp-analytics-total-volume-stats' style='width:50%'
				connect-graph-id='pp-analytics-volume'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<h1 class='pp-analytics-statistic'><?php echo esc_html( $total_volume ); ?></h1>
				<div class='pp-analytics-graph-title'>
					<?php esc_html_e( 'total volume', 'peachpay-for-woocommerce' ); ?>
					<div id='pp-analytics-total-volume-count-currency' class='pp-analytics-currency-selected'></div>
				</div>
			</span>
		</div>
		<div class='pp-analytics-payment-methods-col horizontal' id='pp-analytics-orders'>
			<div class='pp-analytics-payment-methods-wide-graph'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
					<div class='pp-analytics-payment-methods-header left'>
						<h1 class='pp-analytics-graph-title'>Orders by method<div id='pp-analytics-orders-currency' class='pp-analytics-currency-selected'></div></h1>
					</div>
					<div class='pp-analytics-payment-methods-header right'>
						<div id='pp-analytics-interval-graph-type' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
						<div id='pp-analytics-interval-graph-interval' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
						<div id='pp-analytics-interval-graph-time-span' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
					</div>
				</div>
				<hr>
				<div class='pp-analytics-payment-methods-wide-graph-canvas-scrollable'>
					<div class='pp-analytics-payment-methods-wide-graph-canvas-container'>
						<canvas id='pp_analytics_payment_type_order_line_chart'></canvas>
					</div>
				</div>
			</div>
			<div class='pp-analytics-payment-methods-thin-graph' style='width:calc(50% - 8px)'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
					<h1 class='pp-analytics-graph-title'>Currency breakdown by order</h1>
				</div>
				<hr>
				<canvas id='pp_analytics_currency_order_pie_chart'></canvas>
			</div>
		</div>
		<div class='pp-analytics-payment-methods-col horizontal' id='pp-analytics-volume'>
			<div class='pp-analytics-payment-methods-wide-graph'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
					<div class='pp-analytics-payment-methods-header left'>
						<h1 class='pp-analytics-graph-title'>Volume by method<div id='pp-analytics-volume-by-method-currency' class='pp-analytics-currency-selected'></div></h1>
					</div>
					<div class='pp-analytics-payment-methods-header right'>
						<div id='pp-analytics-interval-graph-type' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
						<div id='pp-analytics-interval-graph-interval' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
						<div id='pp-analytics-interval-graph-time-span' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
					</div>
				</div>
				<hr>
				<div class='pp-analytics-payment-methods-wide-graph-canvas-scrollable'>
					<div class='pp-analytics-payment-methods-wide-graph-canvas-container'>
						<canvas id='pp_analytics_payment_type_volume_bar_chart'></canvas>
					</div>
				</div>
			</div>
			<div class='pp-analytics-payment-methods-thin-graph' style='width:calc(50% - 8px)'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
					<h1 class='pp-analytics-graph-title'>Volume breakdown<div id='pp-analytics-volume-breakdown-currency' class='pp-analytics-currency-selected'></div></h1>
				</div>
				<hr>
				<div class='pp-analytics-payment-methods-wide-graph-canvas-scrollable'>
					<div class='pp-analytics-payment-methods-wide-graph-canvas-container'>
						<canvas id='pp_analytics_payment_type_volume_pie_chart'></canvas>
					</div>
				</div>
			</div>
		</div>
	</div>
	<!-- Analytics definitions popup -->
	<div class='pp-analytics-definitions-footer'>
		<div class='pp-analytics-definitions-footer-inner'>
			<div class='pp-analytics-icon information-icon'></div>
			<h4>Not sure what these mean?</h4>
		</div>
		<h4 id='pp-analytics-definitions-popup'>Read our Analytics Guide</h4>
	</div>
</div>
<!-- Analytics Modal -->
<div id='pp-analytics-modal' class='pp-analytics-modal'>
	<div class='pp-analytics-modal-content'>
		<div class='pp-analytics-modal-title'>
			<h1 class='pp-analytics-modal-h1'></h1>
			<span id='pp-analytics-modal-close' class='pp-analytics-modal-close'>&times;</span>
		</div>
		<div class='pp-analytics-modal-message'></div>
	</div>
</div>
<script>
	let currencyOptions   = <?php echo wp_json_encode( $currency_options ); ?>;
	let activeCurrencies  = <?php echo wp_json_encode( $currency ); ?>;
	let dateFormatMap     = <?php echo wp_json_encode( $format_map ); ?>;
	let peachpayAnalytics = new PeachPayAnalyticsUtil({
		dateFormat: dateFormatMap,
	});

	document.addEventListener('DOMContentLoaded', () => {
		const navButtons = document.querySelectorAll('.peachpay .nav-tab-wrapper.peachpay-accordion a.nav-tab');
		for ( let navButton = 0; navButton < navButtons.length; navButton++ ) {
			peachpayAnalytics.subscribe('params', (params) => {
				const navURL = new URL(navButtons[navButton].href);
				Object.keys(params).forEach(paramKey => {
					navURL.searchParams.set(paramKey, params[paramKey]);
				});
				navButtons[navButton].setAttribute('href', navURL.toString());
			});
		}

		document.getElementById('pp-analytics-definitions-popup').addEventListener('click', function() { peachpayAnalytics.analyticsDefinitionModal() });

		const orderStats = document.getElementById('pp-analytics-orders');
		const volumeStats = document.getElementById('pp-analytics-volume');

		peachpayAnalytics.addOptionMenu( orderStats.querySelector('#pp-analytics-interval-graph-type'), 'interval_type' );
		peachpayAnalytics.addOptionMenu( orderStats.querySelector('#pp-analytics-interval-graph-interval'), 'interval' );
		peachpayAnalytics.addOptionMenu( orderStats.querySelector( '#pp-analytics-interval-graph-time-span' ), 'time_span' );

		peachpayAnalytics.addOptionMenu( volumeStats.querySelector('#pp-analytics-interval-graph-type'), 'interval_type' );
		peachpayAnalytics.addOptionMenu( volumeStats.querySelector('#pp-analytics-interval-graph-interval'), 'interval' );
		peachpayAnalytics.addOptionMenu( volumeStats.querySelector( '#pp-analytics-interval-graph-time-span' ), 'time_span' );

		peachpayAnalytics.addOptionMenu( document.getElementById('pp-analytics-header'), 'currency', { currencies: currencyOptions, activeCurrencies } );

		const statisticButtons = document.getElementById('pp-analytics-header-options').querySelectorAll('.pp-analytics-statistic-button-flex');
		for (let button = 0; button < statisticButtons.length; button++) {
			statisticButtons[button].addEventListener('click', function() {
				for (let node = 0; node < statisticButtons.length; node++) {
					statisticButtons[node].classList.remove('active');
					document.getElementById( statisticButtons[node].getAttribute('connect-graph-id') ).style.display = 'none';
				}

				this.classList.add('active');
				document.getElementById( this.getAttribute('connect-graph-id') ).style.display = 'flex';
			});
		}

		peachpayAnalytics.subscribe(
			'total',
			{
				query: <?php echo wp_json_encode( $order_count_query ); ?>,
				chart_id: 'pp-analytics-total-order-stats'
			}
		);
		peachpayAnalytics.subscribe(
			'total',
			{
				query: <?php echo wp_json_encode( $total_volume_query ); ?>,
				chart_id: 'pp-analytics-total-volume-stats'
			}
		);
		peachpayAnalytics.subscribe(
			'currency',
			document.getElementById('pp-analytics-total-volume-count-currency')
		);

		peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $currency_breakdown_query ); ?>,
				data: <?php echo wp_json_encode( $currency_breakdown ); ?>,
				chart_id: 'pp_analytics_currency_order_pie_chart'
			}
		);

		peachpayAnalytics.subscribe(
			'currency',
			document.getElementById('pp-analytics-volume-by-method-currency')
		);
		const orderChart = peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $order_interval_query ); ?>,
				data: <?php echo wp_json_encode( $order_interval ); ?>,
				chart_id: 'pp_analytics_payment_type_order_line_chart'
			}
		);
		peachpayAnalytics.subscribe(
			'dateFormat',
			orderChart
		);

		peachpayAnalytics.subscribe(
			'currency',
			document.getElementById('pp-analytics-volume-by-method-currency')
		);
		peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $volume_count_query ); ?>,
				data: <?php echo wp_json_encode( $volume_count ); ?>,
				chart_id: 'pp_analytics_payment_type_volume_pie_chart'
			}
		);
		peachpayAnalytics.subscribe(
			'currency',
			document.getElementById('pp-analytics-volume-breakdown-currency')
		);
		const volumeChart = peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $volume_interval_query ); ?>,
				data: <?php echo wp_json_encode( $volume_interval ); ?>,
				chart_id: 'pp_analytics_payment_type_volume_bar_chart',
			}
		);
		peachpayAnalytics.subscribe(
			'dateFormat',
			volumeChart
		);

		volumeStats.style.display = 'none';
	});
</script>
