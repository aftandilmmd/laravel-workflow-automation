<div v-pre>

# Code / Expression

Evaluates custom expressions against each item. Inline transformations and filtering without creating a dedicated node.

**Node key:** `code` · **Type:** Code

## Config

| Key | Type | Required | Expression | Description |
| --- | --- | --- | --- | --- |
| `mode` | select | Yes | No | `transform` or `filter` |
| `expression` | textarea | Yes | Yes | Expression to evaluate per item |

## Ports

| Direction | Port | Description |
| --- | --- | --- |
| Input | `main` | Items to process |
| Output | `main` | Transformed or filtered items |
| Output | `error` | Items where evaluation failed |

## Modes

### Transform Mode

The expression result modifies the item:

- If result is an **array** → replaces the item entirely
- If result is a **scalar** → added as `_result` field, existing fields preserved

### Filter Mode

The expression result determines if the item is kept:

- **Truthy** result → item passes to `main`
- **Falsy** result → item is discarded

## Examples

**Transform — compute a value:**

```php
$code = $workflow->addNode('Discount', 'code', [
    'mode'       => 'transform',
    'expression' => '{{ item.price * (1 - item.discount_pct / 100) }}',
]);
// Output: item + {_result: 85.50}
```

**Filter — keep matching items:**

```php
$code = $workflow->addNode('Adults Only', 'code', [
    'mode'       => 'filter',
    'expression' => '{{ item.age >= 18 && item.verified == true }}',
]);
```

## Input / Output Example

**Transform mode input:**

```php
[
    ['price' => 100, 'discount_pct' => 15],
]
```

**Output:**

```php
[
    ['price' => 100, 'discount_pct' => 15, '_result' => 85],
]
```

## Tips

- No `eval()` is used — all expressions run through the safe recursive descent parser
- Use `item.field` syntax: `{{ item.quantity * item.unit_price }}`
- Expression errors route to `error` — handle malformed data gracefully


</div>
