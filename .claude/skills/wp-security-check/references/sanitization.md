# Input Sanitization Patterns

## Core WordPress sanitization functions

| Input type | Function |
|---|---|
| General text (no HTML) | `sanitize_text_field( wp_unslash( $input ) )` |
| Textarea (no HTML) | `sanitize_textarea_field( wp_unslash( $input ) )` |
| Integer | `absint( $input )` or `intval( $input )` |
| Float | `floatval( $input )` |
| Email | `sanitize_email( $input )` |
| URL | `esc_url_raw( $input )` |
| HTML content | `wp_kses_post( $input )` |
| File name | `sanitize_file_name( $input )` |
| Key/slug | `sanitize_key( $input )` |
| HTML class | `sanitize_html_class( $input )` |

## WooCommerce sanitization functions

```php
wc_clean( $input )       // Like sanitize_text_field but strips deep arrays too
wc_sanitize_order_id( $id )
wc_format_decimal( $price )
```

## Always unslash before sanitize

```php
// Magic quotes may be active in old WP — always unslash first
$value = sanitize_text_field( wp_unslash( $_POST['field'] ) );
```

## Array inputs

```php
$items = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['items'] ) );
$ids   = array_map( 'absint', (array) $_POST['ids'] );
```

## Never trust these directly

```php
// NG
$id = $_POST['id'];
$url = $_GET['redirect'];
$html = $_POST['content'];

// OK
$id  = absint( $_POST['id'] );
$url = esc_url_raw( wp_unslash( $_GET['redirect'] ) );
$html = wp_kses_post( wp_unslash( $_POST['content'] ) );
```
