<?php
/**
 * Include file for all migrations.
 *
 * @package PeachPay
 */

if ( ! defined( 'PEACHPAY_ABSPATH' ) ) {
	exit;
}

// Load migration files here.
require_once PEACHPAY_ABSPATH . 'core/migrations/advanced-css.php';
require_once PEACHPAY_ABSPATH . 'core/migrations/migrate-general-settings.php';
require_once PEACHPAY_ABSPATH . 'core/migrations/migrate-button-settings.php';

// Execute migrations!
// Order should be oldest migrations to the newest because merchants may skip some versions and
// to avoid loss of plugin settings we want all intermediate migrations to still run in the
// correct order. Old migrations should never be touched because it may cause issues for newer
// migrations. To help indicate how old a migration is add a comment as `// Migrating {{PP_PREV_VERSION}} -> {{PP_NEW_VERSION}}`
// which can be filled in at release time.

// Migrating "1.65.1 -> 1.66.0".
peachpay_migrate_advanced_css();
// phpcs:ignore
// Migrating "1.66.0 -> 1.66.1"
// DO NOT delete this because it's the only code that sets the switches to on
// by default. Before removing this migration in the future, we'll need to create
// a new place were default option values are set.
peachpay_migrate_general_settings_option();
// phpcs:ignore
// Migrating {{<=1.67.1}} -> {{1.68.0}}
peachpay_migrate_button_settings_option();
