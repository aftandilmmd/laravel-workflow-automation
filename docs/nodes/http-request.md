<div v-pre>

# HTTP Request

Makes an HTTP call for each item using Laravel's Http facade.

**Node key:** `http_request` · **Type:** Action

## Config

| Key | Type | Required | Expression | Description |
| --- | --- | --- | --- | --- |
| `url` | string | Yes | Yes | The URL to call |
| `method` | select | Yes | No | `GET`, `POST`, `PUT`, `PATCH`, `DELETE` |
| `headers` | keyvalue | No | Yes | Custom request headers |
| `body` | json | No | Yes | Request body as JSON |
| `timeout` | integer | No | No | Timeout in seconds (default: 30) |
| `include_response` | boolean | No | No | Include the full response in the output item |

## Ports

| Direction | Port | Description |
| --- | --- | --- |
| Input | `main` | Items to process |
| Output | `main` | Items with optional response data |
| Output | `error` | Items whose request failed |

## Behavior

For each input item:

1. Resolves `url`, `headers`, and `body` as expressions against the current item
2. Sends the HTTP request using the configured `method`
3. If `timeout` is set, overrides the default 30-second limit
4. When `include_response` is `true`, adds `http_response` to the item containing `status`, `body`, and `headers`
5. On success: item goes to `main` port
6. On failure (connection error, timeout): item goes to `error` port

## Example

```php
$apiCall = $workflow->addNode('Check Inventory', 'http_request', [
    'url'              => 'https://inventory.example.com/api/products/{{ item.product_id }}',
    'method'           => 'GET',
    'headers'          => ['Authorization' => 'Bearer my-api-token'],
    'timeout'          => 15,
    'include_response' => true,
]);
```

## Input / Output Example

**Input:**

```php
[
    ['product_id' => 'SKU-001', 'name' => 'Widget'],
]
```

**Output (with `include_response: true`):**

```php
[
    [
        'product_id'    => 'SKU-001',
        'name'          => 'Widget',
        'http_response' => [
            'status'  => 200,
            'body'    => ['stock' => 42, 'warehouse' => 'NYC'],
            'headers' => ['content-type' => 'application/json'],
        ],
    ],
]
```

Access response data downstream: `{{ item.http_response.body.stock }}`

## Tips

- Set `timeout` to a lower value for time-sensitive workflows to fail fast
- Connection errors, timeouts, and non-2xx responses all route to `error`
- The `body` config is sent as JSON for POST/PUT/PATCH requests


</div>
