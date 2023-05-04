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
<!-- Nav bar -->
<div class='pp-analytics-payment-methods-container'>
<div id='pp-analytics-header' class='pp-analytics-payment-methods-row' style='justify-content: space-between;'>
	<h1>
		<div class='pp-analytics-icon abandoned-cart-icon'></div>
		Abandoned carts
	</h1>
</div>
<div class='pp-analytics-payment-methods-matrix'>
	<div class='pp-analytics-payment-methods-row' id='pp-analytics-header-options'>
		<span class='pp-analytics-statistic-button-flex active' id='pp-analytics-total-cart-interval' style='width:33.3%'
			connect-graph-id='pp-analytics-all-carts-page'>
			<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
			<h1 class='pp-analytics-statistic'><?php echo esc_html( $cart_count_total ); ?></h1>
			<div class='pp-analytics-graph-title'><?php esc_html_e( 'Total carts', 'peachpay-for-woocommerce' ); ?></div>
		</span>
		<span class='pp-analytics-statistic-button-flex' id='pp-analytics-recoverable-carts' style='width:33.3%'
			connect-graph-id='pp-analytics-recoverable-carts-page'>
			<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
			<h1 class='pp-analytics-statistic'><?php echo esc_html( $recoverable_cart_count ); ?></h1>
			<div class='pp-analytics-graph-title'><?php esc_html_e( 'Recoverable carts', 'peachpay-for-woocommerce' ); ?></div>
		</span>
		<span class='pp-analytics-statistic-button-flex' id='pp-analytics-recoverable-carts' style='width:33.3%'
			connect-graph-id='pp-analytics-unrecoverable-carts-page'>
			<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
			<h1 class='pp-analytics-statistic'><?php echo esc_html( $unrecoverable_cart_count ); ?></h1>
			<div class='pp-analytics-graph-title'><?php esc_html_e( 'Abandoned carts', 'peachpay-for-woocommerce' ); ?></div>
		</span>
	</div>
	<!-- All carts page -->
	<div class='pp-analytics-payment-methods-col horizontal' id='pp-analytics-all-carts-page'>
		<div class='pp-analytics-payment-methods-wide-graph' id='pp-analytics-browser-stats-interval-graph'>
			<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
			<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
				<div class='pp-analytics-payment-methods-header left'>
					<h1 class='pp-analytics-graph-title'>All cart stats</h1>
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
					<canvas id='pp_total_cart_interval_graph'></canvas>
				</div>
			</div>
		</div>
		<div class='pp-analytics-payment-methods-thin-graph' style='width:calc(50% - 8px)'>
			<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
			<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
				<h1 class='pp-analytics-graph-title'><?php esc_html_e( 'Carts breakdown', 'peachpay-for-woocommerce' ); ?></h1>
			</div>
			<hr>
			<canvas id='pp_abandon_percent_graph'></canvas>
		</div>
	</div>
	<!-- Recoverable carts page -->
	<div class='pp-analytics-payment-methods-col horizontal' id='pp-analytics-recoverable-carts-page'>
		<div class='pp-analytics-payment-methods-wide-graph' id='pp-analytics-browser-stats-interval-graph'>
			<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
			<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
				<div class='pp-analytics-payment-methods-header left'>
					<h1 class='pp-analytics-graph-title'>Recoverable cart stats</h1>
				</div>
				<div class='pp-analytics-payment-methods-header right'>
					<div id='pp-analytics-interval-graph-type-first' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
					<div id='pp-analytics-interval-graph-interval-first' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
					<div id='pp-analytics-interval-graph-time-span-first' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
				</div>
			</div>
			<hr>
			<div class='pp-analytics-payment-methods-wide-graph-canvas-scrollable'>
				<div class='pp-analytics-payment-methods-wide-graph-canvas-container'>
					<canvas id='pp_recoverable_cart_count_graph'></canvas>
				</div>
			</div>
		</div>
		<div class='pp-analytics-payment-methods-wide-graph' id='pp-analytics-browser-stats-interval-graph'>
			<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
			<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
				<div class='pp-analytics-payment-methods-header left'>
					<h1 class='pp-analytics-graph-title'>Recoverable revenue<div id='pp-analytics-recoverable-currency' class='pp-analytics-currency-selected'></div></h1>
				</div>
				<div class='pp-analytics-payment-methods-header right'>
					<div id='pp-analytics-interval-graph-type-second' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
					<div id='pp-analytics-interval-graph-interval-second' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
					<div id='pp-analytics-interval-graph-time-span-second' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
				</div>
			</div>
			<hr>
			<div class='pp-analytics-payment-methods-wide-graph-canvas-scrollable'>
				<div class='pp-analytics-payment-methods-wide-graph-canvas-container'>
					<canvas id='pp_recoverable_revenue_graph'></canvas>
				</div>
			</div>
		</div>
	</div>
	<!-- Unrecoverable carts page -->
	<div class='pp-analytics-payment-methods-col horizontal' id='pp-analytics-unrecoverable-carts-page'>
		<div class='pp-analytics-payment-methods-wide-graph' id='pp-analytics-browser-stats-interval-graph'>
			<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
			<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
				<div class='pp-analytics-payment-methods-header left'>
					<h1 class='pp-analytics-graph-title'>Abandoned cart stats</h1>
				</div>
				<div class='pp-analytics-payment-methods-header right'>
					<div id='pp-analytics-interval-graph-type-first' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
					<div id='pp-analytics-interval-graph-interval-first' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
					<div id='pp-analytics-interval-graph-time-span-first' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
				</div>
			</div>
			<hr>
			<div class='pp-analytics-payment-methods-wide-graph-canvas-scrollable'>
				<div class='pp-analytics-payment-methods-wide-graph-canvas-container'>
					<canvas id='pp_unrecoverable_cart_count_graph'></canvas>
				</div>
			</div>
		</div>
		<div class='pp-analytics-payment-methods-wide-graph' id='pp-analytics-browser-stats-interval-graph'>
			<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
			<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
				<div class='pp-analytics-payment-methods-header left'>
					<h1 class='pp-analytics-graph-title'>Abandoned revenue<div id='pp-analytics-abandoned-revenue-currency' class='pp-analytics-currency-selected'></div></h1>
				</div>
				<div class='pp-analytics-payment-methods-header right'>
					<div id='pp-analytics-interval-graph-type-second' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
					<div id='pp-analytics-interval-graph-interval-second' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
					<div id='pp-analytics-interval-graph-time-span-second' class='pp-analytics-payment-methods-row' style='justify-content: end;'></div>
				</div>
			</div>
			<hr>
			<div class='pp-analytics-payment-methods-wide-graph-canvas-scrollable'>
				<div class='pp-analytics-payment-methods-wide-graph-canvas-container'>
					<canvas id='pp_abandoned_revenue_graph'></canvas>
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

		peachpayAnalytics.addOptionMenu( document.getElementById('pp-analytics-header'), 'currency', { currencies: currencyOptions, activeCurrencies } );

		const allCartsPage = document.getElementById('pp-analytics-all-carts-page');
		peachpayAnalytics.addOptionMenu( allCartsPage.querySelector( '#pp-analytics-interval-graph-interval' ), 'interval' );
		peachpayAnalytics.addOptionMenu( allCartsPage.querySelector( '#pp-analytics-interval-graph-type' ), 'interval_type' );
		peachpayAnalytics.addOptionMenu( allCartsPage.querySelector( '#pp-analytics-interval-graph-time-span' ), 'time_span' );
		const recoverableCartsPage = document.getElementById('pp-analytics-recoverable-carts-page');
		peachpayAnalytics.addOptionMenu( recoverableCartsPage.querySelector( '#pp-analytics-interval-graph-interval-first' ), 'interval' );
		peachpayAnalytics.addOptionMenu( recoverableCartsPage.querySelector( '#pp-analytics-interval-graph-type-first' ), 'interval_type' );
		peachpayAnalytics.addOptionMenu( recoverableCartsPage.querySelector( '#pp-analytics-interval-graph-time-span-first' ), 'time_span' );
		peachpayAnalytics.addOptionMenu( recoverableCartsPage.querySelector( '#pp-analytics-interval-graph-interval-second' ), 'interval' );
		peachpayAnalytics.addOptionMenu( recoverableCartsPage.querySelector( '#pp-analytics-interval-graph-type-second' ), 'interval_type' );
		peachpayAnalytics.addOptionMenu( recoverableCartsPage.querySelector( '#pp-analytics-interval-graph-time-span-second' ), 'time_span' );
		const unrecoverableCartsPage = document.getElementById('pp-analytics-unrecoverable-carts-page');
		peachpayAnalytics.addOptionMenu( unrecoverableCartsPage.querySelector( '#pp-analytics-interval-graph-interval-first' ), 'interval' );
		peachpayAnalytics.addOptionMenu( unrecoverableCartsPage.querySelector( '#pp-analytics-interval-graph-type-first' ), 'interval_type' );
		peachpayAnalytics.addOptionMenu( unrecoverableCartsPage.querySelector( '#pp-analytics-interval-graph-time-span-first' ), 'time_span' );
		peachpayAnalytics.addOptionMenu( unrecoverableCartsPage.querySelector( '#pp-analytics-interval-graph-interval-second' ), 'interval' );
		peachpayAnalytics.addOptionMenu( unrecoverableCartsPage.querySelector( '#pp-analytics-interval-graph-type-second' ), 'interval_type' );
		peachpayAnalytics.addOptionMenu( unrecoverableCartsPage.querySelector( '#pp-analytics-interval-graph-time-span-second' ), 'time_span' );

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
				query: <?php echo wp_json_encode( $cart_count_total_query ); ?>,
				chart_id: 'pp-analytics-total-cart-interval'
			}
		);
		peachpayAnalytics.subscribe(
			'total',
			{
				query: <?php echo wp_json_encode( $recoverable_cart_count_query ); ?>,
				chart_id: 'pp-analytics-recoverable-carts'
			}
		);
		peachpayAnalytics.subscribe(
			'total',
			{
				query: <?php echo wp_json_encode( $unrecoverable_cart_count_query ); ?>,
				chart_id: 'pp-analytics-recoverable-carts'
			}
		);

		// Total cart graph
		const totalCartsChart = peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $total_cart_count_interval_query ); ?>,
				data: <?php echo wp_json_encode( $total_cart_count_interval ); ?>,
				chart_id: 'pp_total_cart_interval_graph'
			}
		);
		peachpayAnalytics.subscribe(
			'dateFormat',
			totalCartsChart
		);

		// Cart Abandonment % Graph
		peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $cart_count_metric_query ); ?>,
				data: <?php echo wp_json_encode( $cart_count_metric ); ?>,
				chart_id: 'pp_abandon_percent_graph'
			}
		);

		// Recoverable cart count graph
		const recoverableCartsChart = peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $recoverable_cart_interval_query ); ?>,
				data: <?php echo wp_json_encode( $recoverable_cart_interval ); ?>,
				chart_id: 'pp_recoverable_cart_count_graph'
			}
		);
		peachpayAnalytics.subscribe(
			'dateFormat',
			recoverableCartsChart
		);

		// Recoverable Revenue Graph
		peachpayAnalytics.subscribe(
			'currency',
			document.getElementById('pp-analytics-recoverable-currency')
		);
		let recoverableRevenueChart = peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $recoverable_volume_interval_query ); ?>,
				data: <?php echo wp_json_encode( $recoverable_volume_interval ); ?>,
				chart_id: 'pp_recoverable_revenue_graph'
			}
		);
		peachpayAnalytics.subscribe(
			'dateFormat',
			recoverableRevenueChart
		);

		// Unrecoverable cart count graph
		const unrecoverableCartsChart = peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $unrecoverable_cart_interval_query ); ?>,
				data: <?php echo wp_json_encode( $unrecoverable_cart_interval ); ?>,
				chart_id: 'pp_unrecoverable_cart_count_graph'
			}
		);
		peachpayAnalytics.subscribe(
			'dateFormat',
			unrecoverableCartsChart
		);

		// Abandoned Revenue Graph
		peachpayAnalytics.subscribe(
			'currency',
			document.getElementById('pp-analytics-abandoned-revenue-currency')
		);
		const unrecoverableRevenueChart = peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $unrecoverable_volume_interval_query ); ?>,
				data: <?php echo wp_json_encode( $unrecoverable_volume_interval ); ?>,
				chart_id: 'pp_abandoned_revenue_graph'
			}
		);
		peachpayAnalytics.subscribe(
			'dateFormat',
			unrecoverableRevenueChart
		);

		recoverableCartsPage.style.display = 'none';
		unrecoverableCartsPage.style.display = 'none';
	});
</script>
