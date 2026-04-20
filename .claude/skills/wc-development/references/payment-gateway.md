# Payment Gateway Development

## Basic Gateway Structure

```php
class WC_Gateway_My_Payment extends WC_Payment_Gateway {

    public function __construct() {
        $this->id                 = 'my_payment';
        $this->icon               = apply_filters( 'wc_my_payment_icon', '' );
        $this->has_fields         = true;
        $this->method_title       = __( 'My Payment', 'my-wc-extension' );
        $this->method_description = __( 'Accept payments via My Payment.', 'my-wc-extension' );

        $this->supports = [
            'products',
            'refunds',
            // 'tokenization',
            // 'subscriptions',
            // 'subscription_cancellation',
        ];

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled     = $this->get_option( 'enabled' );
        $this->testmode    = 'yes' === $this->get_option( 'testmode' );

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
        add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), [ $this, 'handle_webhook' ] );
    }

    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __( 'Enable/Disable', 'my-wc-extension' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable My Payment', 'my-wc-extension' ),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __( 'Title', 'my-wc-extension' ),
                'type'        => 'text',
                'description' => __( 'Payment method title shown at checkout.', 'my-wc-extension' ),
                'default'     => __( 'My Payment', 'my-wc-extension' ),
            ],
            'testmode' => [
                'title'   => __( 'Test mode', 'my-wc-extension' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable test mode', 'my-wc-extension' ),
                'default' => 'yes',
            ],
            'api_key' => [
                'title' => __( 'API Key', 'my-wc-extension' ),
                'type'  => 'password',
            ],
        ];
    }

    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        try {
            // Call external API
            $result = $this->api_charge( $order );

            if ( $result['success'] ) {
                $order->set_transaction_id( $result['transaction_id'] );
                $order->payment_complete( $result['transaction_id'] );
                $order->add_order_note(
                    sprintf( __( 'Payment completed. Transaction: %s', 'my-wc-extension' ), $result['transaction_id'] )
                );

                WC()->cart->empty_cart();

                return [
                    'result'   => 'success',
                    'redirect' => $this->get_return_url( $order ),
                ];
            }
        } catch ( \Exception $e ) {
            wc_add_notice( $e->getMessage(), 'error' );
            $order->add_order_note( 'Payment failed: ' . $e->getMessage() );
            return [ 'result' => 'failure' ];
        }
    }
}
```

## Gateway Registration

```php
add_filter( 'woocommerce_payment_gateways', function( $gateways ) {
    $gateways[] = 'WC_Gateway_My_Payment';
    return $gateways;
});
```

## Refund Support

```php
public function process_refund( $order_id, $amount = null, $reason = '' ) {
    $order = wc_get_order( $order_id );
    $transaction_id = $order->get_transaction_id();

    if ( ! $transaction_id ) {
        return new \WP_Error( 'no_transaction', __( 'No transaction ID found.', 'my-wc-extension' ) );
    }

    try {
        $result = $this->api_refund( $transaction_id, $amount );

        if ( $result['success'] ) {
            $order->add_order_note(
                sprintf( __( 'Refunded %s. Reason: %s', 'my-wc-extension' ), wc_price( $amount ), $reason )
            );
            return true;
        }

        return new \WP_Error( 'refund_failed', $result['message'] );
    } catch ( \Exception $e ) {
        return new \WP_Error( 'refund_error', $e->getMessage() );
    }
}
```

## Redirect-based Payment Flow

For gateways that redirect to an external payment page:

```php
public function process_payment( $order_id ) {
    $order = wc_get_order( $order_id );
    $order->update_status( 'pending', __( 'Awaiting payment redirect.', 'my-wc-extension' ) );

    return [
        'result'   => 'success',
        'redirect' => $this->get_payment_url( $order ),
    ];
}

// Return URL handler
public function handle_return() {
    $order_id = absint( $_GET['order_id'] ?? 0 );
    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        wp_die( 'Invalid order' );
    }

    // Verify payment status with provider
    if ( $this->verify_payment( $order ) ) {
        $order->payment_complete();
        wp_redirect( $this->get_return_url( $order ) );
    } else {
        $order->update_status( 'failed' );
        wp_redirect( wc_get_checkout_url() );
    }
    exit;
}
```

## Webhook Handler

```php
public function handle_webhook() {
    $payload = file_get_contents( 'php://input' );
    $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';

    // Verify webhook signature
    if ( ! $this->verify_signature( $payload, $signature ) ) {
        status_header( 403 );
        exit( 'Invalid signature' );
    }

    $data = json_decode( $payload, true );
    $order = wc_get_order( $data['order_id'] );

    if ( ! $order ) {
        status_header( 404 );
        exit( 'Order not found' );
    }

    switch ( $data['event'] ) {
        case 'payment.completed':
            $order->payment_complete( $data['transaction_id'] );
            break;
        case 'payment.failed':
            $order->update_status( 'failed', $data['reason'] );
            break;
        case 'refund.completed':
            // Handle refund confirmation
            break;
    }

    status_header( 200 );
    exit( 'OK' );
}
```

## Tokenization Support

```php
// In constructor
$this->supports[] = 'tokenization';

// After successful payment, save token
$token = new \WC_Payment_Token_CC();
$token->set_token( $api_token_id );
$token->set_gateway_id( $this->id );
$token->set_card_type( $card_type );
$token->set_last4( $last4 );
$token->set_expiry_month( $exp_month );
$token->set_expiry_year( $exp_year );
$token->set_user_id( get_current_user_id() );
$token->save();

// Add to order
$order->add_payment_token( $token );
```

## Offline Payment Gateway (WC 10.6+ grouping)

To be grouped under "Offline payments" in the new React-based settings:

```php
// The gateway is classified as offline if it does not process payments through
// an external service. WooCommerce 10.6+ automatically groups these.
// New gateways are placed above the offline group by default.
```
