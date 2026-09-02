# Array API (low-level)

Nodes can also be added with a node key and a plain config array.

```php
$workflow->addNode('Welcome Email', 'send_mail', [
    'to'      => '{{ item.email }}',
    'subject' => 'Welcome, {{ item.name }}!',
    'body'    => 'Thanks for signing up.',
]);
```

::: info Supported, and staying
This API is fully supported and there are no plans to remove it — the editor, the REST API
and the MCP server all use it internally. For new PHP code, prefer the
[Node Builder API](./node-builders.md): it gives you autocomplete, enums, and validation
before the node is saved.
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
$mail = $workflow->addNode(SendMailNode::make()->to('...')->subject('...')->body('...'));
```

## Differences from the builder API

| | Array API | Builder API |
| --- | --- | --- |
| Autocomplete | No | Yes |
| Fixed values | Raw strings | Enums (strings still accepted) |
| Config validation on save | No | Yes, against the node's config schema |
| Unknown keys | Stored as-is | Rejected with a suggestion |

Config keys are not validated in the array API on purpose: existing workflows and seeds may
carry extra keys, and rejecting them would break them.

## Config keys

Every node's config keys are documented on its own page under
[Nodes](../nodes/send-mail.md) and [Triggers](../triggers/manual.md). At runtime you can
read the same information from the registry:

```php
use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;

$schema = app(NodeRegistry::class)->getMeta('send_mail')['class']::configSchema();
```
