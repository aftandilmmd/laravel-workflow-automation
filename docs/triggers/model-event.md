# Model Event Trigger

The `model_event` trigger fires automatically when an Eloquent model event occurs. No manual `start()` call needed — the workflow runs whenever the specified model is created, updated, deleted, or restored.

**Node key:** `model_event`

## PHP Builder

```php
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ModelEventTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\ModelEvent;

ModelEventTriggerNode::make()
    ->title('User Created')
    ->model(User::class)
    ->events([ModelEvent::Created, ModelEvent::Updated])
    ->onlyFields(['email', 'status']);
```

See [Node Builders](../api/node-builders.md) for the conventions shared by all builders.

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

No manual setup needed — the package automatically registers model event listeners when the application boots. Active trigger configurations are cached for 60 seconds.

::: tip
If you create a new workflow while the app is running, the trigger will be picked up automatically after the cache expires (60 seconds) or after clearing the cache with `Cache::forget('workflow:model_event_triggers')`.
:::

## How It Works

```text
User::create(['name' => 'Alice', 'email' => 'alice@example.com'])
          │
          ▼ (Eloquent 'created' event fires)
   ┌───────────────────┐
   │ Model Event       │
   │ Trigger           │
   └──────┬────────────┘
          │ main
          ▼
   [{'id': 1, 'name': 'Alice', 'email': 'alice@example.com', ...}]
```

## Example: Welcome Email on User Creation

```php
use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendMailNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ModelEventTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\ModelEvent;

$workflow = Workflow::create(['name' => 'User Onboarding']);

$trigger = $workflow->addNode(
    ModelEventTriggerNode::make()
        ->title('User Created')
        ->model('App\\Models\\User')
        ->events([ModelEvent::Created])
);

$email = $workflow->addNode(
    SendMailNode::make()
        ->title('Welcome Email')
        ->to('{{ item.email }}')
        ->subject('Welcome, {{ item.name }}!')
        ->body('Thanks for signing up.')
);

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
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ModelEventTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\ModelEvent;

$trigger = $workflow->addNode(
    ModelEventTriggerNode::make()
        ->title('Status Changed')
        ->model('App\\Models\\Order')
        ->events([ModelEvent::Updated])
        ->onlyFields(['status'])
);
```

With this config, updating `$order->update(['notes' => '...'])` will **not** trigger the workflow. Only changes to the `status` field fire it.

## Example: Track Order Status Changes

```php
use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendMailNode;
use Aftandilmmd\WorkflowAutomation\Builders\Conditions\SwitchNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ModelEventTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\ConditionOperator;
use Aftandilmmd\WorkflowAutomation\Enums\ModelEvent;

$workflow = Workflow::create(['name' => 'Order Status Tracker']);

$trigger = $workflow->addNode(
    ModelEventTriggerNode::make()
        ->title('Order Status Changed')
        ->model('App\\Models\\Order')
        ->events([ModelEvent::Updated])
        ->onlyFields(['status'])
);

$router = $workflow->addNode(
    SwitchNode::make()
        ->title('Route by Status')
        ->field('{{ item.status }}')
        ->case('case_shipped', ConditionOperator::Equals, 'shipped')
        ->case('case_cancelled', ConditionOperator::Equals, 'cancelled')
);

$shippedEmail = $workflow->addNode(
    SendMailNode::make()
        ->title('Shipped Email')
        ->to('{{ item.customer_email }}')
        ->subject('Order #{{ item.id }} shipped!')
        ->body('Your order is on its way.')
);

$cancelledEmail = $workflow->addNode(
    SendMailNode::make()
        ->title('Cancelled Email')
        ->to('{{ item.customer_email }}')
        ->subject('Order #{{ item.id }} cancelled')
        ->body('Your order has been cancelled.')
);

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
