<div v-pre>

# Quick Start

Build a working workflow in under 5 minutes.

## Goal

Create a workflow that sends a welcome email when triggered manually.

## Step 1 — Create the Workflow

```php
use Aftandilmmd\WorkflowAutomation\Models\Workflow;

$workflow = Workflow::create([
    'name' => 'Welcome Email',
    'description' => 'Sends a welcome email to new users',
]);
```

## Step 2 — Add Nodes

```php
// Trigger — receives the initial payload
$trigger = $workflow->addNode('Start', 'manual');

// Action — sends the email
$sendMail = $workflow->addNode('Send Welcome', 'send_mail', [
    'to'      => '{{ item.email }}',
    'subject' => 'Welcome, {{ item.name }}!',
    'body'    => 'Hi {{ item.name }}, thanks for joining us!',
]);
```

The `{{ item.field }}` syntax is the [Expression Engine](/expressions/) — it resolves values from the current data item at runtime.

## Step 3 — Connect Nodes

```php
$trigger->connect($sendMail);
```

This creates an edge from the trigger's `main` output port to the send mail node's `main` input port.

## Step 4 — Activate & Run

```php
$workflow->activate();

$run = $workflow->start([
    ['name' => 'Alice', 'email' => 'alice@example.com'],
]);
```

The `start()` method accepts an array of items. Each item flows through the graph independently.

## Step 5 — Check the Result

```php
echo $run->status->value; // "completed"

foreach ($run->nodeRuns as $nodeRun) {
    echo "{$nodeRun->node->name}: {$nodeRun->status->value}";
    echo " ({$nodeRun->duration_ms}ms)\n";
}
```

## What Happened

```
┌──────────┐      ┌───────────────┐
│  Manual  │─────▶│  Send Welcome │
│ Trigger  │ main │    (email)    │
└──────────┘      └───────────────┘
```

1. **Manual Trigger** received `[{name: "Alice", email: "alice@example.com"}]`
2. Items flowed through the `main` port to **Send Welcome**
3. The expression engine resolved `{{ item.email }}` to `alice@example.com`
4. Laravel's Mail facade sent the email
5. The run completed

## Using the Facade

```php
use Aftandilmmd\WorkflowAutomation\Facades\Workflow;

$wf = Workflow::create(['name' => 'Welcome Email']);

$trigger  = Workflow::addNode($wf, 'manual', name: 'Start');
$sendMail = Workflow::addNode($wf, 'send_mail', [
    'to'      => '{{ item.email }}',
    'subject' => 'Welcome, {{ item.name }}!',
    'body'    => 'Hi {{ item.name }}, thanks for joining!',
], name: 'Send Welcome');

Workflow::connect($trigger, $sendMail);
Workflow::activate($wf);
Workflow::run($wf, [['name' => 'Alice', 'email' => 'alice@example.com']]);
```

## Using the REST API

```bash
# Create workflow
curl -X POST /workflow-engine/workflows \
  -H "Content-Type: application/json" \
  -d '{"name": "Welcome Email"}'

# Add trigger node
curl -X POST /workflow-engine/workflows/1/nodes \
  -H "Content-Type: application/json" \
  -d '{"node_key": "manual", "name": "Start"}'

# Add action node
curl -X POST /workflow-engine/workflows/1/nodes \
  -H "Content-Type: application/json" \
  -d '{
    "node_key": "send_mail",
    "name": "Send Welcome",
    "config": {
      "to": "{{ item.email }}",
      "subject": "Welcome!",
      "body": "Hi {{ item.name }}!"
    }
  }'

# Connect nodes
curl -X POST /workflow-engine/workflows/1/edges \
  -H "Content-Type: application/json" \
  -d '{"source_node_id": 1, "target_node_id": 2}'

# Activate & run
curl -X POST /workflow-engine/workflows/1/activate
curl -X POST /workflow-engine/workflows/1/run \
  -H "Content-Type: application/json" \
  -d '{"payload": [{"name": "Alice", "email": "alice@example.com"}]}'
```

## Next Steps

- [Concepts](/getting-started/concepts) — understand workflows, nodes, edges, items, and execution
- [Triggers](/triggers/manual) — learn about the 4 trigger types
- [Nodes](/nodes/send-mail) — explore all 22 built-in nodes
- [Examples](/examples/ecommerce-order) — see real-world workflow patterns


</div>
