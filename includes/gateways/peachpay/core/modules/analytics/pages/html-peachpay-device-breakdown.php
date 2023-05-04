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
			<div class='pp-analytics-icon devices-icon'></div>
			Devices
		</h1>
	</div>
	<div class='pp-analytics-payment-methods-matrix'>
		<div class='pp-analytics-payment-methods-row' id='pp-analytics-header-options'>
			<span class='pp-analytics-statistic-button-flex active' id='pp-analytics-browser-stats' style='width:50%'
				connect-graph-id='pp-analytics-browser-stats-page'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<h1 class='pp-analytics-statistic'><?php echo esc_html( $browser_total ); ?></h1>
				<div class='pp-analytics-graph-title'><?php esc_html_e( 'browsers', 'peachpay-for-woocommerce' ); ?></div>
			</span>
			<span class='pp-analytics-statistic-button-flex' id='pp-analytics-browser-stats' style='width:50%'
				connect-graph-id='pp-analytics-operating-system-stats-page'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<h1 class='pp-analytics-statistic'><?php echo esc_html( $operating_system_total ); ?></h1>
				<div class='pp-analytics-graph-title'><?php esc_html_e( 'operating systems', 'peachpay-for-woocommerce' ); ?></div>
			</span>
		</div>
		<div class='pp-analytics-payment-methods-col horizontal' id='pp-analytics-browser-stats-page'>
			<div class='pp-analytics-payment-methods-wide-graph' id='pp-analytics-browser-stats-interval-graph'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
					<div class='pp-analytics-payment-methods-header left'>
						<h1 class='pp-analytics-graph-title'>Browser stats</h1>
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
						<canvas id='pp_analytics_browser_type_line_chart'></canvas>
					</div>
				</div>
			</div>
			<div class='pp-analytics-payment-methods-thin-graph' style='width:calc(50% - 8px)'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
					<h1 class='pp-analytics-graph-title'><?php esc_html_e( 'Browser breakdown', 'peachpay-for-woocommerce' ); ?></h1>
				</div>
				<hr>
				<canvas id='pp-analytics-browser-type-pie-chart'></canvas>
			</div>
		</div>
		<div class='pp-analytics-payment-methods-col horizontal' id='pp-analytics-operating-system-stats-page'>
			<div class='pp-analytics-payment-methods-wide-graph'  id='pp-analytics-operating-system-stats-interval-graph'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
					<div class='pp-analytics-payment-methods-header left'>
						<h1 class='pp-analytics-graph-title'>Operating system stats</h1>
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
						<canvas id='pp_analytics_operating_system_type_line_chart'></canvas>
					</div>
				</div>
			</div>
			<div class='pp-analytics-payment-methods-thin-graph' style='width:calc(50% - 8px)'>
				<div class='pp-analytics-loader'><div class='pp-analytics-loader-inner'><div class='pp-analytics-loader-inner-cover'></div></div></div>
				<div class='pp-analytics-graph-header pp-analytics-payment-methods-header'>
					<h1 class='pp-analytics-graph-title'><?php esc_html_e( 'Operating system breakdown', 'peachpay-for-woocommerce' ); ?></h1>
				</div>
				<hr>
				<canvas id='pp-analytics-os-type-pie-chart'></canvas>
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

		const browserIntervalGraph = document.getElementById('pp-analytics-browser-stats-page');
		const operatingSystemIntervalGraph = document.getElementById('pp-analytics-operating-system-stats-page');

		peachpayAnalytics.addOptionMenu( browserIntervalGraph.querySelector( '#pp-analytics-interval-graph-interval' ), 'interval' );
		peachpayAnalytics.addOptionMenu( browserIntervalGraph.querySelector( '#pp-analytics-interval-graph-type' ), 'interval_type' );
		peachpayAnalytics.addOptionMenu( browserIntervalGraph.querySelector( '#pp-analytics-interval-graph-time-span' ), 'time_span' );

		peachpayAnalytics.addOptionMenu( operatingSystemIntervalGraph.querySelector( '#pp-analytics-interval-graph-interval' ), 'interval' );
		peachpayAnalytics.addOptionMenu( operatingSystemIntervalGraph.querySelector( '#pp-analytics-interval-graph-type' ), 'interval_type' );
		peachpayAnalytics.addOptionMenu( operatingSystemIntervalGraph.querySelector( '#pp-analytics-interval-graph-time-span' ), 'time_span' );

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

		document.getElementById('pp-analytics-definitions-popup').addEventListener('click', function() { peachpayAnalytics.analyticsDefinitionModal() });

		peachpayAnalytics.subscribe(
			'total',
			{
				query: <?php echo wp_json_encode( $browser_total_query ); ?>,
				chart_id: 'pp-analytics-browser-stats'
			}
		);
		peachpayAnalytics.subscribe(
			'total',
			{
				query: <?php echo wp_json_encode( $operating_system_total_query ); ?>,
				chart_id: 'pp-analytics-browser-stats'
			}
		);

		peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $browser_count_query ); ?>,
				data: <?php echo wp_json_encode( $browser_count ); ?>,
				chart_id: 'pp-analytics-browser-type-pie-chart'
			}
		);
		peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $operating_system_count_query ); ?>,
				data: <?php echo wp_json_encode( $operating_system_count ); ?>,
				chart_id: 'pp-analytics-os-type-pie-chart'
			}
		);

		const browserIntervalChart = peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $browser_interval_query ); ?>,
				data: <?php echo wp_json_encode( $browser_interval ); ?>,
				chart_id: 'pp_analytics_browser_type_line_chart'
			}
		);
		peachpayAnalytics.subscribe(
			'dateFormat',
			browserIntervalChart
		);
		const operatingSystemIntervalChart = peachpayAnalytics.subscribe(
			'chart',
			{
				query: <?php echo wp_json_encode( $operating_system_interval_query ); ?>,
				data: <?php echo wp_json_encode( $operating_system_interval ); ?>,
				chart_id: 'pp_analytics_operating_system_type_line_chart'
			}
		);
		peachpayAnalytics.subscribe(
			'dateFormat',
			operatingSystemIntervalChart
		);

		operatingSystemIntervalGraph.style.display = 'none';
	});
</script>
