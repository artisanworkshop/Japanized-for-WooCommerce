# Nonces and CSRF Protection

## Form submissions (admin)

```php
// Output nonce field
wp_nonce_field( 'plugin_name_action', 'plugin_name_nonce' );

// Verify on submission
if ( ! isset( $_POST['plugin_name_nonce'] ) ||
     ! check_admin_referer( 'plugin_name_action', 'plugin_name_nonce' ) ) {
    wp_die( esc_html__( 'Security check failed.', 'plugin-name' ) );
}
```

## AJAX handlers

```php
// Enqueue nonce to JS
wp_localize_script( 'my-script', 'myData', [
    'nonce' => wp_create_nonce( 'my_ajax_action' ),
] );

// AJAX handler
add_action( 'wp_ajax_my_action', 'my_action_handler' );
function my_action_handler() {
    check_ajax_referer( 'my_ajax_action', 'nonce' );
    current_user_can( 'manage_woocommerce' ) || wp_send_json_error( null, 403 );

    // ... process ...
    wp_send_json_success( $data );
}
```

## REST API (cookie auth)

REST API automatically validates nonce from `X-WP-Nonce` header when using cookie auth.
Must still implement `permission_callback`:

```php
register_rest_route( 'plugin/v1', '/items', [
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => [ $this, 'create_item' ],
    'permission_callback' => function() {
        return current_user_can( 'manage_woocommerce' );
    },
] );
```

## WooCommerce checkout

```php
// Validate in woocommerce_checkout_process
add_action( 'woocommerce_checkout_process', function() {
    // Checkout has its own nonce — verify custom fields via isset checks
    if ( empty( $_POST['my_field'] ) ) {
        wc_add_notice( __( 'Field is required.', 'plugin-name' ), 'error' );
    }
} );
```

## Nonce lifetime

WordPress nonces are valid for 12 hours. For long-running admin pages:
- Use `wp_create_nonce()` fresh at page load via `wp_localize_script`
- Handle nonce expiry gracefully in JS (re-fetch or reload)
