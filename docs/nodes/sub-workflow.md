# Sub Workflow

Triggers another workflow from within the current one. Enables reusable, modular workflow composition.

**Node key:** `sub_workflow` · **Type:** Control

## Config

| Key | Type | Required | Expression | Description |
| --- | --- | --- | --- | --- |
| `workflow_id` | workflow_select | Yes | No | ID of the sub-workflow to trigger |
| `pass_items` | boolean | No | No | Pass current items as the sub-workflow's payload |
| `wait_for_result` | boolean | No | No | Execute synchronously and wait for completion |

## Ports

| Direction | Port | Description |
| --- | --- | --- |
| Input | `main` | Items to process |
| Output | `main` | Items after sub-workflow execution |
| Output | `error` | Items when the sub-workflow fails |

## Behavior

| Mode | What Happens |
| --- | --- |
| **Async** (default) | Sub-workflow is dispatched to the queue; current items forwarded immediately |
| **Sync** (`wait_for_result: true`) | Sub-workflow executes inline; result returned to `main` port |

Each invocation creates its own `WorkflowRun` record.

## Example

```php
$sub = $workflow->addNode('Notify', 'sub_workflow', [
    'workflow_id'     => 42,
    'pass_items'      => true,
    'wait_for_result' => false,
]);
```

## Tips

- Sub-workflows have independent run records for separate tracking and debugging
- Use `wait_for_result: true` only for short-running sub-workflows
- Sub-workflow errors are captured on `error` when running synchronously
