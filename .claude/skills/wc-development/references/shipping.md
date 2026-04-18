# Shipping Method Development

## Basic Shipping Method

```php
class WC_Shipping_My_Method extends WC_Shipping_Method {

    public function __construct( $instance_id = 0 ) {
        $this->id                 = 'my_shipping';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'My Shipping', 'my-wc-extension' );
        $this->method_description = __( 'Custom shipping method.', 'my-wc-extension' );
        $this->supports           = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        $this->init();
    }

    public function init() {
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option( 'title', $this->method_title );

        add_action(
            'woocommerce_update_options_shipping_' . $this->id,
            [ $this, 'process_admin_options' ]
        );
    }

    public function init_form_fields() {
        $this->instance_form_fields = [
            'title' => [
                'title'   => __( 'Method Title', 'my-wc-extension' ),
                'type'    => 'text',
                'default' => __( 'My Shipping', 'my-wc-extension' ),
            ],
            'cost' => [
                'title'       => __( 'Cost', 'my-wc-extension' ),
                'type'        => 'price',
                'default'     => '0',
                'description' => __( 'Enter shipping cost.', 'my-wc-extension' ),
            ],
            'free_min_amount' => [
                'title'       => __( 'Free shipping minimum', 'my-wc-extension' ),
                'type'        => 'price',
                'default'     => '',
                'description' => __( 'Minimum order amount for free shipping. Leave empty to disable.', 'my-wc-extension' ),
            ],
        ];
    }

    public function calculate_shipping( $package = [] ) {
        $cost = $this->get_option( 'cost', 0 );
        $free_min = $this->get_option( 'free_min_amount', '' );

        // Free shipping threshold
        if ( ! empty( $free_min ) && $package['contents_cost'] >= floatval( $free_min ) ) {
            $cost = 0;
        }

        $this->add_rate( [
            'id'      => $this->get_rate_id(),
            'label'   => $this->title,
            'cost'    => $cost,
            'package' => $package,
        ] );
    }
}
```

## Registration

```php
add_filter( 'woocommerce_shipping_methods', function( $methods ) {
    $methods['my_shipping'] = 'WC_Shipping_My_Method';
    return $methods;
} );
```

## Multiple Rates

```php
public function calculate_shipping( $package = [] ) {
    // Standard rate
    $this->add_rate( [
        'id'    => $this->get_rate_id( 'standard' ),
        'label' => __( 'Standard Shipping', 'my-wc-extension' ),
        'cost'  => 500,
    ] );

    // Express rate
    $this->add_rate( [
        'id'    => $this->get_rate_id( 'express' ),
        'label' => __( 'Express Shipping', 'my-wc-extension' ),
        'cost'  => 1200,
    ] );
}
```

## Weight/Dimension-based Calculation

```php
public function calculate_shipping( $package = [] ) {
    $total_weight = 0;
    foreach ( $package['contents'] as $item ) {
        $product = $item['data'];
        $total_weight += floatval( $product->get_weight() ) * $item['quantity'];
    }

    $cost = $this->calculate_cost_by_weight( $total_weight );

    $this->add_rate( [
        'id'    => $this->get_rate_id(),
        'label' => $this->title,
        'cost'  => $cost,
    ] );
}
```

## Tax-Inclusive Shipping (WC 10.6+)

New `woocommerce_shipping_prices_include_tax` filter for EU/DE compliance:

```php
// Allow tax-inclusive shipping costs (required by some EU countries)
add_filter( 'woocommerce_shipping_prices_include_tax', function( $include_tax ) {
    // Return true to treat shipping costs as gross (tax-inclusive)
    return true;
} );
```

Previously, WooCommerce always treated shipping as net prices with tax added.
This filter allows fixed gross shipping prices required by consumer protection laws
in Germany, Switzerland, and other EU countries.

## Shipping Package Name (WC 10.6+)

```php
// Customize shipping package name
// WC 10.6: get_shipping_package_name() now receives total package count
// Single package: "Shipment" (not "Shipment 1")
// Multiple: "Shipment 1", "Shipment 2"
add_filter( 'woocommerce_shipping_package_name', function( $name, $i, $package ) {
    return sprintf( __( 'Delivery %d', 'my-wc-extension' ), ( $i + 1 ) );
}, 10, 3 );
```

## External API Rate Calculation

```php
public function calculate_shipping( $package = [] ) {
    $destination = $package['destination'];
    $cache_key   = 'my_shipping_' . md5( wp_json_encode( $destination ) . $package['contents_cost'] );
    $cached      = get_transient( $cache_key );

    if ( false !== $cached ) {
        foreach ( $cached as $rate ) {
            $this->add_rate( $rate );
        }
        return;
    }

    // Call external API
    $response = wp_remote_post( 'https://api.shipping-provider.com/rates', [
        'body' => wp_json_encode( [
            'origin'      => get_option( 'woocommerce_store_postcode' ),
            'destination'  => $destination['postcode'],
            'country'      => $destination['country'],
            'weight'       => $this->get_package_weight( $package ),
        ] ),
        'headers' => [ 'Content-Type' => 'application/json' ],
        'timeout' => 10,
    ] );

    if ( is_wp_error( $response ) ) {
        return; // Silently fail — don't show this method
    }

    $rates = json_decode( wp_remote_retrieve_body( $response ), true );
    $wc_rates = [];

    foreach ( $rates as $rate ) {
        $wc_rate = [
            'id'        => $this->get_rate_id( $rate['service_id'] ),
            'label'     => $rate['service_name'],
            'cost'      => $rate['price'],
            'meta_data' => [ 'delivery_days' => $rate['delivery_days'] ],
        ];
        $this->add_rate( $wc_rate );
        $wc_rates[] = $wc_rate;
    }

    set_transient( $cache_key, $wc_rates, HOUR_IN_SECONDS );
}
```
