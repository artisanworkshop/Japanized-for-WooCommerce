# WooCommerce REST API Extension

## Custom Endpoint Controller

```php
class My_Extension_REST_Controller extends WC_REST_Controller {
    protected $namespace = 'my-extension/v1';
    protected $rest_base = 'items';

    public function register_routes() {
        register_rest_route( $this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_items' ],
                'permission_callback' => [ $this, 'get_items_permissions_check' ],
                'args'                => $this->get_collection_params(),
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_item' ],
                'permission_callback' => [ $this, 'create_item_permissions_check' ],
                'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
            ],
        ] );

        register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_item' ],
                'permission_callback' => [ $this, 'get_item_permissions_check' ],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_item' ],
                'permission_callback' => [ $this, 'update_item_permissions_check' ],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'delete_item' ],
                'permission_callback' => [ $this, 'delete_item_permissions_check' ],
            ],
        ] );
    }

    public function get_items_permissions_check( $request ) {
        return current_user_can( 'manage_woocommerce' );
    }

    public function get_items( $request ) {
        $items = $this->get_data( $request->get_params() );
        $data = [];
        foreach ( $items as $item ) {
            $data[] = $this->prepare_item_for_response( $item, $request )->get_data();
        }
        return rest_ensure_response( $data );
    }

    public function get_item_schema() {
        return [
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'my-extension-item',
            'type'       => 'object',
            'properties' => [
                'id'     => [ 'type' => 'integer', 'readonly' => true ],
                'name'   => [ 'type' => 'string', 'required' => true ],
                'status' => [ 'type' => 'string', 'enum' => [ 'active', 'inactive' ] ],
            ],
        ];
    }
}

// Register
add_action( 'rest_api_init', function() {
    $controller = new My_Extension_REST_Controller();
    $controller->register_routes();
} );
```

## Extending WooCommerce Core Endpoints

### Add fields to products

```php
add_action( 'rest_api_init', function() {
    register_rest_field( 'product', 'my_custom_field', [
        'get_callback' => function( $product_arr ) {
            $product = wc_get_product( $product_arr['id'] );
            return $product ? $product->get_meta( '_my_custom_field', true ) : '';
        },
        'update_callback' => function( $value, $post ) {
            $product = wc_get_product( $post->ID );
            if ( $product ) {
                $product->update_meta_data( '_my_custom_field', sanitize_text_field( $value ) );
                $product->save();
            }
        },
        'schema' => [
            'type'        => 'string',
            'description' => 'My custom field',
            'context'     => [ 'view', 'edit' ],
        ],
    ] );
} );
```

### Add fields to orders

```php
register_rest_field( 'shop_order', 'my_order_field', [
    'get_callback' => function( $order_arr ) {
        $order = wc_get_order( $order_arr['id'] );
        return $order ? $order->get_meta( '_my_order_field', true ) : '';
    },
    'update_callback' => function( $value, $post ) {
        $order = wc_get_order( $post->ID );
        if ( $order ) {
            $order->update_meta_data( '_my_order_field', sanitize_text_field( $value ) );
            $order->save();
        }
    },
] );
```

### Pre/Post insert hooks

```php
// Before order is created via REST API
add_filter( 'woocommerce_rest_pre_insert_shop_order_object', function( $order, $request ) {
    if ( isset( $request['my_field'] ) ) {
        $order->update_meta_data( '_my_field', sanitize_text_field( $request['my_field'] ) );
    }
    return $order;
}, 10, 2 );
```

## Authentication

WooCommerce REST API supports:

1. **API Keys** (recommended for server-to-server):
   - Consumer Key + Consumer Secret
   - Generated in WooCommerce > Settings > Advanced > REST API
   - Use HTTP Basic Auth or query parameter auth

2. **OAuth 1.0a** (for HTTP connections):
   - Used when HTTPS is not available
   - Sends signature with each request

3. **Application Passwords** (WordPress 5.6+):
   - Standard WordPress authentication
   - Good for first-party integrations

```php
// Example: API key auth in requests
$response = wp_remote_get(
    'https://store.example.com/wp-json/wc/v3/orders',
    [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode( $consumer_key . ':' . $consumer_secret ),
        ],
    ]
);
```

## Store API (Block checkout)

The Store API (`wc/store/v1`) is used by block-based cart/checkout.
It has different authentication (nonce-based, frontend-only):

```php
// Extend Store API cart data (see blocks-integration.md)
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

woocommerce_store_api_register_endpoint_data( [
    'endpoint'        => CartSchema::IDENTIFIER,
    'namespace'       => 'my-extension',
    'data_callback'   => function() { return [ 'key' => 'value' ]; },
    'schema_callback' => function() {
        return [ 'key' => [ 'type' => 'string' ] ];
    },
] );
```

## WC 10.5+: Experimental REST API Caching

WooCommerce 10.5 introduced experimental REST API caching.
Extensions should handle cache invalidation if they modify data
that affects cached endpoints:

```php
// Invalidate WC REST cache when your extension modifies data
do_action( 'woocommerce_rest_api_cache_invalidated' );
```
