/**
 * Support for adding Woocommerce linked products to cart
 * @param event Product data which includes product's id and sku
 */

// deno-lint-ignore no-unused-vars camelcase
async function peachpay_addLinkedProductToCart(event) {
	const productId = event.product_id ? event.product_id : event.data.productID;

	const formData = new FormData();
	formData.append('add-to-cart', productId);
	formData.append('quantity', 1);

	const response = await fetch(peachpay_data.wp_home_url + '/?wc-ajax=add-to-cart', {
		method: 'POST',
		headers: { Accept: 'application/json' },
		body: formData,
	});

	if (!response.ok) {
		return false;
	}

	peachpay_refreshFragments();

	return true;
}
