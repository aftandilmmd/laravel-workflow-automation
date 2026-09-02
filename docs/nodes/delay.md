# Delay

Pauses a workflow run for a specified duration before continuing. Uses Laravel's queue for reliable scheduling.

**Node key:** `delay` · **Type:** Control

## PHP Builder

```php
use Aftandilmmd\WorkflowAutomation\Builders\Controls\DelayNode;
use Aftandilmmd\WorkflowAutomation\Enums\DelayUnit;

DelayNode::minutes(15)
    ->title('Cooldown');   // also ::seconds(), ::hours()

DelayNode::make()
    ->delayType(DelayUnit::Hours)
    ->delayValue(2);
```

See [Node Builders](../api/node-builders.md) for the conventions shared by all builders.

## Config

| Key | Type | Required | Expression | Description |
| --- | --- | --- | --- | --- |
| `delay_type` | select | Yes | No | Unit: `seconds`, `minutes`, or `hours` |
| `delay_value` | integer | Yes | No | Amount of time to delay |

## Ports

| Direction | Port | Description |
| --- | --- | --- |
| Input | `main` | Items to delay |
| Output | `main` | Items forwarded after the delay |

## Behavior

1. Sets the run status to `waiting`
2. Dispatches a `ResumeWorkflowJob` scheduled after the configured duration
3. The workflow engine exits — no blocking or sleeping
4. When the job fires, the run resumes from the delay node
5. Items are forwarded to `main` unchanged

If `delay_value` is zero or negative, items pass through immediately.

## Example

```php
use Aftandilmmd\WorkflowAutomation\Builders\Controls\DelayNode;
use Aftandilmmd\WorkflowAutomation\Enums\DelayUnit;

$delay = $workflow->addNode(
    DelayNode::make()
        ->title('Wait 1 Hour')
        ->delayType(DelayUnit::Hours)
        ->delayValue(1)
);
```

## Full Workflow: Email Drip

```php
$trigger->connect($welcomeEmail);
$welcomeEmail->connect($delay3Days);     // delay 3 days
$delay3Days->connect($followUpEmail);
$followUpEmail->connect($delay7Days);     // delay 7 days
$delay7Days->connect($finalEmail);
```

## Tips

- A queue worker must be running (`php artisan queue:work`) for the delayed job to fire
- The run status becomes `waiting` — query paused runs: `WorkflowRun::where('status', 'waiting')`
- Zero/negative values pass items through immediately (useful for conditional delays)
