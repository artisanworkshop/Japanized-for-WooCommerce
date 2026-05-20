import { type Page, type APIRequestContext, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

// ---------------------------------------------------------------------------
// WP REST API helper
// ---------------------------------------------------------------------------

/** WP REST API helper for /wp-json/wp/v2/ endpoints */
export async function wpFetch(
	request: APIRequestContext,
	baseURL: string,
	endpoint: string,
	method: 'GET' | 'POST' | 'PUT' | 'DELETE',
	body?: object,
) {
	const res = await request.fetch( `${ baseURL }/wp-json/wp/v2/${ endpoint }`, {
		method,
		headers: {
			Authorization: `Basic ${ getBasicAuth() }`,
			'Content-Type': 'application/json',
		},
		data: body ? JSON.stringify( body ) : undefined,
	} );
	return res.json();
}

/** wp-env default admin user (password is used only for WP login form) */
export const ADMIN_USER = 'admin';
export const ADMIN_PASS = 'password';

/** Read the Application Password written by global-setup.ts. Used by wcGet, wcPut, and related helpers. */
export function getBasicAuth(): string {
	// Prefer env var (set by global-setup in the same process).
	if ( process.env.WP_APP_PASSWORD ) {
		return Buffer.from( `${ ADMIN_USER }:${ process.env.WP_APP_PASSWORD }` ).toString( 'base64' );
	}
	// Fallback: read from credentials file.
	const credFile = path.resolve( __dirname, '../.auth/credentials.json' );
	if ( fs.existsSync( credFile ) ) {
		const creds = JSON.parse( fs.readFileSync( credFile, 'utf8' ) ) as { user: string; appPassword: string };
		return Buffer.from( `${ creds.user }:${ creds.appPassword }` ).toString( 'base64' );
	}
	// Last resort: plain password (works only if Application Passwords are not enforced).
	return Buffer.from( `${ ADMIN_USER }:${ ADMIN_PASS }` ).toString( 'base64' );
}

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------

/** Log in to WordPress wp-admin. */
export async function login( page: Page, baseURL: string ): Promise<void> {
	await page.goto( `${ baseURL }/wp-login.php` );
	await page.fill( '#user_login', ADMIN_USER );
	await page.fill( '#user_pass', ADMIN_PASS );
	await page.click( '#wp-submit' );
	await page.waitForURL( /wp-admin/ );
}

// ---------------------------------------------------------------------------
// WooCommerce REST API helpers
// ---------------------------------------------------------------------------

/** PUT to WC REST API /wc/v3/{endpoint}. */
export async function wcPut(
	request: APIRequestContext,
	baseURL: string,
	endpoint: string,
	body: object,
): Promise<unknown> {
	const res = await request.put( `${ baseURL }/wp-json/wc/v3/${ endpoint }`, {
		headers: {
			Authorization: `Basic ${ getBasicAuth() }`,
			'Content-Type': 'application/json',
		},
		data: JSON.stringify( body ),
	} );
	return res.json();
}

/** GET from WC REST API /wc/v3/{endpoint}. */
export async function wcGet(
	request: APIRequestContext,
	baseURL: string,
	endpoint: string,
): Promise<unknown> {
	const res = await request.get( `${ baseURL }/wp-json/wc/v3/${ endpoint }`, {
		headers: { Authorization: `Basic ${ getBasicAuth() }` },
	} );
	return res.json();
}

/** Get the current WC checkout page ID from WC settings. */
export async function getCheckoutPageId(
	request: APIRequestContext,
	baseURL: string,
): Promise<string> {
	const settings = ( await wcGet( request, baseURL, 'settings/advanced' ) ) as { id: string; value: string }[];
	return settings.find( ( s ) => s.id === 'woocommerce_checkout_page_id' )?.value ?? '';
}

/** Set the WC checkout page ID in WC settings. */
export async function setCheckoutPageId(
	request: APIRequestContext,
	baseURL: string,
	pageId: string | number,
): Promise<void> {
	await wcPut( request, baseURL, 'settings/advanced/woocommerce_checkout_page_id', {
		value: String( pageId ),
	} );
}

/** Set billing or shipping address for a WC customer by user ID. */
export async function setCustomerAddress(
	request: APIRequestContext,
	baseURL: string,
	customerId: number,
	type: 'billing' | 'shipping',
	address: Record< string, string >,
): Promise<void> {
	await wcPut( request, baseURL, `customers/${ customerId }`, { [ type ]: address } );
}

async function wcFetch(
	request: APIRequestContext,
	baseURL: string,
	endpoint: string,
	method: 'GET' | 'POST' | 'PUT' | 'DELETE',
	body?: object,
) {
	const url = `${ baseURL }/wp-json/wc/v3/${ endpoint }`;
	const opts: Parameters< typeof request.fetch >[ 1 ] = {
		method,
		headers: {
			Authorization: `Basic ${ getBasicAuth() }`,
			'Content-Type': 'application/json',
		},
		data: body ? JSON.stringify( body ) : undefined,
	};
	const res = await request.fetch( url, opts );
	return res.json();
}

/** Create a simple physical product. Returns the new product ID. */
export async function createProduct(
	request: APIRequestContext,
	baseURL: string,
	opts: { name?: string; price?: string; virtual?: boolean } = {},
): Promise<number> {
	const data = await wcFetch( request, baseURL, 'products', 'POST', {
		name: opts.name ?? 'Test Product',
		type: 'simple',
		regular_price: opts.price ?? '1000',
		virtual: opts.virtual ?? false,
		manage_stock: false,
		stock_status: 'instock',
	} );
	return data.id as number;
}

/** Delete a product by ID. */
export async function deleteProduct(
	request: APIRequestContext,
	baseURL: string,
	productId: number,
): Promise<void> {
	await wcFetch( request, baseURL, `products/${ productId }?force=true`, 'DELETE' );
}

/** Enable a payment gateway (e.g. 'cod', 'cheque'). */
export async function enablePaymentGateway(
	request: APIRequestContext,
	baseURL: string,
	gatewayId: string,
): Promise<void> {
	await wcFetch( request, baseURL, `payment_gateways/${ gatewayId }`, 'PUT', {
		enabled: true,
	} );
}

/** Set up a catch-all flat-rate shipping zone (¥0 fee). */
export async function setupFreeShippingZone(
	request: APIRequestContext,
	baseURL: string,
): Promise<void> {
	// Check if "Everywhere" zone already has a free-shipping method.
	const zones = ( await wcFetch( request, baseURL, 'shipping/zones', 'GET' ) ) as { id: number; name: string }[];
	const everywhere = zones.find( ( z ) => z.name === 'Locations not covered by your other zones' );
	if ( everywhere ) {
		const methods = ( await wcFetch(
			request, baseURL, `shipping/zones/${ everywhere.id }/methods`, 'GET',
		) ) as { method_id: string }[];
		if ( methods.some( ( m ) => m.method_id === 'free_shipping' ) ) return;
	}

	// Create a new zone covering Japan and add free shipping.
	const zone = ( await wcFetch( request, baseURL, 'shipping/zones', 'POST', {
		name: 'Japan (test)',
	} ) ) as { id: number };

	await wcFetch( request, baseURL, `shipping/zones/${ zone.id }/methods`, 'POST', {
		method_id: 'free_shipping',
	} );
}

/** Delete all orders (for cleanup). */
export async function deleteAllOrders(
	request: APIRequestContext,
	baseURL: string,
): Promise<void> {
	const orders = ( await wcFetch( request, baseURL, 'orders?per_page=50', 'GET' ) ) as { id: number }[];
	await Promise.all(
		orders.map( ( o ) => wcFetch( request, baseURL, `orders/${ o.id }?force=true`, 'DELETE' ) ),
	);
}

// ---------------------------------------------------------------------------
// JP4WC settings via custom REST endpoint
// ---------------------------------------------------------------------------

/** Read JP4WC settings from /jp4wc/v1/settings. */
export async function getJp4wcSettings(
	request: APIRequestContext,
	baseURL: string,
): Promise<Record<string, unknown>> {
	const res = await request.fetch( `${ baseURL }/wp-json/jp4wc/v1/settings`, {
		headers: { Authorization: `Basic ${ getBasicAuth() }` },
	} );
	return res.json();
}

/** Write JP4WC settings to /jp4wc/v1/settings. */
export async function setJp4wcSettings(
	request: APIRequestContext,
	baseURL: string,
	settings: Record<string, unknown>,
): Promise<void> {
	await request.fetch( `${ baseURL }/wp-json/jp4wc/v1/settings`, {
		method: 'POST',
		headers: {
			Authorization: `Basic ${ getBasicAuth() }`,
			'Content-Type': 'application/json',
		},
		data: JSON.stringify( settings ),
	} );
}

// ---------------------------------------------------------------------------
// Browser / page helpers
// ---------------------------------------------------------------------------

/** Add a product to cart by visiting its shop URL. */
export async function addProductToCart(
	page: Page,
	baseURL: string,
	productId: number,
): Promise<void> {
	await page.goto( `${ baseURL }/?add-to-cart=${ productId }` );
	// Wait until the cart widget reflects the addition.
	await page.waitForLoadState( 'networkidle' );
}

/**
 * Fill WooCommerce Classic Checkout billing fields.
 * All fields use Japanese test data appropriate for the JP4WC plugin.
 * Call after selecting billing_country='JP' and waiting for network idle.
 */
export async function fillClassicCheckoutBilling(
	page: Page,
	opts: {
		firstName?: string;
		lastName?: string;
		state?: string;
		address?: string;
		city?: string;
		postcode?: string;
		phone?: string;
		email?: string;
	} = {},
): Promise<void> {
	await page.fill( '#billing_last_name', opts.lastName ?? '田中' );
	await page.fill( '#billing_first_name', opts.firstName ?? '正平' );
	// Select JP prefecture (state). Default: Tokyo (東京都) = JP13.
	await page.selectOption( '#billing_state', opts.state ?? 'JP13' ).catch( () => {} );
	await page.fill( '#billing_postcode', opts.postcode ?? '150-0001' );
	await page.fill( '#billing_address_1', opts.address ?? '渋谷1-1-1' );
	await page.fill( '#billing_city', opts.city ?? '渋谷区' );
	await page.fill( '#billing_phone', opts.phone ?? '0312345678' );
	await page.fill( '#billing_email', opts.email ?? 'test-e2e@example.com' );
}

/** Place the Classic Checkout order and return the received-order URL. */
export async function placeClassicOrder( page: Page ): Promise<string> {
	await page.click( '#place_order' );
	await expect( page ).toHaveURL( /order-received/, { timeout: 20_000 } );
	return page.url();
}
