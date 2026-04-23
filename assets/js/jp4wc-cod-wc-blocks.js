( function () {
	'use strict';

	/**
	 * Send the selected gateway ID to the server via extensionCartUpdate.
	 * Only sends when a valid gateway_id is available.
	 *
	 * @param {string} gatewayId The selected payment method ID.
	 */
	function sendGatewayFee( gatewayId ) {
		if (
			! jp4wc_cod_blocks_param.is_checkout ||
			'yes' !== jp4wc_cod_blocks_param.is_gateway_fee_enabled
		) {
			return;
		}

		// Guard: extensionCartUpdate must be available (wc-blocks-checkout / wc-cart-checkout-base).
		if (
			! window.wc ||
			! window.wc.blocksCheckout ||
			'function' !== typeof window.wc.blocksCheckout.extensionCartUpdate
		) {
			return;
		}

		window.wc.blocksCheckout.extensionCartUpdate( {
			namespace: 'jp4wc-add-gateway-fee',
			data: {
				action: 'add-fee',
				gateway_id: gatewayId,
			},
		} );
	}

	/**
	 * Read the active payment method from the WC blocks payment store.
	 *
	 * @returns {string|null}
	 */
	function getActivePaymentMethod() {
		if (
			window.wp &&
			window.wp.data &&
			'function' === typeof window.wp.data.select
		) {
			var paymentStore = window.wp.data.select( 'wc/store/payment' );
			if ( paymentStore && 'function' === typeof paymentStore.getActivePaymentMethod ) {
				return paymentStore.getActivePaymentMethod() || null;
			}
		}
		return null;
	}

	var lastGatewayId = null;
	var subscribed    = false;

	/**
	 * Subscribe to wp.data payment store changes and send fee update when
	 * the active payment method changes.
	 *
	 * @returns {boolean} true if subscription was set up successfully.
	 */
	function initDataStoreSubscription() {
		if ( subscribed ) {
			return true;
		}
		if ( ! window.wp || ! window.wp.data || 'function' !== typeof window.wp.data.subscribe ) {
			return false;
		}
		// Verify the payment store is already registered before subscribing.
		if ( ! window.wp.data.select( 'wc/store/payment' ) ) {
			return false;
		}

		window.wp.data.subscribe( function () {
			var currentGatewayId = getActivePaymentMethod();
			if ( currentGatewayId && currentGatewayId !== lastGatewayId ) {
				lastGatewayId = currentGatewayId;
				sendGatewayFee( currentGatewayId );
			}
		} );

		subscribed = true;
		return true;
	}

	/**
	 * Fallback: read gateway ID from DOM radio input.
	 *
	 * @returns {string|undefined}
	 */
	function getGatewayIdFromDOM() {
		var input = document.querySelector(
			'input[name="radio-control-wc-payment-method-options"]:checked'
		);
		return input ? input.value : undefined;
	}

	/**
	 * Fallback trigger: send fee based on current DOM or data-store state.
	 * Only sends when a gateway is actually selected.
	 */
	function triggerFeeUpdate() {
		var gatewayId = getActivePaymentMethod() || getGatewayIdFromDOM();
		if ( gatewayId && gatewayId !== lastGatewayId ) {
			lastGatewayId = gatewayId;
			sendGatewayFee( gatewayId );
		}
	}

	/**
	 * Try to subscribe; if the store is not ready yet, retry with exponential back-off.
	 * Gives up after ~10 s (attempts: 100 ms, 200 ms, 400 ms, 800 ms, 1600 ms, 3200 ms, ...).
	 *
	 * @param {number} delay Next retry delay in ms.
	 */
	function trySubscribeWithRetry( delay ) {
		if ( initDataStoreSubscription() ) {
			// Subscription successful — send the current state immediately.
			triggerFeeUpdate();
			return;
		}

		var nextDelay = delay * 2;
		if ( nextDelay > 10000 ) {
			// Store never became available; fall back to DOM polling.
			triggerFeeUpdate();
			return;
		}

		setTimeout( function () {
			trySubscribeWithRetry( nextDelay );
		}, delay );
	}

	// Fallback: listen for jQuery change events on radio inputs.
	if ( window.jQuery ) {
		jQuery( document ).on(
			'change',
			'.wc-block-components-radio-control__input',
			function () {
				var gatewayId = getGatewayIdFromDOM() || getActivePaymentMethod();
				if ( gatewayId ) {
					lastGatewayId = gatewayId;
					sendGatewayFee( gatewayId );
				}
			}
		);
	}

	// Bootstrap: wait for DOM then start the subscription with retry logic.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			trySubscribeWithRetry( 100 );
		} );
	} else {
		trySubscribeWithRetry( 100 );
	}
} )();
