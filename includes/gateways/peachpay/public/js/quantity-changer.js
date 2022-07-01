/**
 * Support for changing product's quantity
 * @param event Product data that includes product id and an action which could be either increase/decrease.
 * @returns true if changing quantity succeed, otherwise return false if it fails
 */
// deno-lint-ignore no-unused-vars camelcase
async function peachpay_changeQuantity(change) {
	const formData = new FormData();

	formData.append('cart_item_key', change.key);
	formData.append('quantity', change.amount);
	formData.append('absolute', change.set);

	const response = await fetch(`${peachpay_data.wp_home_url}/?wc-ajax=pp-cart-item-quantity`, {
		method: 'POST',
		body: formData,
	});

	return response;
}
