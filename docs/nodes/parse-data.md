<div v-pre>

# Parse Data

Parses raw data from a field (JSON, CSV, or query string) into structured data.

**Node key:** `parse_data` · **Type:** Transformer

## PHP Builder

```php
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\ParseDataNode;
use Aftandilmmd\WorkflowAutomation\Enums\ParseFormat;

ParseDataNode::make()
    ->title('Parse Webhook Body')
    ->sourceField('{{ item.raw_body }}')
    ->format(ParseFormat::Json)
    ->targetField('payload');
```

See [Node Builders](../api/node-builders.md) for the conventions shared by all builders.

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

## Examples

### JSON — Parse an API response

An HTTP Request node fetches order data from an external API. The response body comes back as a raw JSON string — Parse Data turns it into a usable array.

```php
use Aftandilmmd\WorkflowAutomation\Builders\Actions\HttpRequestNode;
use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendMailNode;
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\ParseDataNode;
use Aftandilmmd\WorkflowAutomation\Enums\HttpMethod;

$fetch = $workflow->addNode(
    HttpRequestNode::make()
        ->title('Fetch Order')
        ->url('https://api.store.com/orders/{{ item.order_id }}')
        ->method(HttpMethod::Get)
);

$parse = $workflow->addNode(
    ParseDataNode::make()
        ->title('Parse Response')
        ->sourceField('response_body')
        ->format(ParseFormat::Json)
        ->targetField('order')
);

$notify = $workflow->addNode(
    SendMailNode::make()
        ->title('Notify Customer')
        ->to('{{ item.order.customer.email }}')
        ->subject('Your order #{{ item.order.id }} has shipped!')
);

$fetch->connect($parse);
$parse->connect($notify);
```

**Before parse** — `response_body` is a string:

```json
"{\"id\":1024,\"customer\":{\"email\":\"john@example.com\"},\"status\":\"shipped\"}"
```

**After parse** — `order` is a structured array you can reference with expressions:

```json
{
  "order": {
    "id": 1024,
    "customer": { "email": "john@example.com" },
    "status": "shipped"
  }
}
```

### CSV — Import contacts from a file

A webhook receives a CSV file. Parse Data splits it into rows, then a Loop node processes each contact.

```php
use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendMailNode;
use Aftandilmmd\WorkflowAutomation\Builders\Controls\LoopNode;
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\ParseDataNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\WebhookTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\ParseFormat;

$webhook = $workflow->addNode(
    WebhookTriggerNode::make()
        ->title('CSV Upload')
);

$parse = $workflow->addNode(
    ParseDataNode::make()
        ->title('Parse CSV')
        ->sourceField('csv_body')
        ->format(ParseFormat::Csv)
        ->targetField('contacts')
);

$loop = $workflow->addNode(
    LoopNode::make()
        ->title('Each Contact')
        ->sourceField('contacts')
);

$mail = $workflow->addNode(
    SendMailNode::make()
        ->title('Send Welcome')
        ->to('{{ item.email }}')
        ->subject('Welcome, {{ item.name }}!')
);

$webhook->connect($parse);
$parse->connect($loop);
$loop->connect($mail);
```

**Before parse** — `csv_body` is a raw string:

```
name,email,role
Alice,alice@example.com,admin
Bob,bob@example.com,editor
```

**After parse** — `contacts` is an array of rows:

```json
{
  "contacts": [
    { "name": "Alice", "email": "alice@example.com", "role": "admin" },
    { "name": "Bob", "email": "bob@example.com", "role": "editor" }
  ]
}
```

### Query String — Parse form data

A webhook receives URL-encoded form data. Parse Data converts it into key-value pairs.

```php
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\ParseDataNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\WebhookTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\ParseFormat;

$webhook = $workflow->addNode(
    WebhookTriggerNode::make()
        ->title('Form Submit')
);

$parse = $workflow->addNode(
    ParseDataNode::make()
        ->title('Parse Form')
        ->sourceField('body')
        ->format(ParseFormat::KeyValue)
        ->targetField('form')
);

$webhook->connect($parse);
```

**Before parse** — `body` is a query string:

```
name=Alice&email=alice%40example.com&plan=pro
```

**After parse** — `form` is a structured array:

```json
{
  "form": {
    "name": "Alice",
    "email": "alice@example.com",
    "plan": "pro"
  }
}
```

## Tips

- Combine with a [Loop](/nodes/loop) node to iterate over parsed CSV rows or JSON arrays
- The `source_field` supports expressions — use `{{ nodes.HTTP Request.response_body }}` to reference a previous node's output
- Malformed data routes to the `error` port with the exception message, so you can handle failures gracefully

</div>
