/**
 * E2E tests: My Account address view page — yomigana deduplication
 *
 * Verifies that on the My Account address display page (/my-account/?edit-address),
 * yomigana appears exactly once regardless of whether classic or block checkout
 * is configured.
 *
 * Both classic and block checkout: yomigana must appear only via the JP-formatted
 * address string (address_formats + formatted_address). WC Additional Fields API
 * render_address_fields() must NOT output a duplicate below the formatted block.
 */
import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';
import {
	ADMIN_USER,
	ADMIN_PASS,
	getJp4wcSettings,
	setJp4wcSettings,
	wpFetch,
	wcPut,
	getCheckoutPageId,
	setCheckoutPageId,
	setCustomerAddress,
} from './utils/helpers';

const BASE_URL = process.env.WP_BASE_URL ?? 'http://localhost:8891';

// ---------------------------------------------------------------------------
// Page content constants
// ---------------------------------------------------------------------------

const CLASSIC_CHECKOUT_CONTENT =
	`<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->`;

const BLOCK_CHECKOUT_CONTENT =
	`<!-- wp:woocommerce/checkout {"className":"wc-block-checkout"} --><div class="wp-block-woocommerce-checkout is-loading"></div><!-- /wp:woocommerce/checkout -->`;

// ---------------------------------------------------------------------------
// CLI helper — set user meta via wp-env (WP REST API doesn't expose custom meta keys)
// ---------------------------------------------------------------------------

/**
 * Set arbitrary usermeta via wp-env CLI.
 * Uses the tests-cli container targeting the test environment (port 8891).
 */
function setUserMetaCli( userId: number, key: string, value: string ): void {
	execSync(
		`npx wp-env run tests-cli wp user meta update ${ userId } "${ key }" "${ value }"`,
		{ stdio: 'pipe' },
	);
}

// ---------------------------------------------------------------------------
// Test suite
// ---------------------------------------------------------------------------

test.describe( 'My Account address view — yomigana deduplication', () => {
	let originalCheckoutPageId: string;
	let originalYomigana: string;
	let classicPageId: number;
	let blockPageId: number;

	test.beforeAll( async ( { request } ) => {
		// Save original settings so we can restore them after.
		originalCheckoutPageId = await getCheckoutPageId( request, BASE_URL );
		const settings = await getJp4wcSettings( request, BASE_URL );
		originalYomigana = ( settings.yomigana as string ) ?? '';

		await setJp4wcSettings( request, BASE_URL, { yomigana: '1' } );

		// Seed a complete billing + shipping address for admin (customer ID 1).
		// Without a full address, WC shows "You have not set up this type of address yet"
		// and render_address_fields is never invoked, making the duplicate test trivial.
		const baseAddress = {
			first_name: '太郎',
			last_name: '山田',
			address_1: 'どっかどっか１',
			city: '加古川市',
			state: 'JP28',
			postcode: '675-0001',
			country: 'JP',
		};
		await setCustomerAddress( request, BASE_URL, 1, 'billing', { ...baseAddress, email: 'test@example.com', phone: '090-1234-5678' } );
		await setCustomerAddress( request, BASE_URL, 1, 'shipping', baseAddress );

		// Seed yomigana usermeta via wp-cli (WC REST API does not expose these custom meta keys).
		for ( const type of [ 'billing', 'shipping' ] ) {
			setUserMetaCli( 1, `${ type }_yomigana_last_name`, 'やまだ' );
			setUserMetaCli( 1, `${ type }_yomigana_first_name`, 'たろう' );
		}

		const classicPage = await wpFetch( request, BASE_URL, 'pages', 'POST', {
			title: 'Classic Checkout (e2e view yomigana)',
			status: 'publish',
			content: CLASSIC_CHECKOUT_CONTENT,
		} ) as { id: number };
		classicPageId = classicPage.id;

		const blockPage = await wpFetch( request, BASE_URL, 'pages', 'POST', {
			title: 'Block Checkout (e2e view yomigana)',
			status: 'publish',
			content: BLOCK_CHECKOUT_CONTENT,
		} ) as { id: number };
		blockPageId = blockPage.id;
	} );

	test.afterAll( async ( { request } ) => {
		// Restore original settings.
		await setJp4wcSettings( request, BASE_URL, { yomigana: originalYomigana } );
		if ( originalCheckoutPageId ) {
			await setCheckoutPageId( request, BASE_URL, originalCheckoutPageId );
		}

		// Clean up test pages.
		for ( const id of [ classicPageId, blockPageId ] ) {
			if ( id ) {
				await wpFetch( request, BASE_URL, `pages/${ id }?force=true`, 'DELETE' );
			}
		}
	} );

	async function loginAsAdmin( page: import('@playwright/test').Page ): Promise<void> {
		await page.goto( `${ BASE_URL }/wp-login.php` );
		await page.fill( '#user_login', ADMIN_USER );
		await page.fill( '#user_pass', ADMIN_PASS );
		await page.click( '#wp-submit' );
		await page.waitForURL( /wp-admin/ );
	}

	// -----------------------------------------------------------------------
	// Classic checkout
	// -----------------------------------------------------------------------

	test( 'Classic: address view shows yomigana exactly once (no WC-API duplicate)', async ( { page, request } ) => {
		await setCheckoutPageId( request, BASE_URL, classicPageId );
		await loginAsAdmin( page );

		await page.goto( `${ BASE_URL }/my-account/?edit-address` );
		await page.waitForLoadState( 'networkidle' );

		await page.screenshot( { path: 'test-results/classic-view-page.png', fullPage: true } );

		// The formatted address must include yomigana (from JP format string).
		await expect( page.locator( '.woocommerce-address-fields, .woocommerce-MyAccount-content' ) ).toContainText( 'やまだ' );

		// WC's render_address_fields must NOT add a bare "Last Name ( Yomigana ): やまだ" line.
		const wcApiLabels = page.locator( 'text="Last Name ( Yomigana )"' );
		await expect( wcApiLabels ).toHaveCount( 0 );
	} );

	// -----------------------------------------------------------------------
	// Block checkout — yomigana stored in classic meta keys
	// -----------------------------------------------------------------------

	test( 'Block: address view shows yomigana exactly once (no WC-API duplicate)', async ( { page, request } ) => {
		await setCheckoutPageId( request, BASE_URL, blockPageId );
		await loginAsAdmin( page );

		await page.goto( `${ BASE_URL }/my-account/?edit-address` );
		await page.waitForLoadState( 'networkidle' );

		await page.screenshot( { path: 'test-results/block-view-page.png', fullPage: true } );

		await expect( page.locator( '.woocommerce-address-fields, .woocommerce-MyAccount-content' ) ).toContainText( 'やまだ' );

		const wcApiLabels = page.locator( 'text="Last Name ( Yomigana )"' );
		await expect( wcApiLabels ).toHaveCount( 0 );
	} );

	// -----------------------------------------------------------------------
	// Block checkout — yomigana stored only in WC Additional Fields API meta keys
	// -----------------------------------------------------------------------

	test( 'Block (WC API meta): address view shows yomigana from _wc_billing/jp4wc/ keys', async ( { page, request } ) => {
		await setCheckoutPageId( request, BASE_URL, blockPageId );

		// Replace classic meta keys with WC Additional Fields API meta keys.
		for ( const type of [ 'billing', 'shipping' ] ) {
			setUserMetaCli( 1, `${ type }_yomigana_last_name`, '' );
			setUserMetaCli( 1, `${ type }_yomigana_first_name`, '' );
			setUserMetaCli( 1, `_wc_${ type }/jp4wc/yomigana_last_name`, 'やまだ' );
			setUserMetaCli( 1, `_wc_${ type }/jp4wc/yomigana_first_name`, 'たろう' );
		}

		await loginAsAdmin( page );

		await page.goto( `${ BASE_URL }/my-account/?edit-address` );
		await page.waitForLoadState( 'networkidle' );

		await page.screenshot( { path: 'test-results/block-wc-api-meta-view-page.png', fullPage: true } );

		// Yomigana must appear via the JP format string fallback (from block checkout meta keys).
		await expect( page.locator( '.woocommerce-address-fields, .woocommerce-MyAccount-content' ) ).toContainText( 'やまだ' );

		// No duplicate bare label line from WC render_address_fields.
		const wcApiLabels = page.locator( 'text="Last Name ( Yomigana )"' );
		await expect( wcApiLabels ).toHaveCount( 0 );

		// Restore classic meta keys.
		for ( const type of [ 'billing', 'shipping' ] ) {
			setUserMetaCli( 1, `${ type }_yomigana_last_name`, 'やまだ' );
			setUserMetaCli( 1, `${ type }_yomigana_first_name`, 'たろう' );
			setUserMetaCli( 1, `_wc_${ type }/jp4wc/yomigana_last_name`, '' );
			setUserMetaCli( 1, `_wc_${ type }/jp4wc/yomigana_first_name`, '' );
		}
	} );
} );
