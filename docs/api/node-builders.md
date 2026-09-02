# Node Builders

Node builders are fluent PHP classes that produce a node's configuration. They give you
autocomplete, enums for fixed values, and validation before anything is written to the
database.

```php
use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendMailNode;

$welcome = $workflow->addNode(
    SendMailNode::make()
        ->title('Welcome Email')
        ->to('{{ item.email }}')
        ->subject('Welcome, {{ item.name }}!')
        ->body('Thanks for signing up.')
);
```

The [array API](./array-api.md) still works, but it is no longer recommended and will
likely be removed in a future release.

## Conventions

### Class names

```
builder class = node key in PascalCase + "Node"
trigger nodes = node key in PascalCase + "TriggerNode"
```

| Node key | Builder | Node key | Builder |
| --- | --- | --- | --- |
| `send_mail` | `SendMailNode` | `manual` | `ManualTriggerNode` |
| `http_request` | `HttpRequestNode` | `webhook` | `WebhookTriggerNode` |
| `if_condition` | `IfConditionNode` | `schedule` | `ScheduleTriggerNode` |
| `switch` | `SwitchNode` | `model_event` | `ModelEventTriggerNode` |
| `code` | `CodeNode` | `event` | `EventTriggerNode` |
| `ai` | `AiNode` | `workflow` | `WorkflowTriggerNode` |

Builders live under `Aftandilmmd\WorkflowAutomation\Builders\{Group}`, where the group
matches the node type: `Triggers`, `Actions`, `Conditions`, `Transformers`, `Controls`,
`Utilities`, `Annotations`.

### Method names

Every config key has a method with the same name in camelCase — no exceptions.
`send_mode` → `sendMode()`, `source_field` → `sourceField()`.

- **Boolean fields** default to `true`: `->includeResponse()` is `include_response = true`.
- **List fields** get a plural setter that replaces and a singular one that appends:
  `->conditions([...])` vs `->condition('total', ConditionOperator::GreaterThan, 100)`.
- **Key/value fields** get the same pair: `->headers([...])` and `->header('X-Key', 'value')`.
- **`*_id` fields** accept an ID or a model: `->workflowId(12)`, `->workflow($workflow)`.

### Chain order

```php
XNode::make()          // 1. constructor
    ->title('...')     // 2. display name
    ->requiredField()  // 3. required fields, in config schema order
    ->optionalField()  // 4. optional fields
    ->position(x, y);  // 5. editor position
```

### Enums for fixed values

Every `select` field is backed by an enum. Setters accept the enum or a plain string.

```php
->operator(ConditionOperator::GreaterThan)   // recommended
->operator('greater_than')                   // also valid
```

| Enum | Used by |
| --- | --- |
| `ConditionOperator` | `if_condition`, `switch`, `filter` |
| `AggregateFunction` | `aggregate` |
| `MailSendMode` | `send_mail` |
| `HttpMethod` | `http_request`, `webhook` |
| `CommandType` | `run_command` |
| `AiProvider` | `ai` |
| `ModelEvent` | `model_event` |
| `ScheduleInterval` | `schedule` |
| `WebhookAuthType` | `webhook` |
| `WorkflowTriggerOn` | `workflow` (trigger) |
| `DelayUnit` | `delay` |
| `ErrorRoute` | `error_handler` |
| `MergeMode` | `merge` |
| `ParseFormat` | `parse_data` |
| `CodeMode` | `code` |
| `FilterLogic` | `filter` |
| `StickyColor` | `sticky_note` |

::: tip Operator vs ConditionOperator
`Operator` is deprecated in favour of `ConditionOperator`. The values are identical and the
old enum keeps working, so migrating is a one-line `use` change.
:::

### Validation

`addNode()` validates a builder against the node's config schema before saving. Missing
required fields and unknown keys throw `InvalidNodeConfigException`:

```
Missing required config for node [send_mail]: to, subject, body.
Unknown config key [operatr] for node [if_condition]. Did you mean [operator]?
```

Fields hidden by a `show_when` rule are not required. In `send_mail` for example, `to` is
only required in inline mode.

## Adding nodes

```php
// one node
$node = $workflow->addNode(
    SendMailNode::make()
        ->to('a@b.test')
        ->subject('Hi')
        ->body('...')
);

// several, in order
[$trigger, $prepare] = $workflow->addNodes(
    ManualTriggerNode::make()
        ->title('Start'),
    SetFieldsNode::make()
        ->field('source', 'api'),
);

// from an existing node (same workflow)
$next = $trigger->addNode(DelayNode::minutes(5));
```

## Triggers

```php
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\{
    ManualTriggerNode, ModelEventTriggerNode, EventTriggerNode,
    ScheduleTriggerNode, WebhookTriggerNode, WorkflowTriggerNode
};
use Aftandilmmd\WorkflowAutomation\Enums\{
    ModelEvent, ScheduleInterval, HttpMethod, WebhookAuthType, WorkflowTriggerOn
};

ManualTriggerNode::make()
    ->title('Manual Start')
    ->inputSchema(['email' => 'string', 'name' => 'string']);

ModelEventTriggerNode::make()
    ->title('User Created')
    ->model(User::class)
    ->events([ModelEvent::Created, ModelEvent::Updated])
    ->onlyFields(['email', 'status']);          // updated events only

EventTriggerNode::make()
    ->title('Order Placed')
    ->eventClass(OrderPlaced::class);

ScheduleTriggerNode::make()
    ->title('Every 6 Hours')
    ->intervalType(ScheduleInterval::Hours)
    ->intervalValue(6);

ScheduleTriggerNode::every(6, ScheduleInterval::Hours);   // same thing
// switches to cron mode
ScheduleTriggerNode::make()
    ->cron('0 9 * * 1');

WebhookTriggerNode::make()
    ->title('Stripe Webhook')
    ->method(HttpMethod::Post)
    ->authType(WebhookAuthType::Bearer)
    ->credential($stripeCredential);

WorkflowTriggerNode::make()
    ->title('After Import')
    ->sourceWorkflow($importWorkflow)
    ->triggerOn(WorkflowTriggerOn::Completed);
```

## Actions

```php
use Aftandilmmd\WorkflowAutomation\Builders\Actions\{
    SendMailNode, HttpRequestNode, UpdateModelNode,
    DispatchJobNode, SendNotificationNode, RunCommandNode, AiNode
};
use Aftandilmmd\WorkflowAutomation\Enums\AiProvider;

// inline mode is selected automatically by the fields you use
SendMailNode::make()
    ->title('Welcome')
    ->to('{{ item.email }}')
    ->subject('Welcome, {{ item.name }}!')
    ->body('Thanks for signing up.')
    ->cc('ops@example.com')
    ->replyTo('support@example.com')
    ->isHtml()
    ->attachment('invoice.pdf', '{{ item.invoice_path }}');

// mailable mode
SendMailNode::make()
    ->mailableClass(WelcomeMail::class)
    ->mailableTo('{{ item.email }}');

HttpRequestNode::post('https://api.example.com/orders', ['order_id' => '{{ item.id }}'])
    ->title('Push Order')
    ->header('X-Source', 'workflow')
    ->credential($apiCredential)
    ->timeout(15)
    ->includeResponse();

// also: HttpRequestNode::get(), ::put(), ::patch(), ::delete()

UpdateModelNode::make()
    ->title('Mark Paid')
    ->model(Order::class)
    ->findBy('id')
    ->findValue('{{ item.order_id }}')
    ->fields(['status' => 'paid']);

DispatchJobNode::make()
    ->jobClass(GenerateInvoice::class)
    ->queue('invoices')
    ->delay(30)
    ->withItem();

SendNotificationNode::make()
    ->notificationClass(OrderShipped::class)
    ->notifiableClass(User::class)
    ->notifiableId('{{ item.user_id }}');

RunCommandNode::artisan('reports:generate')
    ->argument('--date', '{{ item.date }}')
    ->timeout(120)
    ->includeOutput();

RunCommandNode::shell('rsync -a ./storage /backup')
    ->workingDirectory('/var/www/app');

AiNode::make()
    ->prompt('Classify this ticket: {{ item.body }}')
    ->systemPrompt('Answer with one word.')
    ->provider(AiProvider::Anthropic)
    ->model('claude-sonnet-4-5-20250514')
    ->temperature(0.2)
    ->maxTokens(200)
    ->outputKey('category');
```

## Conditions

```php
use Aftandilmmd\WorkflowAutomation\Builders\Conditions\{IfConditionNode, SwitchNode};
use Aftandilmmd\WorkflowAutomation\Enums\ConditionOperator;

// ports: true / false
IfConditionNode::make()
    ->title('VIP Check')
    ->field('{{ item.total }}')
    ->operator(ConditionOperator::GreaterThan)
    ->value(500);

IfConditionNode::when('{{ item.total }}', ConditionOperator::GreaterThan, 500);

// ports: the case ports you define, plus default
SwitchNode::make()
    ->title('Route by Plan')
    ->field('{{ item.plan }}')
    ->case('case_premium', ConditionOperator::Equals, 'premium')
    ->case('case_pro', ConditionOperator::Equals, 'pro')
    ->fallthrough();
```

## Transformers

```php
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\{SetFieldsNode, ParseDataNode};
use Aftandilmmd\WorkflowAutomation\Enums\ParseFormat;

SetFieldsNode::make()
    ->fields(['full_name' => '{{ item.first_name }} {{ item.last_name }}'])
    ->field('source', 'workflow')
    ->keepExisting();

ParseDataNode::make()
    ->sourceField('{{ item.raw_body }}')
    ->format(ParseFormat::Json)
    ->targetField('payload');
```

## Controls

```php
use Aftandilmmd\WorkflowAutomation\Builders\Controls\{
    LoopNode, MergeNode, DelayNode, SubWorkflowNode, ErrorHandlerNode, WaitResumeNode
};
use Aftandilmmd\WorkflowAutomation\Enums\{MergeMode, DelayUnit, ErrorRoute};

// ports: loop_item / loop_done
LoopNode::make()
    ->sourceField('{{ item.lines }}');

MergeNode::make()
    ->mode(MergeMode::WaitAll);

DelayNode::minutes(15);   // also ::seconds(), ::hours()

DelayNode::make()
    ->delayType(DelayUnit::Hours)
    ->delayValue(2);

SubWorkflowNode::make()
    ->workflow($fulfillmentWorkflow)
    ->passItems()
    ->waitForResult();

// ports: notify / retry / ignore / stop
ErrorHandlerNode::make()
    ->rule('/timeout|timed out/i', ErrorRoute::Retry)
    ->rule('/validation/i', ErrorRoute::Ignore)
    ->defaultRoute(ErrorRoute::Notify);

// ports: resume / timeout
WaitResumeNode::make()
    ->timeoutSeconds(86400);
```

## Utilities and annotations

```php
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\{FilterNode, AggregateNode, CodeNode};
use Aftandilmmd\WorkflowAutomation\Builders\Annotations\StickyNoteNode;
use Aftandilmmd\WorkflowAutomation\Enums\{ConditionOperator, FilterLogic, AggregateFunction, StickyColor};

FilterNode::make()
    ->condition('status', ConditionOperator::Equals, 'active')
    ->condition('total', ConditionOperator::GreaterOrEqual, 1000)
    ->logic(FilterLogic::And);

AggregateNode::make()
    ->groupBy('country')
    ->operation('total', AggregateFunction::Sum, 'revenue')
    ->operation('id', AggregateFunction::Count, 'orders');

CodeNode::transform('{{ item.total - item.cost }}');           // also ::filter()

StickyNoteNode::make()
    ->content('This branch only runs for VIP customers.')
    ->color(StickyColor::Yellow)
    ->position(240, 80);
```

## Nodes without a builder

Custom and plugin nodes that ship no builder can use `GenericNode`:

```php
use Aftandilmmd\WorkflowAutomation\Builders\GenericNode;

GenericNode::make('my_custom_node')
    ->title('Custom Step')
    ->set('some_key', 'value')
    ->set('other_key', ['a', 'b']);
```

`GenericNode` validates against the node's config schema like any other builder.

## Generating a builder

```bash
php artisan workflow:make-node-builder my_custom_node
```

See [Commands](../commands.md#workflow-make-node-builder) for the options, and
[Custom Nodes](../advanced/custom-nodes.md#node-builders) for writing one by hand.

## Full example

```php
use Aftandilmmd\WorkflowAutomation\Models\Workflow;

$workflow = Workflow::create(['name' => 'User Onboarding']);

$trigger = $workflow->addNode(
    ModelEventTriggerNode::make()
        ->title('User Created')
        ->model(User::class)
        ->events([ModelEvent::Created])
);

$vip = $workflow->addNode(
    IfConditionNode::when('{{ item.total_spent }}', ConditionOperator::GreaterThan, 500)
        ->title('VIP Check')
);

$vipMail = $workflow->addNode(
    SendMailNode::make()
        ->title('VIP Welcome')
        ->to('{{ item.email }}')
        ->subject('Welcome, VIP!')
        ->body('...')
);

$mail = $workflow->addNode(
    SendMailNode::make()
        ->title('Generic Welcome')
        ->to('{{ item.email }}')
        ->subject('Welcome!')
        ->body('...')
);

$trigger->connect($vip);
$vip->connect($vipMail, 'true');
$vip->connect($mail, 'false');

$workflow->activate();
```
