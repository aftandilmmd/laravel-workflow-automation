# Model Event Trigger

The `model_event` trigger fires automatically when an Eloquent model event occurs. No manual `start()` call needed — the workflow runs whenever the specified model is created, updated, deleted, or restored.

**Node key:** `model_event`

## Config

| Key | Type | Required | Expression | Description |
| --- | --- | --- | --- | --- |
| `model` | string | Yes | No | Fully qualified model class (e.g. `App\\Models\\User`) |
| `events` | multiselect | Yes | No | Events to listen for: `created`, `updated`, `deleted`, `restored` |
| `only_fields` | array | No | No | Only trigger on these fields (for `updated` events; empty = any change) |

## Ports

| Direction | Port | Description |
| --- | --- | --- |
| Output | `main` | The model's data as `$model->toArray()` wrapped in a single-item array |

## Setup

Register the model event listener in your `AppServiceProvider`:

```php
// app/Providers/AppServiceProvider.php

use Aftandilmmd\WorkflowAutomation\Listeners\ModelEventListener;

public function boot(): void
{
    ModelEventListener::register();
}
```

This scans all active workflows with `model_event` triggers and attaches Eloquent observers. Trigger configurations are cached for 60 seconds.

::: warning Important
Call `ModelEventListener::register()` in `boot()`, not `register()`. The database must be available when observers are attached.
:::

## How It Works

```text
User::create(['name' => 'Alice', 'email' => 'alice@example.com'])
          │
          ▼ (Eloquent 'created' event fires)
   ┌──────────────────┐
   │ Model Event       │
   │ Trigger           │
   └──────┬───────────┘
          │ main
          ▼
   [{'id': 1, 'name': 'Alice', 'email': 'alice@example.com', ...}]
```

## Example: Welcome Email on User Creation

```php
$workflow = Workflow::create(['name' => 'User Onboarding']);

$trigger = $workflow->addNode('User Created', 'model_event', [
    'model'  => 'App\\Models\\User',
    'events' => ['created'],
]);

$email = $workflow->addNode('Welcome Email', 'send_mail', [
    'to'      => '{{ item.email }}',
    'subject' => 'Welcome, {{ item.name }}!',
    'body'    => 'Thanks for signing up.',
]);

$trigger->connect($email);
$workflow->activate();
```

Now whenever `User::create(...)` runs, the workflow fires automatically:

```php
User::create([
    'name'     => 'Alice',
    'email'    => 'alice@example.com',
    'password' => bcrypt('secret'),
]);
// → Workflow triggers, welcome email sent
```

## Field Filtering (Updated Events)

For `updated` events, you can limit the trigger to fire only when specific fields change:

```php
$trigger = $workflow->addNode('Status Changed', 'model_event', [
    'model'       => 'App\\Models\\Order',
    'events'      => ['updated'],
    'only_fields' => ['status'],
]);
```

With this config, updating `$order->update(['notes' => '...'])` will **not** trigger the workflow. Only changes to the `status` field fire it.

## Example: Track Order Status Changes

```php
$workflow = Workflow::create(['name' => 'Order Status Tracker']);

$trigger = $workflow->addNode('Order Status Changed', 'model_event', [
    'model'       => 'App\\Models\\Order',
    'events'      => ['updated'],
    'only_fields' => ['status'],
]);

$router = $workflow->addNode('Route by Status', 'switch', [
    'field' => '{{ item.status }}',
    'cases' => [
        ['port' => 'case_shipped',   'operator' => 'equals', 'value' => 'shipped'],
        ['port' => 'case_cancelled', 'operator' => 'equals', 'value' => 'cancelled'],
    ],
]);

$shippedEmail = $workflow->addNode('Shipped Email', 'send_mail', [
    'to'      => '{{ item.customer_email }}',
    'subject' => 'Order #{{ item.id }} shipped!',
    'body'    => 'Your order is on its way.',
]);

$cancelledEmail = $workflow->addNode('Cancelled Email', 'send_mail', [
    'to'      => '{{ item.customer_email }}',
    'subject' => 'Order #{{ item.id }} cancelled',
    'body'    => 'Your order has been cancelled.',
]);

$trigger->connect($router);
$router->connect($shippedEmail, 'case_shipped');
$router->connect($cancelledEmail, 'case_cancelled');
$workflow->activate();
```

## Input / Output

**Input:** None (triggers have no input ports)

**Output on `main` port:**

```php
// When User::create(['name' => 'Alice', 'email' => 'alice@example.com']) fires:
[
    [
        'id'         => 1,
        'name'       => 'Alice',
        'email'      => 'alice@example.com',
        'created_at' => '2024-01-15T08:00:00.000000Z',
        'updated_at' => '2024-01-15T08:00:00.000000Z',
    ],
]
```

## Tips

- The model data is always a single item: `[$model->toArray()]`
- If multiple workflows use the same model + event, they all fire independently
- `only_fields` only applies to `updated` events; it's ignored for `created`, `deleted`, and `restored`
- Workflows are dispatched asynchronously via the queue by default
- Hidden model attributes (e.g. `password`) are excluded from `toArray()` per Eloquent convention
