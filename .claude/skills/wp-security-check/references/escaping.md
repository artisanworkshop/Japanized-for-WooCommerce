# Output Escaping Patterns

## Core escaping functions

| Context | Function |
|---|---|
| HTML text content | `esc_html( $text )` |
| HTML attribute value | `esc_attr( $value )` |
| URL in href/src/action | `esc_url( $url )` |
| JavaScript string | `esc_js( $value )` |
| HTML (post content) | `wp_kses_post( $html )` |
| Arbitrary HTML | `wp_kses( $html, $allowed_tags )` |
| Translatable text | `esc_html__()`, `esc_html_e()`, `esc_attr__()`, `esc_attr_e()` |

## Rules

1. Escape as late as possible (at the point of output).
2. Never sanitize for display — sanitize on input, escape on output.
3. Double-escaping is harmless; missing escaping is a vulnerability.

## Examples

```php
// Text in HTML
echo esc_html( $title );

// In attribute
echo '<input value="' . esc_attr( $value ) . '">';

// URL
echo '<a href="' . esc_url( $url ) . '">';

// Translatable + escaped
echo esc_html__( 'Hello World', 'plugin-name' );
esc_html_e( 'Hello World', 'plugin-name' );

// Post content with allowed HTML
echo wp_kses_post( $content );

// wp_localize_script handles escaping automatically via json_encode
wp_localize_script( 'handle', 'pluginData', [
    'apiUrl' => esc_url_raw( rest_url( 'plugin/v1' ) ),
    'nonce'  => wp_create_nonce( 'wp_rest' ),
] );
```

## Common mistakes

```php
// NG — unescaped output
echo $user_input;
echo "<div class='$class'>";
echo "<a href='$url'>";

// NG — wrong function for context
echo esc_attr( $url );   // Use esc_url for URLs
echo esc_html( $attr );  // Fine but esc_attr is more precise for attributes
```
