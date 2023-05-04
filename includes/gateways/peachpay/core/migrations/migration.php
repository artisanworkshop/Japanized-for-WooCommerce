<?php
/**
 * Include file for all migrations.
 *
 *  @PHPCS:disable Squiz.PHP.CommentedOutCode.Found
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

// Load migration files here.
require_once PEACHPAY_ABSPATH . 'core/migrations/migrate-general-settings.php';
require_once PEACHPAY_ABSPATH . 'core/migrations/migrate-button-settings.php';
require_once PEACHPAY_ABSPATH . 'core/migrations/migrate-related-product-settings.php';
require_once PEACHPAY_ABSPATH . 'core/migrations/migrate-payment-method-thresholds.php';
require_once PEACHPAY_ABSPATH . 'core/migrations/migrate-square.php';
require_once PEACHPAY_ABSPATH . 'core/migrations/migrate-stripe.php';
require_once PEACHPAY_ABSPATH . 'core/migrations/migrate-paypal.php';
require_once PEACHPAY_ABSPATH . 'core/migrations/migrate-settings-after-reorg.php';

// Execute migrations!
// Order should be oldest migrations to the newest because merchants may skip some versions and
// to avoid loss of plugin settings we want all intermediate migrations to still run in the
// correct order. Old migrations should never be touched because it may cause issues for newer
// migrations. To help indicate how old a migration is add a comment as `// Migrating {{PP_PREV_VERSION}} -> {{PP_NEW_VERSION}}`
// which can be filled in at release time.

// Migrating "1.66.0 -> 1.66.1"
// DO NOT delete this because it's the only code that sets the switches to on
// by default. Before removing this migration in the future, we'll need to create
// a new place were default option values are set.
peachpay_migrate_general_settings_option();
// Migrating <=1.67.1 -> 1.68.0
peachpay_migrate_button_settings_option();
// Migrating <=1.68.1 -> 1.69.0
peachpay_migrate_related_products_settings_option();
// Migrating <=1.75.1 -> 1.76.0
peachpay_migrate_linked_and_related_products_settings_option();
// Migrating <=1.79.0 -> 1.80.0
peachpay_migrate_payment_method_thresholds();
// Migrating <=1.81.0 -> 1.81.0
peachpay_migrate_square();
// Migrating <=1.83.0 -> 1.84.0
peachpay_migrate_stripe();
// Migrating <=1.84.1 -> 1.85.0
peachpay_migrate_express_checkout();
// Migrating <=1.85.0 -> 1.86.0
peachpay_migrate_paypal();
