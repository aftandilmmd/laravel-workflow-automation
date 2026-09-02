# Set Fields

Merges new or overwritten fields into each item. A lightweight data transformer.

**Node key:** `set_fields` · **Type:** Transformer

## PHP Builder

```php
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\SetFieldsNode;

SetFieldsNode::make()
    ->title('Normalize')
    ->fields(['full_name' => '{{ item.first_name }} {{ item.last_name }}'])
    ->field('source', 'workflow')
    ->keepExisting();
```

See [Node Builders](../api/node-builders.md) for the conventions shared by all builders.

## Config

| Key | Type | Required | Expression | Description |
| --- | --- | --- | --- | --- |
| `fields` | keyvalue | Yes | Yes | Fields to set or overwrite on each item |
| `keep_existing` | boolean | No | No | Keep existing item fields (default: `true`) |

## Ports

| Direction | Port | Description |
| --- | --- | --- |
| Input | `main` | Items to process |
| Output | `main` | Items with fields applied |
| Output | `error` | Items that failed during field evaluation |

## Behavior

- **`keep_existing: true`** (default) — new fields are merged on top of existing data
- **`keep_existing: false`** — the item is replaced entirely with only the configured fields

## Example

```php
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\SetFieldsNode;

$setFields = $workflow->addNode(
    SetFieldsNode::make()
        ->title('Normalize')
        ->fields([
            'processed_at' => '{{ now() }}',
            'email'        => '{{ lower(item.email) }}',
            'full_name'    => '{{ item.first_name + " " + item.last_name }}',
        ])
        ->keepExisting()
);
```

## Input / Output Example

**Input:**

```php
[
    ['first_name' => 'Alice', 'last_name' => 'Smith', 'email' => 'Alice@Example.COM'],
]
```

**Output (with `keep_existing: true`):**

```php
[
    [
        'first_name'   => 'Alice',
        'last_name'    => 'Smith',
        'email'        => 'alice@example.com',
        'processed_at' => '2024-01-15T08:00:00Z',
        'full_name'    => 'Alice Smith',
    ],
]
```

**Output (with `keep_existing: false`):**

```php
[
    [
        'email'        => 'alice@example.com',
        'processed_at' => '2024-01-15T08:00:00Z',
        'full_name'    => 'Alice Smith',
    ],
]
```

## Tips

- Use `keep_existing: false` to reshape items into a completely new structure
- All field values support expressions — combine fields, call functions, use conditionals
- Chain after HTTP Request or webhook triggers to normalize incoming data before processing
