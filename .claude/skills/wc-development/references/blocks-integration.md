# WooCommerce Blocks Integration

Block-based Cart/Checkout is WooCommerce's default. Extensions must integrate properly.

## Payment Method Registration (Server-side)

```php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

class My_Payment_Blocks extends AbstractPaymentMethodType {
    protected $name = 'my_payment';

    public function initialize() {
        $this->settings = get_option( 'woocommerce_my_payment_settings', [] );
    }

    public function is_active() {
        return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'my-payment-blocks',
            plugins_url( 'build/blocks.js', MY_EXTENSION_PLUGIN_FILE ),
            [],
            MY_EXTENSION_VERSION,
            true
        );
        return [ 'my-payment-blocks' ];
    }

    public function get_payment_method_data() {
        return [
            'title'       => $this->settings['title'] ?? 'My Payment',
            'description' => $this->settings['description'] ?? '',
            'supports'    => [ 'products', 'refunds' ],
            'testmode'    => ( $this->settings['testmode'] ?? 'no' ) === 'yes',
        ];
    }
}
```

## Payment Method Registration Hook

```php
add_action( 'woocommerce_blocks_loaded', function() {
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry' ) ) {
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( $registry ) {
                $registry->register( new My_Payment_Blocks() );
            }
        );
    }
});
```

## Payment Method (Client-side JavaScript)

```javascript
// src/blocks/payment-method.js
import { decodeEntities } from '@wordpress/html-entities';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';

const settings = getSetting( 'my_payment_data', {} );
const title = decodeEntities( settings.title || 'My Payment' );

const Content = () => {
    return decodeEntities( settings.description || '' );
};

const Label = ( props ) => {
    const { PaymentMethodLabel } = props.components;
    return <PaymentMethodLabel text={ title } />;
};

registerPaymentMethod( {
    name: 'my_payment',
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: title,
    supports: {
        features: settings.supports || [ 'products' ],
    },
} );
```

## Handling Payment Data from Block Checkout

In block checkout, payment field data arrives via `payment_data` in the Store API request:

```php
// In process_payment(), extract block checkout data:
public function process_payment( $order_id ) {
    $order = wc_get_order( $order_id );

    // Block checkout sends data through WC()->session or as payment_data
    $token = sanitize_text_field(
        isset( $_POST['my_payment_token'] )
            ? wp_unslash( $_POST['my_payment_token'] )
            : ''
    );

    // Or from Store API payment data
    if ( empty( $token ) && isset( WC()->session ) ) {
        $token = WC()->session->get( 'my_payment_token' );
    }

    // Process payment...
}
```

## Store API Schema Extension

Add custom data to Store API endpoints:

```php
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;

add_action( 'woocommerce_blocks_loaded', function() {
    $extend = StoreApi::container()->get( ExtendSchema::class );

    $extend->register_endpoint_data( [
        'endpoint'        => CartSchema::IDENTIFIER,
        'namespace'       => 'my-wc-extension',
        'data_callback'   => function() {
            return [
                'custom_field' => WC()->session->get( 'my_extension_data', '' ),
            ];
        },
        'schema_callback' => function() {
            return [
                'custom_field' => [
                    'description' => 'Custom extension data',
                    'type'        => 'string',
                    'readonly'    => true,
                ],
            ];
        },
    ] );
});
```

## Store API Update Callback

For frontend-to-server data flow in block checkout:

```php
woocommerce_store_api_register_update_callback( [
    'namespace' => 'my-wc-extension',
    'callback'  => function( $data ) {
        if ( isset( $data['custom_value'] ) ) {
            WC()->session->set(
                'my_extension_data',
                sanitize_text_field( $data['custom_value'] )
            );
        }
    },
] );
```

Client-side trigger:

```javascript
import { extensionCartUpdate } from '@woocommerce/blocks-checkout';

extensionCartUpdate( {
    namespace: 'my-wc-extension',
    data: { custom_value: 'selected_option' },
} );
```

## Checkout Block Integration (non-payment)

Register a block integration for scripts/styles:

```php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class My_Extension_Block_Integration implements IntegrationInterface {
    public function get_name() { return 'my-wc-extension'; }

    public function initialize() {
        $this->register_scripts();
    }

    public function get_script_handles() {
        return [ 'my-extension-checkout-frontend' ];
    }

    public function get_editor_script_handles() {
        return [ 'my-extension-checkout-editor' ];
    }

    public function get_script_data() {
        return [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'settings' => get_option( 'my_extension_settings', [] ),
        ];
    }

    private function register_scripts() {
        wp_register_script(
            'my-extension-checkout-frontend',
            plugins_url( 'build/checkout-frontend.js', MY_EXTENSION_PLUGIN_FILE ),
            [],
            MY_EXTENSION_VERSION,
            true
        );
    }
}

add_action( 'woocommerce_blocks_loaded', function() {
    add_action(
        'woocommerce_blocks_checkout_block_registration',
        function( $registry ) {
            $registry->register( new My_Extension_Block_Integration() );
        }
    );
    add_action(
        'woocommerce_blocks_cart_block_registration',
        function( $registry ) {
            $registry->register( new My_Extension_Block_Integration() );
        }
    );
});
```

## SlotFill for Checkout Extensibility

```javascript
import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';

const MyExtensionOrderMeta = () => {
    return (
        <ExperimentalOrderMeta>
            <div className="my-extension-order-meta">
                Custom content in checkout sidebar
            </div>
        </ExperimentalOrderMeta>
    );
};

registerPlugin( 'my-extension-order-meta', {
    render: MyExtensionOrderMeta,
    scope: 'woocommerce-checkout',
} );
```
