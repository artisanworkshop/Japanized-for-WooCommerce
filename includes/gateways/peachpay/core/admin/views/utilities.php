<?php
/**
 * Helper functions for rendering parts of the view.
 *
 * @package PeachPay
 */

defined( 'PEACHPAY_ABSPATH' ) || exit;

if ( function_exists( 'peachpay_generate_top_nav_link' ) ) {
	// Since the view templates can be included multiple times, if one of these
	// functions has already been defined, then we return to avoid an error.
	return;
}

/**
 * Returns the current top navigation tab that should be highlighted.
 */
function peachpay_get_current_nav_top_tab() {
	if ( peachpay_nav_is_analytics_page() ) {
		return 'analytics';
	}
	if ( peachpay_nav_is_gateway_page() ) {
		return 'settings';
	}
	if ( ! peachpay_nav_is_peachpay_page() ) {
		return '';
	}
	// PHPCS:ignore
	if ( ! isset( $_GET['tab'] ) || isset( $_GET['tab'] ) && 'home' === $_GET['tab'] ) {
		return 'dashboard';
	}
	return 'settings';
}

/**
 * Returns the key of the tab that should be active in the navigation.
 */
function peachpay_get_current_nav_tab() {
	if ( peachpay_nav_is_peachpay_page() ) {
		// PHPCS:ignore
		return isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'home';
	}
	if ( peachpay_nav_is_analytics_page() ) {
		// PHPCS:ignore
		return isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'payment_methods';
	}
	if ( peachpay_nav_is_gateway_page() ) {
		// PHPCS:ignore
		return '';
	}
	return '';
}

/**
 * Returns the current section in the tab if applicable.
 */
function peachpay_get_current_nav_sub_tab() {
	$current_tab = peachpay_get_current_nav_tab();
	if ( 'field' === $current_tab ) {
		// PHPCS:ignore
		return isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'billing';
	}
	if ( 'express_checkout' === $current_tab ) {
		// PHPCS:ignore
		return isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'branding';
	}
	return '';
}

/**
 * Returns the current hash value.
 */
function peachpay_get_current_hashed_nav() {
	if ( ! isset( $_COOKIE['pp_sub_nav_payment'] ) ) {
		return '';
	}
	$prefix = 'pp-sub-nav-';
	// PHPCS:ignore
	$hash = substr( $_COOKIE['pp_sub_nav_payment'], strlen( $prefix ) );
	return $hash;
}

/**
 * Returns true if this is a PeachPay settings page (but not a gateway page).
 */
function peachpay_nav_is_peachpay_page() {
	// PHPCS:ignore
	return isset( $_GET['page'] ) && ( 'peachpay' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) );
}

/**
 * Returns true if this is the PeachPay analytics page.
 */
function peachpay_nav_is_analytics_page() {
	// PHPCS:ignore
	return isset( $_GET['page'] ) && ( 'peachpay' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) && isset( $_GET['section'] ) && ( 'analytics' === sanitize_text_field( wp_unslash( $_GET['section'] ) ) );
}

/**
 * Returns true if this is a PeachPay gateway page.
 */
function peachpay_nav_is_gateway_page() {
	// PHPCS:ignore
	return isset( $_GET['page'] ) && ( 'wc-settings' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) && isset( $_GET['section'] ) && ( 'peachpay' === substr( sanitize_text_field( wp_unslash( $_GET['section'] ) ), 0, 8 ) );
}

/**
 * Returns true if the feature in this tab is active.
 *
 * @param string $tab The tab key.
 */
function peachpay_nav_feature_is_active( $tab ) {
	if ( peachpay_should_mark_premium( $tab ) ) {
		return false;
	}
	switch ( $tab ) {
		case 'payment':
			return ! peachpay_is_test_mode();
		case 'currency':
			return peachpay_get_settings_option( 'peachpay_currency_options', 'enabled' );
		case 'field':
			return true;
		case 'related_products':
			return peachpay_get_settings_option( 'peachpay_related_products_options', 'peachpay_related_enable' );
		case 'express_checkout':
			return peachpay_express_checkout_enabled();
		default:
			return false;
	}
}

/**
 * If the given tab has sub-tabs, generates the sub-tabs for the side navigation.
 *
 * @param string $tab The tab key.
 */
function peachpay_get_subtabs( $tab ) {
	$tabs_with_subtabs           = array(
		'field'            => array(
			'billing'    => 'Billing',
			'shipping'   => 'Shipping',
			'additional' => 'Additional',
		),
		'express_checkout' => array(
			'branding'                => 'Branding',
			'button'                  => 'Checkout button',
			'window'                  => 'Checkout window',
			'product_recommendations' => 'Product recommendations',
			'advanced'                => 'Advanced',
		),
	);
	$tabs_with_hashed_navigation = array(
		'payment' => array(
			'stripe'    => 'Stripe',
			'square'    => 'Square',
			'paypal'    => 'PayPal',
			'poynt'     => 'GoDaddy Poynt',
			'authnet'   => 'Authorize.net',
			'amazonpay' => 'Amazon Pay',
			'peachpay'  => 'Purchase order',
		),
	);

	$sub_tabs_exist           = array_key_exists( $tab, $tabs_with_subtabs ) && is_array( $tabs_with_subtabs[ $tab ] );
	$hashed_navigation_exists = array_key_exists( $tab, $tabs_with_hashed_navigation ) && is_array( $tabs_with_hashed_navigation[ $tab ] );

	if ( ! $sub_tabs_exist && ! $hashed_navigation_exists ) {
		return;
	}
	?>
	<div class="nav-sub-tabs accordion-content">
		<?php
		if ( $sub_tabs_exist ) {
			foreach ( $tabs_with_subtabs[ $tab ] as $subtab => $title ) {
				peachpay_generate_subtab( $tab, $subtab, '', $title );
			}
		} elseif ( $hashed_navigation_exists ) {
			foreach ( $tabs_with_hashed_navigation[ $tab ] as $hash => $title ) {
				peachpay_generate_subtab( $tab, '', $hash, $title );
			}
		}
		?>
	</div>
	<?php
}

/**
 * Generates a sub-tab.
 *
 * @param string $tab    The tab key.
 * @param string $subtab The sub-tab key.
 * @param string $hash   The hash value.
 * @param string $title  the sub-tab title.
 */
function peachpay_generate_subtab( $tab, $subtab, $hash, $title ) {
	if ( '' === $subtab ) {
		?>
		<div class="nav-sub-tab <?php echo esc_attr( ( peachpay_get_current_hashed_nav() === $hash ) ? 'current' : '' ); ?>" tabindex="0" data-hash="<?php echo esc_attr( trim( wp_json_encode( $hash ), '"' ) ); ?>" data-tab="<?php echo esc_attr( trim( wp_json_encode( $tab ), '"' ) ); ?>">
			<?php peachpay_generate_nav_tab_title( $title ); ?>
		</div>
		<?php
	} else {
		?>
		<a class="nav-sub-tab <?php echo esc_attr( ( peachpay_get_current_nav_sub_tab() === $subtab ) ? 'current' : '' ); ?>" href="<?php echo esc_url( PeachPay_Admin::admin_settings_url( 'peachpay', $tab, $subtab, '', false ) ); ?>">
			<?php peachpay_generate_nav_tab_title( $title ); ?>
		</a>
		<?php
	}
}

/**
 * Generates a styled link on the top right of PeachPay settings header.
 *
 * @param string $key    A unique identifier for the link.
 * @param string $link   The url.
 * @param string $icon   The file name of the icon.
 * @param string $title  The text to display on the link.
 */
function peachpay_generate_top_nav_link( $key, $link, $icon, $title ) {
	?>
	<a class="top-nav-link <?php echo esc_attr( ( peachpay_get_current_nav_top_tab() === $key ) ? 'current' : '' ); ?> <?php echo esc_attr( $icon ); ?>-link" href="<?php echo esc_url( $link ); ?>"<?php echo ( ( 'Docs' === $title || 'Twitter' === $title ) ? ' target="_blank"' : '' ); ?>>
		<div class="icon <?php echo esc_attr( $icon ); ?>"></div>
		<?php peachpay_generate_nav_tab_title( $title ); ?>
		<?php if ( null !== $key ) { ?>
			<div class="icon chevron-down"></div>
		<?php } ?>
	</a>
	<?php
}

/**
 * Renders the dropdown for the top navigation.
 */
function peachpay_top_nav_dropdown() {
	?>
	<div class="dropdown accordion-content">
		<a class="<?php echo esc_attr( 'dashboard' === peachpay_get_current_nav_top_tab() ? 'current' : '' ); ?>" href="<?php echo esc_url( PeachPay_Admin::admin_settings_url( 'peachpay', 'home', '', '', false ) ); ?>">
			<div class="icon dashboard-icon"></div>
			<?php peachpay_generate_nav_tab_title( 'Dashboard' ); ?>
		</a>
		<a class="<?php echo esc_attr( 'settings' === peachpay_get_current_nav_top_tab() ? 'current' : '' ); ?>" href="<?php echo esc_url( PeachPay_Admin::admin_settings_url( 'peachpay', 'payment', '', '', false ) ); ?>">
			<div class="icon settings-icon"></div>
			<?php peachpay_generate_nav_tab_title( 'Settings' ); ?>
		</a>
		<a class="<?php echo esc_attr( 'analytics' === peachpay_get_current_nav_top_tab() ? 'current' : '' ); ?>" href="<?php echo esc_url( PeachPay_Admin::admin_settings_url( 'peachpay', 'payment_methods', 'analytics', '', false ) ); ?>">
			<div class="icon analytics-icon"></div>
			<?php peachpay_generate_nav_tab_title( 'Analytics' ); ?>
		</a>
	</div>
	<?php
}

/**
 * Generates a single navigation tab for the given tab and section.
 *
 * @param string $page        The page key.
 * @param string $tab         The tab key.
 * @param string $section     The section key.
 * @param string $title       The text to display on the tab.
 * @param string $has_subtabs If true, chevron down will be rendered.
 */
function peachpay_generate_nav_tab( $page, $tab, $section, $title, $has_subtabs = false ) {
	if ( $has_subtabs ) {
		?>
		<div class="tab-with-subtabs-container <?php echo esc_attr( peachpay_should_mark_premium( $tab ) ? 'pp-popup-mousemove-trigger' : '' ); ?>">
			<div class="nav-tab has-subtabs accordion-tab <?php echo esc_attr( ( peachpay_get_current_nav_tab() === $tab ) ? 'current expanded' : '' ); ?>">
				<div class="title">
					<div class="icon <?php echo esc_attr( str_replace( '_', '-', $tab ) ); ?>-icon">
					</div>
					<?php if ( peachpay_nav_feature_is_active( $tab ) ) { ?>
						<div class="active-status"></div>
					<?php } ?>
					<div class="flex-row"><?php peachpay_generate_nav_tab_title( $title ); ?></div>
					<?php peachpay_premium_crown( $tab ); ?>
				</div>
				<?php if ( $has_subtabs ) { ?>
					<div class="icon chevron-down"></div>
				<?php } ?>
				<?php if ( peachpay_should_mark_premium( $tab ) ) { ?>
					<div class="pp-popup pp-popup-right pp-tooltip-popup"> <?php esc_html_e( 'Premium Feature', 'peachpay-for-woocommerce' ); ?> </div>
				<?php } ?>
			</div>
			<?php peachpay_get_subtabs( $tab ); ?>
		</div>
	<?php } else { ?>
		<a class="nav-tab <?php echo esc_attr( ( peachpay_get_current_nav_tab() === $tab ) ? 'current' : '' ); ?> <?php echo esc_attr( peachpay_should_mark_premium( $tab ) ? 'pp-popup-mousemove-trigger' : '' ); ?>" href="<?php esc_url( PeachPay_Admin::admin_settings_url( $page, $tab, $section ) ); ?>">
			<div class="title">
				<div class="icon <?php echo esc_attr( str_replace( '_', '-', $tab ) ); ?>-icon">
				</div>
				<?php if ( peachpay_nav_feature_is_active( $tab ) ) { ?>
					<div class="active-status"></div>
				<?php } ?>
				<div class="flex-row"><?php peachpay_generate_nav_tab_title( $title ); ?></div>
				<?php peachpay_premium_crown( $tab ); ?>
			</div>
			<?php if ( peachpay_should_mark_premium( $tab ) ) { ?>
				<div class="pp-popup pp-popup-right pp-tooltip-popup"> <?php esc_html_e( 'Premium Feature', 'peachpay-for-woocommerce' ); ?> </div>
			<?php } ?>
		</a>
		<?php
	}
}

/**
 * Generates escaped and translated text for the navigation tab title.
 * This is a workaround for esc_html_e only accepting string literals.
 *
 * @param string $title The text to display on the tab.
 */
function peachpay_generate_nav_tab_title( $title ) {
	switch ( $title ) {
		case 'Home':
			echo esc_html_e( 'Home', 'peachpay-for-woocommerce' );
			break;
		case 'Payments':
			echo esc_html_e( 'Payments', 'peachpay-for-woocommerce' );
			break;
		case 'Currency':
			echo esc_html_e( 'Currency', 'peachpay-for-woocommerce' );
			break;
		case 'Field editor':
			echo esc_html_e( 'Field editor', 'peachpay-for-woocommerce' );
			break;
		case 'Related products':
			echo esc_html_e( 'Related products', 'peachpay-for-woocommerce' );
			break;
		case 'Express checkout':
			echo esc_html_e( 'Express checkout', 'peachpay-for-woocommerce' );
			break;
		case 'Billing':
			echo esc_html_e( 'Billing', 'peachpay-for-woocommerce' );
			break;
		case 'Shipping':
			echo esc_html_e( 'Shipping', 'peachpay-for-woocommerce' );
			break;
		case 'Additional':
			echo esc_html_e( 'Additional', 'peachpay-for-woocommerce' );
			break;
		case 'Branding':
			echo esc_html_e( 'Branding', 'peachpay-for-woocommerce' );
			break;
		case 'Checkout button':
			echo esc_html_e( 'Checkout button', 'peachpay-for-woocommerce' );
			break;
		case 'Checkout window':
			echo esc_html_e( 'Checkout window', 'peachpay-for-woocommerce' );
			break;
		case 'Product recommendations':
			echo esc_html_e( 'Product recommendations', 'peachpay-for-woocommerce' );
			break;
		case 'Advanced':
			echo esc_html_e( 'Advanced', 'peachpay-for-woocommerce' );
			break;
		case 'Docs':
			echo esc_html_e( 'Docs', 'peachpay-for-woocommerce' );
			break;
		case 'Support':
			echo esc_html_e( 'Support', 'peachpay-for-woocommerce' );
			break;
		case 'Analytics':
			echo esc_html_e( 'Analytics', 'peachpay-for-woocommerce' );
			break;
		case 'Billing':
			echo esc_html_e( 'Billing', 'peachpay-for-woocommerce' );
			break;
		case 'Shipping':
			echo esc_html_e( 'Shipping', 'peachpay-for-woocommerce' );
			break;
		case 'Additional':
			echo esc_html_e( 'Additional', 'peachpay-for-woocommerce' );
			break;
		case 'Branding':
			echo esc_html_e( 'Branding', 'peachpay-for-woocommerce' );
			break;
		case 'Checkout button':
			echo esc_html_e( 'Checkout button', 'peachpay-for-woocommerce' );
			break;
		case 'Checkout window':
			echo esc_html_e( 'Checkout window', 'peachpay-for-woocommerce' );
			break;
		case 'Product recommendations':
			echo esc_html_e( 'Product recommendations', 'peachpay-for-woocommerce' );
			break;
		case 'Advanced':
			echo esc_html_e( 'Advanced', 'peachpay-for-woocommerce' );
			break;
		case 'Settings':
			echo esc_html_e( 'Settings', 'peachpay-for-woocommerce' );
			break;
		case 'Dashboard':
			echo esc_html_e( 'Dashboard', 'peachpay-for-woocommerce' );
			break;
		case 'Twitter':
			echo esc_html_e( 'Twitter', 'peachpay-for-woocommerce' );
			break;
		case 'Payment methods':
			echo esc_html_e( 'Payment methods', 'peachpay-for-woocommerce' );
			break;
		case 'Device breakdown':
			echo esc_html_e( 'Device breakdown', 'peachpay-for-woocommerce' );
			break;
		case 'Abandoned carts':
			echo esc_html_e( 'Abandoned carts', 'peachpay-for-woocommerce' );
			break;
		case 'Stripe':
			echo esc_html_e( 'Stripe', 'peachpay-for-woocommerce' );
			break;
		case 'Square':
			echo esc_html_e( 'Square', 'peachpay-for-woocommerce' );
			break;
		case 'PayPal':
			echo esc_html_e( 'PayPal', 'peachpay-for-woocommerce' );
			break;
		case 'GoDaddy Poynt':
			echo esc_html_e( 'GoDaddy Poynt', 'peachpay-for-woocommerce' );
			break;
		case 'Authorize.net':
			echo esc_html_e( 'Authorize.net', 'peachpay-for-woocommerce' );
			break;
		case 'Amazon Pay':
			echo esc_html_e( 'Amazon Pay', 'peachpay-for-woocommerce' );
			break;
		case 'Purchase order':
			echo esc_html_e( 'Purchase Order', 'peachpay-for-woocommerce' );
			break;
	}
}

/**
 * Returns true if the feature located at the given tab needs to be marked as premium.
 *
 * @param string $tab The navigation tab key.
 */
function peachpay_should_mark_premium( $tab ) {
	return ! peachpay_premium_status() && in_array(
		$tab,
		array(
			'currency',
			'field',
			'related_products',
			'express_checkout',
		),
		true
	);
}

/**
 * Returns number of days left of premium trial; if no trial, returns empty string.
 */
function peachpay_premium_days_left() {
	$peachpay_premium_config = peachpay_plugin_get_capability_config( 'woocommerce_premium', array( 'woocommerce_premium' => get_option( 'peachpay_premium_capability' ) ) );
	$is_trial                = isset( $peachpay_premium_config['trialEnd'] );
	if ( $is_trial ) {
		$now       = new DateTime();
		$trial_end = ( new DateTime() )->setTimestamp( $peachpay_premium_config['trialEnd'] );
		$days_left = $now->diff( $trial_end );
	} else {
		$days_left = '';
	}
	return $days_left;
}

/**
 * Returns 'bypass' if applicable; else, returns boolean indicating whether or not the merchant has PeachPay premium.
 */
function peachpay_premium_status() {
	$peachpay_has_premium_capability = peachpay_plugin_has_capability( 'woocommerce_premium', array( 'woocommerce_premium' => get_option( 'peachpay_premium_capability' ) ) );
	$peachpay_premium_config         = peachpay_plugin_get_capability_config( 'woocommerce_premium', array( 'woocommerce_premium' => get_option( 'peachpay_premium_capability' ) ) );

	if ( isset( $peachpay_premium_config['bypass'] ) ) {
		return 'bypass';
	}

	return $peachpay_has_premium_capability || isset( $peachpay_premium_config['paused'] );
}

/**
 * Renders the peachpay premium misc link code.
 */
function peachpay_premium_misc_link() {
	//phpcs:ignore
	if ( ! isset( $_GET['page'] ) && strpos( $_GET['page'], 'peachpay' ) ) {
		return;
	}

	$premium_status = peachpay_premium_status();
	if ( 'bypass' === $premium_status ) {
		return;
	}

	if ( $premium_status ) {
		?>
			<button
				type='submit'
				form='peachpay-premium-subscription-portal-form'
				class='button-to-anchor top-nav-link <?php echo esc_attr( 'crown-icon' ); ?>-link'
			>
				<div class="icon <?php echo esc_attr( 'crown-icon' ); ?>"></div>
				<?php
				$days_left = peachpay_premium_days_left();
				if ( '' !== $days_left ) {
					// translators: %d: days left in trial
					echo esc_html( sprintf( __( 'Premium Trial (%d days left)', 'peachpay-for-woocommerce' ), $days_left->days + 1 ) );
				} else {
					echo esc_html_e( 'Premium Portal', 'peachpay-for-woocommerce' );
				}
				?>
			</button>
		<?php
		require_once PeachPay::get_plugin_path() . '/core/admin/views/html-premium-portal.php';
	} else {
		$peachpay_premium_config = peachpay_plugin_get_capability_config( 'woocommerce_premium', array( 'woocommerce_premium' => get_option( 'peachpay_premium_capability' ) ) );
		?>
			<button class="pp-button-continue-premium button-to-anchor top-nav-link <?php echo esc_attr( 'crown-icon' ); ?>-link'">
				<div class="icon <?php echo esc_attr( 'crown-icon' ); ?>"></div>
				<?php echo isset( $peachpay_premium_config['canceled'] ) ? esc_html_e( 'Upgrade', 'peachpay-for-woocommerce' ) : esc_html_e( 'Try Premium', 'peachpay-for-woocommerce' ); ?>
			</button>
		<?php
		require_once PeachPay::get_plugin_path() . 'core/admin/views/html-premium-modal.php';
	}
}

/**
 * Renders the premium crown if the feature located at the given tab needs to be marked as premium.
 *
 * @param string $tab The navigation tab key.
 */
function peachpay_premium_crown( $tab ) {
	if ( peachpay_should_mark_premium( $tab ) ) {
		?>
		<div class="icon crown-icon"></div>
		<?php
	}
}
