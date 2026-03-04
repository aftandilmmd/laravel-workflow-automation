# Laravel Workflow Automation

> [!WARNING]
> This package is under active development and is not yet recommended for production use. APIs, database schemas, and features may change.

> English | **[Türkçe](README.tr.md)**

Define multi-step business logic as visual, configurable graphs — then let Laravel execute them. Instead of scattering if/else chains, queue jobs, and event listeners across your codebase, you describe the entire flow once: trigger, conditions, actions, loops, delays. The engine handles execution, retries, logging, and human-in-the-loop pauses. Think N8N, but as a Laravel package you own and extend.

**[Full Documentation](https://laravel-workflow.pilyus.com)**

## Installation

```bash
composer require aftandilmmd/laravel-workflow-automation
php artisan vendor:publish --tag=workflow-automation-config --tag=workflow-automation-migrations
php artisan migrate
```

## Quick Start

When a user registers, send a welcome email:

```php
use Aftandilmmd\WorkflowAutomation\Models\Workflow;

$workflow = Workflow::create(['name' => 'Welcome Email']);

$trigger = $workflow->addNode('User Created', 'model_event', [
    'model'  => 'App\\Models\\User',
    'events' => ['created'],
]);

$email = $workflow->addNode('Send Welcome', 'send_mail', [
    'to'      => '{{ item.email }}',
    'subject' => 'Welcome, {{ item.name }}!',
    'body'    => 'Thanks for signing up.',
]);

$trigger->connect($email);
$workflow->activate();
```

Every `User::create()` call now triggers the workflow automatically.

## Features

- **26 Built-in Nodes** — Triggers, actions, conditions, loops, delays, AI, and more
- **Visual Editor** — Drag-and-drop React Flow canvas at `/workflow-editor`
- **Expression Engine** — `{{ item.email }}`, arithmetic, functions — no `eval()`
- **5 Trigger Types** — Manual, model event, Laravel event, webhook, cron schedule
- **Human-in-the-Loop** — Pause workflows and resume on external signal
- **Retry & Replay** — Re-run from failure point or replay with original payload
- **Custom Nodes** — One PHP class with `#[AsWorkflowNode]` attribute
- **Plugin System** — Bundle nodes, middleware, and listeners into reusable packages
- **Full REST API** — CRUD + execution endpoints for any frontend or AI agent

## Testing

```bash
composer test
```

## License

MIT
