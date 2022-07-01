/**
 * Support for Gift cards with the Peachpay Modal. Currently specific only to PW Woocommerce Gift cards.
 */

/**
 * Global to track applied gift card numbers
 *
 * @deprecated
 */
const appliedGiftCards = [];

/**
 * Redeems a gift card on a order.
 *
 * @param { IPHPData } peachpayData Peachpay data context.
 * @param { string } cardNumber The card number in the form of "XXXX-XXXX-XXXX-XXXX"
 * @returns void
 */
// deno-lint-ignore no-unused-vars
async function redeemGiftCard(peachpayData, cardNumber) {
	try {
		const data = new FormData();

		data.append('action', 'pw-gift-cards-redeem');
		data.append('card_number', cardNumber);
		data.append('security', peachpayData.pw_gift_cards_apply_nonce);

		const response = await fetch(peachpayData.wp_ajax_url, {
			method: 'POST',
			body: data,
		});

		const responseData = await response.json();
		if (response.ok && responseData.success) {
			appliedGiftCards.push(cardNumber);
		} else {
			return sendGiftCardAppliedMessage(false, null);
		}
	} catch {
		return sendGiftCardAppliedMessage(false, null);
	}

	// Sweet the gift card was applied but how much did it apply?
	const giftCard = await getGiftCardDetails(cardNumber);
	sendGiftCardAppliedMessage(giftCard !== null, giftCard);
}

/**
 * Gets the current applied gift card amount for the peachpay modal.
 *
 * @param { string } cardNumber The card number in the form of "XXXX-XXXX-XXXX-XXXX"
 * @returns  Promise<{balance: number, card: number} | null>
 */
async function getGiftCardDetails(cardNumber) {
	try {
		const response = await fetch(`${baseStoreURL()}/wp-json/peachpay/v1/compatibility/pw-wc-gift-cards/card/${cardNumber}`);

		if (response.ok) {
			return await response.json();
		}

		return null;
	} catch {
		return null;
	}
}

/**
 * Tells the modal that a gift card has been successfully applied.
 *
 * @param { boolean } success Gift cards Applied response data or false.
 * @param { { balance: number, card_number: string, message?: string} | null } giftCard WC Gift card data.
 */
function sendGiftCardAppliedMessage(success, giftCard) {
	// Message for when something went wrong
	let message = (!success && (giftCard && giftCard.message)) ? giftCard.message : '';

	if (giftCard && Number.parseFloat(giftCard.balance).toFixed(2) === '0.00') {
		success = false;
		message = getPluginLocaleText('This gift card has no money left.');
	}

	document.querySelector('#peachpay-iframe').contentWindow.postMessage({
		event: 'giftCardApplied',
		success,
		message,
		giftCard,
	}, '*');
}

/**
 * Removes all gift cards from the session usually after a purchase.
 */
// deno-lint-ignore no-unused-vars
async function removeGiftCardsFromSession() {
	if (appliedGiftCards) {
		for (const cardNumber of appliedGiftCards) {
			// eslint-disable-next-line no-await-in-loop
			await removeGiftCardFromSession(cardNumber);
		}
	}
}

/**
 * Removes a single gift card from session so it is not reapplied.
 *
 * @param { string } cardNumber The card number in the form of "XXXX-XXXX-XXXX-XXXX"
 * @returns boolean
 */
async function removeGiftCardFromSession(cardNumber) {
	const data = new FormData();

	data.append('action', 'pw-gift-cards-remove');
	data.append('card_number', cardNumber);
	data.append('security', peachpay_data.pw_gift_cards_remove_nonce);

	const response = await fetch(peachpay_data.wp_ajax_url, {
		method: 'POST',
		body: data,
	});

	return response.status === 200;
}
