/**
 * Support for adding Woocommerce linked products to cart
 * @param event Product data which includes product's id and sku
 */

async function addLinkedProducttoCart(event) {
	const data = new FormData();
	data.append('action', 'peachpay_woocommerce_ajax_add_to_cart');

	// Event.product_id is for adding upsell item to cart for payment
	// Event.data.productId is for adding upsell item to cart
	const product_id = event.product_id ? event.product_id : event.data.productID;
	data.append('product_id', product_id);
	data.append('variation_id', product_id);
	data.append('quantity', 1);

	const response = await fetch(woocommerce_params.ajax_url, {
		method: 'POST',
		body: data,
	});

	const result = await response.json();

	return {
		success: response.status === 200,
		newCheckoutNonce: result.data._wpnonce,
	};
}
