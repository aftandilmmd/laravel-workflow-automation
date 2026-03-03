# Custom Nodes

Create your own node types to extend the workflow engine with custom logic.

## Creating a Node

Create a class that implements `NodeInterface` (or extends `BaseNode`) and add the `#[AsWorkflowNode]` attribute:

```php
<?php

namespace App\Workflow\Nodes;

use Aftandilmmd\WorkflowAutomation\Attributes\AsWorkflowNode;
use Aftandilmmd\WorkflowAutomation\DTOs\NodeInput;
use Aftandilmmd\WorkflowAutomation\DTOs\NodeOutput;
use Aftandilmmd\WorkflowAutomation\Enums\NodeType;
use Aftandilmmd\WorkflowAutomation\Nodes\BaseNode;

#[AsWorkflowNode(key: 'slack_message', type: NodeType::Action, label: 'Slack Message')]
class SlackMessageNode extends BaseNode
{
    public static function configSchema(): array
    {
        return [
            ['key' => 'channel', 'type' => 'string', 'label' => 'Channel', 'required' => true, 'supports_expression' => true],
            ['key' => 'message', 'type' => 'textarea', 'label' => 'Message', 'required' => true, 'supports_expression' => true],
            ['key' => 'webhook_url', 'type' => 'string', 'label' => 'Webhook URL', 'required' => true],
        ];
    }

    public function execute(NodeInput $input, array $config): NodeOutput
    {
        $results = [];

        foreach ($input->items as $item) {
            try {
                \Illuminate\Support\Facades\Http::post($config['webhook_url'], [
                    'channel' => $config['channel'],
                    'text'    => $config['message'],
                ]);

                $results[] = array_merge($item, ['slack_sent' => true]);
            } catch (\Throwable $e) {
                return NodeOutput::ports([
                    'main'  => $results,
                    'error' => [array_merge($item, ['error' => $e->getMessage()])],
                ]);
            }
        }

        return NodeOutput::main($results);
    }
}
```

## The AsWorkflowNode Attribute

```php
#[AsWorkflowNode(
    key: 'slack_message',       // Unique identifier used in addNode()
    type: NodeType::Action,     // Category for UI grouping
    label: 'Slack Message',     // Human-readable label
)]
```

| Parameter | Type | Description |
|-----------|------|-------------|
| `key` | string | Unique key for this node type |
| `type` | NodeType | `Trigger`, `Action`, `Condition`, `Transformer`, `Control`, `Utility`, or `Code` |
| `label` | string | Display name |

## NodeInterface

Every node must implement `NodeInterface`:

```php
interface NodeInterface
{
    public function inputPorts(): array;    // e.g. ['main']
    public function outputPorts(): array;   // e.g. ['main', 'error']
    public static function configSchema(): array;
    public function execute(NodeInput $input, array $config): NodeOutput;
}
```

The `BaseNode` class provides sensible defaults: input `['main']`, output `['main', 'error']`, and an empty config schema.

## NodeInput

```php
class NodeInput
{
    public readonly array $items;              // Array of items to process
    public readonly ExecutionContext $context;  // Run context (IDs, outputs)
}
```

## NodeOutput

Create output using static methods:

```php
// Send all items to the 'main' port
NodeOutput::main($items);

// Send items to a specific port
NodeOutput::port('custom_port', $items);

// Send items to multiple ports
NodeOutput::ports([
    'main'  => $successItems,
    'error' => $errorItems,
]);
```

## Config Schema

The config schema defines what fields appear in the UI and validates configuration:

```php
public static function configSchema(): array
{
    return [
        [
            'key'                 => 'field_name',
            'type'                => 'string',       // string, textarea, integer, boolean, select, multiselect, json, keyvalue, array_of_objects, workflow_select
            'label'               => 'Display Label',
            'required'            => true,
            'supports_expression' => true,           // Whether {{ }} is resolved
            'options'             => ['a', 'b'],     // For select/multiselect types
            'schema'              => [...],           // For array_of_objects type
        ],
    ];
}
```

## Registering Custom Nodes

### Auto-Discovery

Add your node directory to the config:

```php
// config/workflow-automation.php
'node_discovery' => [
    'app_paths' => [
        app_path('Workflow/Nodes'),
    ],
],
```

The package scans these directories for classes with the `#[AsWorkflowNode]` attribute.

### Manual Registration

Register in a service provider:

```php
use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;

public function boot(): void
{
    $registry = app(NodeRegistry::class);
    $registry->discoverNodes(app_path('Workflow/Nodes'));
}
```

## Creating a Trigger

Triggers implement `TriggerInterface` instead of `NodeInterface`:

```php
use Aftandilmmd\WorkflowAutomation\Contracts\TriggerInterface;

#[AsWorkflowNode(key: 'my_trigger', type: NodeType::Trigger, label: 'My Trigger')]
class MyTrigger implements TriggerInterface
{
    public function inputPorts(): array { return []; }      // Triggers have no input
    public function outputPorts(): array { return ['main']; }

    public static function configSchema(): array { return []; }

    public function register(int $workflowId, int $nodeId, array $config): void
    {
        // Called when the workflow is activated
    }

    public function unregister(int $workflowId, int $nodeId, array $config): void
    {
        // Called when the workflow is deactivated
    }

    public function extractPayload(mixed $event): array
    {
        // Convert the triggering event to an items array
        return is_array($event) ? $event : [[]];
    }

    public function execute(NodeInput $input, array $config): NodeOutput
    {
        return NodeOutput::main($input->items);
    }
}
```

## Using Your Custom Node

```php
$workflow = Workflow::create(['name' => 'Alert Pipeline']);

$trigger = $workflow->addNode('New Alert', 'manual');
$slack   = $workflow->addNode('Notify Team', 'slack_message', [
    'channel'     => '#alerts',
    'message'     => 'Alert: {{ item.message }}',
    'webhook_url' => 'https://hooks.slack.com/services/...',
]);

$trigger->connect($slack);
$workflow->activate();
```

## Dependency Injection

Custom nodes support constructor injection from the Laravel container:

```php
#[AsWorkflowNode(key: 'ai_classify', type: NodeType::Action, label: 'AI Classify')]
class AiClassifyNode extends BaseNode
{
    public function __construct(
        private readonly MyAiService $ai,
    ) {}

    public function execute(NodeInput $input, array $config): NodeOutput
    {
        $results = [];
        foreach ($input->items as $item) {
            $category = $this->ai->classify($item['text']);
            $results[] = array_merge($item, ['category' => $category]);
        }
        return NodeOutput::main($results);
    }
}
```
