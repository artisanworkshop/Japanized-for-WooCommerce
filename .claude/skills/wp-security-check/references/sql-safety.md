# SQL Safety

## Always use $wpdb->prepare() for variable queries

```php
global $wpdb;

// NG — SQL injection risk
$results = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}plugin_items WHERE status = '{$status}'"
);

// OK
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}plugin_items WHERE status = %s",
        $status
    )
);
```

## Placeholders

| Placeholder | Type |
|---|---|
| `%d` | Integer |
| `%f` | Float |
| `%s` | String (auto-quoted) |
| `%i` | Identifier (table/column name) — WP 6.2+ |

## Safe insert/update/delete

```php
// INSERT
$wpdb->insert(
    $wpdb->prefix . 'plugin_items',
    [
        'order_id' => $order_id,
        'status'   => $status,
    ],
    [ '%d', '%s' ]
);

// UPDATE
$wpdb->update(
    $wpdb->prefix . 'plugin_items',
    [ 'status' => 'completed' ],
    [ 'id' => $item_id ],
    [ '%s' ],
    [ '%d' ]
);

// DELETE
$wpdb->delete(
    $wpdb->prefix . 'plugin_items',
    [ 'id' => $item_id ],
    [ '%d' ]
);
```

## IN clause (multiple values)

```php
$ids          = array_map( 'absint', $id_array );
$placeholders = implode( ', ', array_fill( 0, count( $ids ), '%d' ) );
$sql          = $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}plugin_items WHERE id IN ({$placeholders})",
    ...$ids
);
$results = $wpdb->get_results( $sql );
```

## Never use direct queries without prepare

```php
// NG — even for seemingly safe values
$wpdb->query( "DELETE FROM {$table} WHERE id = " . $id );

// OK
$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id = %d", $id ) );
```
