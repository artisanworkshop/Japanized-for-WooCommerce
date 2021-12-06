/**
 * Support for changing product's quantity
 * @param event Product data that includes product id and an action which could be either increase/decrease.
 * @returns true if changing quantity succeed, otherwise return false if it fails
 */
async function ppChangeQuantity(event) {
	const data = new FormData();
	data.append('action', 'peachpay_product_quantity_changer');
	data.append('key', event.data.key);
	data.append('value', event.data.amount);

	const response = await fetch(woocommerce_params.ajax_url, {
		method: 'POST',
		body: data,
	});

	return response.status === 200;
}
