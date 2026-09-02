<div v-pre>

# Workflow Trigger

The `workflow` trigger fires automatically when another workflow completes or fails. This enables **workflow chaining** — connecting workflows together so the output of one becomes the input of another.

**Node key:** `workflow`

## PHP Builder

```php
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\WorkflowTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\WorkflowTriggerOn;

WorkflowTriggerNode::make()
    ->title('After Import')
    ->sourceWorkflow($importWorkflow)
    ->triggerOn(WorkflowTriggerOn::Completed);
```

See [Node Builders](../api/node-builders.md) for the conventions shared by all builders.

## Config

| Key | Type | Required | Expression | Description |
| --- | --- | --- | --- | --- |
| `source_workflow_id` | workflow_select | No | No | Source workflow to listen to (leave empty to listen to any workflow) |
| `trigger_on` | select | Yes | No | When to trigger: `completed`, `failed`, or `any` |

## Ports

| Direction | Port | Description |
| --- | --- | --- |
| Output | `main` | Source workflow's metadata and output data |

## Output Data

When the trigger fires, it outputs a single item with this structure:

```json
{
  "source_workflow_id": 1,
  "source_run_id": 42,
  "source_status": "completed",
  "error_message": null,
  "data": { /* source workflow's full context/output */ }
}
```

## How It Works

```text
Workflow A completes
        │
        ▼ (WorkflowCompleted event)
  ┌───────────────────────┐
  │ WorkflowChainListener │
  └──────┬────────────────┘
         │ matches trigger config?
         ▼
  ┌──────────────────────┐
  │ Workflow Trigger (B) │
  └──────┬───────────────┘
         │ main
         ▼
  [{ source_workflow_id, source_run_id, source_status, data }]
```

## Example: Order → Shipping → Invoice

Three independent workflows chained together:

```php
use Aftandilmmd\WorkflowAutomation\Builders\Actions\HttpRequestNode;
use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendMailNode;
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\SetFieldsNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\WebhookTriggerNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\WorkflowTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\HttpMethod;
use Aftandilmmd\WorkflowAutomation\Enums\WorkflowTriggerOn;

// 1. Order Processing Workflow (runs first)
$orderWf = Workflow::create(['name' => 'Process Order']);
$orderTrigger = $orderWf->addNode(
    WebhookTriggerNode::make()
        ->title('New Order')
        ->method(HttpMethod::Post)
);
$processOrder = $orderWf->addNode(
    SetFieldsNode::make()
        ->title('Process')
        ->fields(['status' => 'processed'])
);
$orderTrigger->connect($processOrder);
$orderWf->activate();

// 2. Shipping Workflow (triggered when order completes)
$shippingWf = Workflow::create(['name' => 'Ship Order']);
$shippingTrigger = $shippingWf->addNode(
    WorkflowTriggerNode::make()
        ->title('Order Done')
        ->sourceWorkflowId($orderWf->id)
        ->triggerOn(WorkflowTriggerOn::Completed)
);
$ship = $shippingWf->addNode(
    HttpRequestNode::make()
        ->title('Ship')
        ->url('https://shipping-api.com/create')
        ->method(HttpMethod::Post)
        ->body('{{ item.data }}')
);
$shippingTrigger->connect($ship);
$shippingWf->activate();

// 3. Invoice Workflow (triggered when shipping completes)
$invoiceWf = Workflow::create(['name' => 'Send Invoice']);
$invoiceTrigger = $invoiceWf->addNode(
    WorkflowTriggerNode::make()
        ->title('Shipping Done')
        ->sourceWorkflowId($shippingWf->id)
        ->triggerOn(WorkflowTriggerOn::Completed)
);
$invoice = $invoiceWf->addNode(
    SendMailNode::make()
        ->title('Send Invoice')
        ->to('{{ item.data.customer_email }}')
        ->subject('Your invoice')
        ->body('Order has been shipped and invoiced.')
);
$invoiceTrigger->connect($invoice);
$invoiceWf->activate();
```

## Example: Error Handling Workflow

A dedicated workflow that runs whenever any workflow fails:

```php
use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendNotificationNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\WorkflowTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\WorkflowTriggerOn;

$errorHandler = Workflow::create(['name' => 'Global Error Handler']);

$trigger = $errorHandler->addNode(
    WorkflowTriggerNode::make()
        ->title('Any Failure')
        ->sourceWorkflowId(null)
        ->triggerOn(WorkflowTriggerOn::Failed)
);

$notify = $errorHandler->addNode(
    SendNotificationNode::make()
        ->title('Alert Team')
        ->notificationClass(WorkflowFailed::class)
        ->notifiableClass(User::class)
        ->notifiableId('{{ item.owner_id }}')
);

$trigger->connect($notify);
$errorHandler->activate();
```

## Chain Depth Protection

To prevent infinite chain loops (A → B → A → B → ...), a maximum depth is enforced. The default limit is 10 levels.

Configure it in `config/workflow-automation.php`:

```php
'chaining' => [
    'max_depth' => 10, // or env('WORKFLOW_CHAIN_MAX_DEPTH', 10)
],
```

When the limit is reached, the chain stops and a warning is logged.

## Self-Triggering Prevention

A workflow cannot trigger itself. If workflow A has a workflow trigger listening to workflow A, it will be silently skipped. Use the [Loop node](/nodes/loop) for repetitive execution within a workflow.

## Caching

Active workflow triggers are cached for 60 seconds. After creating or modifying a workflow trigger:

- Wait up to 60 seconds for automatic cache refresh, or
- Clear manually: `Cache::forget('workflow:workflow_triggers')`

## Advanced: Workflow Chaining

The Workflow Trigger is one of two mechanisms for connecting workflows together. For a comprehensive comparison of both approaches (Workflow Trigger vs Sub Workflow), decision guides, and advanced patterns, see the **[Workflow Chaining](/advanced/workflow-chaining)** guide.

## Tips

- Chained workflows are always dispatched **asynchronously** via the queue
- For synchronous parent-child execution, use the [Sub Workflow](/nodes/sub-workflow) control node instead
- Set `source_workflow_id` to `null` to create a "catch-all" trigger that responds to any workflow
- Use `trigger_on: failed` to build error handling workflows
- Access the source workflow's output via `{{ item.data }}` in expressions
- Each chained workflow creates its own independent `WorkflowRun` record

</div>
