# Send Mail

Sends an email for each item passing through it using Laravel's Mail facade.

**Node key:** `send_mail` · **Type:** Action

## Config

| Key | Type | Required | Expression | Description |
| --- | --- | --- | --- | --- |
| `to` | string | Yes | Yes | Recipient email address |
| `subject` | string | Yes | Yes | Email subject line |
| `body` | textarea | Yes | Yes | Email body content |
| `from` | string | No | No | Override default from address |
| `is_html` | boolean | No | No | Send as HTML instead of plain text |

## Ports

| Direction | Port | Description |
| --- | --- | --- |
| Input | `main` | Items to process |
| Output | `main` | Items that were emailed successfully (with `mail_sent: true`) |
| Output | `error` | Items that failed to send (with error message) |

## Behavior

The node iterates over every input item and sends one email per item:

1. Resolves `to`, `subject`, and `body` as expressions against the current item
2. Sends via `Mail::raw()` (plain text) or `Mail::html()` (when `is_html` is `true`)
3. If `from` is provided, overrides the default mailer address
4. On success: item goes to `main` port with `mail_sent: true` added
5. On failure: item goes to `error` port with the exception message

## Example

```php
$workflow = Workflow::create(['name' => 'Order Confirmation']);

$trigger = $workflow->addNode('New Order', 'model_event', [
    'model'  => 'App\\Models\\Order',
    'events' => ['created'],
]);

$email = $workflow->addNode('Confirmation Email', 'send_mail', [
    'to'      => '{{ item.customer_email }}',
    'subject' => 'Order #{{ item.id }} confirmed',
    'body'    => 'Hi {{ item.customer_name }}, your order for ${{ item.total }} has been confirmed.',
    'is_html' => false,
]);

$trigger->connect($email);
$workflow->activate();
```

## Input / Output Example

**Input (on `main`):**

```php
[
    ['customer_email' => 'alice@example.com', 'customer_name' => 'Alice', 'id' => 42, 'total' => 99.90],
]
```

**Output (on `main` — success):**

```php
[
    ['customer_email' => 'alice@example.com', 'customer_name' => 'Alice', 'id' => 42, 'total' => 99.90, 'mail_sent' => true],
]
```

**Output (on `error` — failure):**

```php
[
    ['customer_email' => 'invalid', 'customer_name' => 'Alice', 'id' => 42, 'total' => 99.90, 'error' => 'Expected valid email address'],
]
```

## Tips

- No Mailable class needed — the node uses `Mail::raw()` / `Mail::html()` directly
- If a send fails (SMTP timeout, invalid recipient), the item is routed to `error` so you can handle failures downstream
- Connect the `error` port to an [Error Handler](/nodes/error-handler) for centralized error routing
