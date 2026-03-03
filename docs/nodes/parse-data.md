# Parse Data

Parses raw data from a field (JSON, CSV, or query string) into structured data.

**Node key:** `parse_data` · **Type:** Transformer

## Config

| Key | Type | Required | Expression | Description |
| --- | --- | --- | --- | --- |
| `source_field` | string | Yes | Yes | Field containing the raw data |
| `format` | select | Yes | No | Parse format: `json`, `csv`, or `key_value` |
| `target_field` | string | Yes | No | Field to store the parsed result |

## Ports

| Direction | Port | Description |
| --- | --- | --- |
| Input | `main` | Items to process |
| Output | `main` | Items with parsed data added |
| Output | `error` | Items that failed to parse |

## Parse Formats

| Format | Behavior |
| --- | --- |
| `json` | `json_decode()` — produces array or scalar |
| `csv` | First row = headers, subsequent rows = associative arrays |
| `key_value` | `parse_str()` — handles `key1=val1&key2=val2` format |

## Example

```php
$parse = $workflow->addNode('Parse CSV', 'parse_data', [
    'source_field' => 'csv_body',
    'format'       => 'csv',
    'target_field' => 'rows',
]);
```

## Input / Output Example

**Input (JSON format):**

```php
[
    ['api_response' => '{"status":"ok","count":42}'],
]
```

**Config:** `source_field: "api_response"`, `format: "json"`, `target_field: "parsed"`

**Output:**

```php
[
    [
        'api_response' => '{"status":"ok","count":42}',
        'parsed'       => ['status' => 'ok', 'count' => 42],
    ],
]
```

## Tips

- Combine with a [Loop](/nodes/loop) node to iterate over parsed CSV rows or JSON arrays
- The `source_field` supports expressions for dynamic field resolution
- Malformed data routes to `error` with the exception message
