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

	/**
	 * Subscribe to wp.data payment store changes and send fee update when
	 * the active payment method changes.
	 */
	function initDataStoreSubscription() {
		if ( ! window.wp || ! window.wp.data || 'function' !== typeof window.wp.data.subscribe ) {
			return false;
		}

		window.wp.data.subscribe( function () {
			var currentGatewayId = getActivePaymentMethod();
			if ( currentGatewayId && currentGatewayId !== lastGatewayId ) {
				lastGatewayId = currentGatewayId;
				sendGatewayFee( currentGatewayId );
			}
		} );

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
		if ( gatewayId ) {
			sendGatewayFee( gatewayId );
		}
	}

	// Try to use wp.data store subscription (preferred method).
	// Schedule initialization to ensure wp.data and wc stores are ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initDataStoreSubscription();
		} );
	} else {
		initDataStoreSubscription();
	}

	// Fallback: listen for jQuery change events on radio inputs.
	if ( window.jQuery ) {
		jQuery( document ).on(
			'change',
			'.wc-block-components-radio-control__input',
			function () {
				var gatewayId = getGatewayIdFromDOM() || getActivePaymentMethod();
				if ( gatewayId ) {
					sendGatewayFee( gatewayId );
				}
			}
		);
	}

	// Delayed initial trigger to catch the default payment method after React renders.
	setTimeout( triggerFeeUpdate, 1500 );
	setTimeout( triggerFeeUpdate, 3000 );
} )();
