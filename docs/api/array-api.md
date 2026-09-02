# Array API (low-level)

Nodes can also be added with a node key and a plain config array.

```php
$workflow->addNode('Welcome Email', 'send_mail', [
    'to'      => '{{ item.email }}',
    'subject' => 'Welcome, {{ item.name }}!',
    'body'    => 'Thanks for signing up.',
]);
```

::: warning Not recommended
Use the [Node Builder API](./node-builders.md) instead: autocomplete, enums, and config
validation before the node is saved. The array API still works and existing code keeps
running, but it will likely be removed in a future release.
:::

## Signatures

```php
// Workflow model
$workflow->addNode(string $name, string $nodeKey, array $config = []): WorkflowNode

// WorkflowNode model — adds another node to the same workflow
$node->addNode(string $name, string $nodeKey, array $config = []): WorkflowNode

// Service / facade
Workflow::addNode(int|Workflow $workflow, string $nodeKey, array $config = [], ?string $name = null): WorkflowNode
```

The same methods accept a builder as the first argument, so the two styles can be mixed in
one workflow:

```php
$trigger = $workflow->addNode('Start', 'manual');
$mail = $workflow->addNode(
    SendMailNode::make()
        ->to('...')
        ->subject('...')
        ->body('...')
);
```

## Differences from the builder API

| | Array API | Builder API |
| --- | --- | --- |
| Autocomplete | No | Yes |
| Fixed values | Raw strings | Enums (strings still accepted) |
| Config validation on save | No | Yes, against the node's config schema |
| Unknown keys | Stored as-is | Rejected with a suggestion |

Config keys are not validated in the array API: whatever you pass is stored as-is.

## Config keys

Every node's config keys are documented on its own page under
[Nodes](../nodes/send-mail.md) and [Triggers](../triggers/manual.md). At runtime you can
read the same information from the registry:

```php
use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;

$schema = app(NodeRegistry::class)->getMeta('send_mail')['class']::configSchema();
```
