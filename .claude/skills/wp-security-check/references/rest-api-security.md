# REST API Security

## permission_callback is mandatory

Every REST route MUST have a `permission_callback`. Without it WordPress throws `_doing_it_wrong`.

```php
// NG — no permission check
register_rest_route( 'plugin/v1', '/items', [
    'methods'  => WP_REST_Server::READABLE,
    'callback' => [ $this, 'get_items' ],
    // Missing permission_callback!
] );

// OK — public read
register_rest_route( 'plugin/v1', '/items', [
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => [ $this, 'get_items' ],
    'permission_callback' => '__return_true',
] );

// OK — authenticated write
register_rest_route( 'plugin/v1', '/items', [
    'methods'             => WP_REST_Server::CREATABLE,
    'callback'            => [ $this, 'create_item' ],
    'permission_callback' => function() {
        return current_user_can( 'manage_woocommerce' );
    },
] );
```

## Never use __return_true for write operations

- `GET` (read-only, non-sensitive data): `__return_true` is acceptable
- `POST`, `PUT`, `PATCH`, `DELETE`: always require authentication + capability

## Input validation in REST args

```php
'args' => [
    'status' => [
        'type'              => 'string',
        'enum'              => [ 'active', 'inactive' ],
        'sanitize_callback' => 'sanitize_key',
        'validate_callback' => 'rest_validate_request_arg',
    ],
    'limit' => [
        'type'              => 'integer',
        'minimum'           => 1,
        'maximum'           => 100,
        'default'           => 10,
        'sanitize_callback' => 'absint',
    ],
],
```

## Authentication patterns

```php
// Cookie auth (wp-admin, Gutenberg) — nonce in X-WP-Nonce header
// Handled automatically by WP REST API

// Application passwords (external clients)
// User installs an app password; basic auth with encoded credentials

// Do NOT create custom auth schemes unless absolutely required
```

## Response security

```php
// Return WP_Error for failures (not raw false/null)
if ( ! $item ) {
    return new WP_Error(
        'rest_not_found',
        __( 'Item not found.', 'plugin-name' ),
        [ 'status' => 404 ]
    );
}

// Never expose internal error messages to unauthenticated users
// Use generic messages for 403/401 responses
```
