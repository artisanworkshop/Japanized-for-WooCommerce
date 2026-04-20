# Product Data and Types

## Product Class Hierarchy

```
WC_Product (abstract)
├── WC_Product_Simple
├── WC_Product_Variable
│   └── WC_Product_Variation
├── WC_Product_Grouped
├── WC_Product_External
└── Your_Custom_Product_Type (extends WC_Product)
```

## Working with Products

```php
$product = wc_get_product( $product_id ); // Returns correct subclass

// Core data
$product->get_id();
$product->get_name();
$product->get_type();              // 'simple', 'variable', etc.
$product->get_status();            // 'publish', 'draft', etc.
$product->get_sku();
$product->get_price();             // Active price (sale or regular)
$product->get_regular_price();
$product->get_sale_price();
$product->is_on_sale();
$product->get_stock_quantity();
$product->is_in_stock();
$product->get_stock_status();      // 'instock', 'outofstock', 'onbackorder'
$product->get_weight();
$product->get_dimensions();
$product->get_category_ids();
$product->get_tag_ids();
$product->get_image_id();
$product->get_gallery_image_ids();

// Meta
$product->get_meta( '_my_custom_field', true );
$product->update_meta_data( '_my_custom_field', $value );
$product->save();
```

## Variable Products & Variations

```php
if ( $product->is_type( 'variable' ) ) {
    $variations = $product->get_available_variations();
    $attributes = $product->get_variation_attributes();
    $children   = $product->get_children(); // Variation IDs

    foreach ( $children as $var_id ) {
        $variation = wc_get_product( $var_id );
        $variation->get_attributes(); // ['color' => 'red', 'size' => 'large']
        $variation->get_price();
        $variation->get_sku();
    }
}
```

## Custom Product Fields (Simple Product)

```php
// Add fields to product editor — General tab
add_action( 'woocommerce_product_options_general_product_data', function() {
    woocommerce_wp_text_input( [
        'id'          => '_my_custom_field',
        'label'       => __( 'Custom Field', 'my-wc-extension' ),
        'description' => __( 'Enter a custom value.', 'my-wc-extension' ),
        'desc_tip'    => true,
        'type'        => 'text',
    ] );

    woocommerce_wp_select( [
        'id'      => '_my_select_field',
        'label'   => __( 'Select Option', 'my-wc-extension' ),
        'options' => [
            ''       => __( 'Choose...', 'my-wc-extension' ),
            'opt_a'  => __( 'Option A', 'my-wc-extension' ),
            'opt_b'  => __( 'Option B', 'my-wc-extension' ),
        ],
    ] );

    woocommerce_wp_checkbox( [
        'id'    => '_my_checkbox',
        'label' => __( 'Enable Feature', 'my-wc-extension' ),
    ] );
});

// Save custom fields
add_action( 'woocommerce_process_product_meta', function( $post_id ) {
    $product = wc_get_product( $post_id );

    if ( isset( $_POST['_my_custom_field'] ) ) {
        $product->update_meta_data(
            '_my_custom_field',
            sanitize_text_field( wp_unslash( $_POST['_my_custom_field'] ) )
        );
    }

    $product->update_meta_data(
        '_my_checkbox',
        isset( $_POST['_my_checkbox'] ) ? 'yes' : 'no'
    );

    $product->save();
} );
```

## Product Tabs

```php
// Add tab to product data panel
add_filter( 'woocommerce_product_data_tabs', function( $tabs ) {
    $tabs['my_extension'] = [
        'label'    => __( 'My Extension', 'my-wc-extension' ),
        'target'   => 'my_extension_product_data',
        'class'    => [ 'show_if_simple', 'show_if_variable' ],
        'priority' => 70,
    ];
    return $tabs;
} );

// Tab content
add_action( 'woocommerce_product_data_panels', function() {
    echo '<div id="my_extension_product_data" class="panel woocommerce_options_panel">';
    woocommerce_wp_text_input( [
        'id'    => '_my_tab_field',
        'label' => __( 'Tab Field', 'my-wc-extension' ),
    ] );
    echo '</div>';
} );
```

## Variation Fields

```php
// Add field to each variation
add_action( 'woocommerce_variation_options_pricing', function( $loop, $variation_data, $variation ) {
    woocommerce_wp_text_input( [
        'id'    => "_my_var_field_{$loop}",
        'name'  => "my_var_field[{$loop}]",
        'label' => __( 'Custom Var Field', 'my-wc-extension' ),
        'value' => get_post_meta( $variation->ID, '_my_var_field', true ),
    ] );
}, 10, 3 );

// Save variation field
add_action( 'woocommerce_save_product_variation', function( $variation_id, $loop ) {
    $variation = wc_get_product( $variation_id );

    if ( isset( $_POST['my_var_field'][ $loop ] ) ) {
        $variation->update_meta_data(
            '_my_var_field',
            sanitize_text_field( wp_unslash( $_POST['my_var_field'][ $loop ] ) )
        );
        $variation->save();
    }
}, 10, 2 );
```

## Custom Product Type

```php
// Register product type
class WC_Product_My_Type extends WC_Product {
    public function get_type() {
        return 'my_type';
    }
}

add_filter( 'woocommerce_product_class', function( $classname, $product_type ) {
    if ( 'my_type' === $product_type ) {
        return 'WC_Product_My_Type';
    }
    return $classname;
}, 10, 2 );

add_filter( 'product_type_selector', function( $types ) {
    $types['my_type'] = __( 'My Product Type', 'my-wc-extension' );
    return $types;
} );
```

## Product Query Filters

```php
// Modify main product query
add_action( 'woocommerce_product_query', function( $query ) {
    $meta_query = $query->get( 'meta_query' );
    $meta_query[] = [
        'key'     => '_my_custom_field',
        'value'   => 'active',
        'compare' => '=',
    ];
    $query->set( 'meta_query', $meta_query );
} );

// WC 10.6: Product images lazy-loaded by default
// Customize with woocommerce_product_image_loading_attr filter
add_filter( 'woocommerce_product_image_loading_attr', function( $attr, $product ) {
    return 'eager'; // Disable lazy-load for specific cases
}, 10, 2 );
```
