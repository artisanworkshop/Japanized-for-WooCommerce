/**
 * E2E tests: My Account address edit form — yomigana deduplication
 *
 * Verifies that the yomigana fields appear exactly once in the My Account
 * address edit form for both classic and block checkout setups.
 *
 * Classic checkout: yomigana must appear only via the traditional
 * billing_yomigana_last_name / billing_yomigana_first_name fields
 * (positioned near the name section, NOT duplicated below email).
 *
 * Block checkout: yomigana must appear only via the WC Additional Checkout
 * Fields API (_wc_billing/jp4wc/yomigana_last_name) field — no traditional
 * field is added, so the WC-managed field is the single source of truth.
 */
import { test, expect, type APIRequestContext } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import {
	ADMIN_USER,
	ADMIN_PASS,
	setJp4wcSettings,
	wpFetch,
} from './utils/helpers';

const BASE_URL = process.env.WP_BASE_URL ?? 'http://localhost:8891';

// ---------------------------------------------------------------------------
// Auth helper
// ---------------------------------------------------------------------------

function getAuth(): string {
	// Prefer env var (set by global-setup in the same process).
	if ( process.env.WP_APP_PASSWORD ) {
		return Buffer.from( `${ ADMIN_USER }:${ process.env.WP_APP_PASSWORD }` ).toString( 'base64' );
	}
	// Fallback: read from credentials file written by global-setup.ts.
	const credFile = path.resolve( __dirname, '.auth/credentials.json' );
	if ( fs.existsSync( credFile ) ) {
		const creds = JSON.parse( fs.readFileSync( credFile, 'utf8' ) ) as { user: string; appPassword: string };
		return Buffer.from( `${ creds.user }:${ creds.appPassword }` ).toString( 'base64' );
	}
	return Buffer.from( `${ ADMIN_USER }:${ ADMIN_PASS }` ).toString( 'base64' );
}

// ---------------------------------------------------------------------------
// REST helpers (scoped to this file for clarity)
// ---------------------------------------------------------------------------

async function wcPut(
	request: APIRequestContext,
	endpoint: string,
	body: object,
): Promise<unknown> {
	const res = await request.put( `${ BASE_URL }/wp-json/wc/v3/${ endpoint }`, {
		headers: {
			Authorization: `Basic ${ getAuth() }`,
			'Content-Type': 'application/json',
		},
		data: JSON.stringify( body ),
	} );
	return res.json();
}

async function wcGet(
	request: APIRequestContext,
	endpoint: string,
): Promise<unknown> {
	const res = await request.get( `${ BASE_URL }/wp-json/wc/v3/${ endpoint }`, {
		headers: { Authorization: `Basic ${ getAuth() }` },
	} );
	return res.json();
}

/** Get the current WC checkout page ID from settings. */
async function getCheckoutPageId( request: APIRequestContext ): Promise<string> {
	const settings = ( await wcGet( request, 'settings/advanced' ) ) as { id: string; value: string }[];
	return settings.find( ( s ) => s.id === 'woocommerce_checkout_page_id' )?.value ?? '';
}

/** Set the WC checkout page ID in settings. */
async function setCheckoutPageId(
	request: APIRequestContext,
	pageId: string | number,
): Promise<void> {
	await wcPut( request, 'settings/advanced/woocommerce_checkout_page_id', {
		value: String( pageId ),
	} );
}

// ---------------------------------------------------------------------------
// Page content constants
// ---------------------------------------------------------------------------

const CLASSIC_CHECKOUT_CONTENT =
	`<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->`;

const BLOCK_CHECKOUT_CONTENT =
	`<!-- wp:woocommerce/checkout {"className":"wc-block-checkout"} --><div class="wp-block-woocommerce-checkout is-loading"></div><!-- /wp:woocommerce/checkout -->`;

// ---------------------------------------------------------------------------
// Test suite
// ---------------------------------------------------------------------------

test.describe( 'My Account address edit — yomigana deduplication', () => {
	let originalCheckoutPageId: string;
	let classicPageId: number;
	let blockPageId: number;

	test.beforeAll( async ( { request } ) => {
		// Save the original checkout page ID so we can restore it after.
		originalCheckoutPageId = await getCheckoutPageId( request );

		// Enable yomigana in JP4WC settings.
		await setJp4wcSettings( request, BASE_URL, { yomigana: '1' } );

		// Create a classic checkout page (shortcode).
		const classicPage = await wpFetch( request, BASE_URL, 'pages', 'POST', {
			title: 'Classic Checkout (e2e yomigana)',
			status: 'publish',
			content: CLASSIC_CHECKOUT_CONTENT,
		} ) as { id: number };
		classicPageId = classicPage.id;

		// Create a block checkout page.
		const blockPage = await wpFetch( request, BASE_URL, 'pages', 'POST', {
			title: 'Block Checkout (e2e yomigana)',
			status: 'publish',
			content: BLOCK_CHECKOUT_CONTENT,
		} ) as { id: number };
		blockPageId = blockPage.id;
	} );

	test.afterAll( async ( { request } ) => {
		// Restore original checkout page.
		if ( originalCheckoutPageId ) {
			await setCheckoutPageId( request, originalCheckoutPageId );
		}

		// Clean up test pages.
		for ( const id of [ classicPageId, blockPageId ] ) {
			if ( id ) {
				await wpFetch( request, BASE_URL, `pages/${ id }?force=true`, 'DELETE' );
			}
		}
	} );

	// -----------------------------------------------------------------------
	// Classic checkout
	// -----------------------------------------------------------------------

	test( 'Classic: billing edit shows only traditional yomigana (no WC-API duplicate)', async ( { page, request } ) => {
		await setCheckoutPageId( request, classicPageId );

		// Log in as admin via the login form.
		await page.goto( `${ BASE_URL }/wp-login.php` );
		await page.fill( '#user_login', ADMIN_USER );
		await page.fill( '#user_pass', ADMIN_PASS );
		await page.click( '#wp-submit' );
		await page.waitForURL( /wp-admin/ );

		await page.goto( `${ BASE_URL }/my-account/edit-address/billing/` );
		await page.waitForLoadState( 'networkidle' );

		// Traditional field must be present.
		const traditionalLast  = page.locator( 'input[name="billing_yomigana_last_name"]' );
		const traditionalFirst = page.locator( 'input[name="billing_yomigana_first_name"]' );
		await expect( traditionalLast ).toHaveCount( 1 );
		await expect( traditionalFirst ).toHaveCount( 1 );

		// WC Additional Fields API duplicates must be absent.
		const wcLast  = page.locator( 'input[name="_wc_billing/jp4wc/yomigana_last_name"]' );
		const wcFirst = page.locator( 'input[name="_wc_billing/jp4wc/yomigana_first_name"]' );
		await expect( wcLast ).toHaveCount( 0 );
		await expect( wcFirst ).toHaveCount( 0 );

		// Save a full-page screenshot for visual verification.
		await page.screenshot( { path: 'test-results/classic-billing-edit-form.png', fullPage: true } );
	} );

	test( 'Classic: shipping edit shows only traditional yomigana (no WC-API duplicate)', async ( { page, request } ) => {
		await setCheckoutPageId( request, classicPageId );

		await page.goto( `${ BASE_URL }/wp-login.php` );
		await page.fill( '#user_login', ADMIN_USER );
		await page.fill( '#user_pass', ADMIN_PASS );
		await page.click( '#wp-submit' );
		await page.waitForURL( /wp-admin/ );

		await page.goto( `${ BASE_URL }/my-account/edit-address/shipping/` );
		await page.waitForLoadState( 'networkidle' );

		const traditionalLast  = page.locator( 'input[name="shipping_yomigana_last_name"]' );
		const traditionalFirst = page.locator( 'input[name="shipping_yomigana_first_name"]' );
		await expect( traditionalLast ).toHaveCount( 1 );
		await expect( traditionalFirst ).toHaveCount( 1 );

		const wcLast  = page.locator( 'input[name="_wc_shipping/jp4wc/yomigana_last_name"]' );
		const wcFirst = page.locator( 'input[name="_wc_shipping/jp4wc/yomigana_first_name"]' );
		await expect( wcLast ).toHaveCount( 0 );
		await expect( wcFirst ).toHaveCount( 0 );
	} );

	// -----------------------------------------------------------------------
	// Block checkout
	// -----------------------------------------------------------------------

	test( 'Block: billing edit shows only WC-API yomigana (no traditional duplicate)', async ( { page, request } ) => {
		await setCheckoutPageId( request, blockPageId );

		await page.goto( `${ BASE_URL }/wp-login.php` );
		await page.fill( '#user_login', ADMIN_USER );
		await page.fill( '#user_pass', ADMIN_PASS );
		await page.click( '#wp-submit' );
		await page.waitForURL( /wp-admin/ );

		await page.goto( `${ BASE_URL }/my-account/edit-address/billing/` );
		await page.waitForLoadState( 'networkidle' );

		// WC Additional Fields API field must be present.
		const wcLast  = page.locator( 'input[name="_wc_billing/jp4wc/yomigana_last_name"]' );
		const wcFirst = page.locator( 'input[name="_wc_billing/jp4wc/yomigana_first_name"]' );
		await expect( wcLast ).toHaveCount( 1 );
		await expect( wcFirst ).toHaveCount( 1 );

		// Traditional fields must be absent (block checkout doesn't add them).
		const traditionalLast  = page.locator( 'input[name="billing_yomigana_last_name"]' );
		const traditionalFirst = page.locator( 'input[name="billing_yomigana_first_name"]' );
		await expect( traditionalLast ).toHaveCount( 0 );
		await expect( traditionalFirst ).toHaveCount( 0 );
	} );
} );
