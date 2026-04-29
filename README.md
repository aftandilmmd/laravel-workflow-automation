<div align="center">

# Laravel Workflow Automation

**A visual, n8n-style workflow engine that lives inside your Laravel app.**

Build automation flows by dragging nodes onto a canvas — or describe them in plain English and let AI build them for you. No new infrastructure, no external service. Just `composer require` and open `/workflow-editor`.

[![Laravel Compatibility](https://badge.laravel.cloud/badge/aftandilmmd/laravel-workflow-automation)](https://packagist.org/packages/aftandilmmd/laravel-workflow-automation)
[![Documentation](https://img.shields.io/badge/docs-laravel--workflow.pilyus.com-blue)](https://laravel-workflow.pilyus.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

English | **[Türkçe](README.tr.md)**

</div>

> [!WARNING]
> This package is under active development and is not yet recommended for production use. APIs, database schemas, and features may change.

![Workflow Editor](docs/screenshots/workflow-editor.png)

---

## Why this package

Every Laravel app eventually grows a tangle of automation logic — fraud checks, drip emails, approval flows, webhook routers, cron jobs. It starts in a controller, spreads to listeners, leaks into jobs, and ends as a graveyard of `if`s nobody dares to touch.

This package moves that logic out of your code and into a **visual graph stored in your database**:

- **Your controllers stay clean** — automation lives in workflows, not models.
- **Non-developers can ship rules** — product, ops, and support edit flows in the browser.
- **AI agents become first-class** — describe a flow in chat, the agent builds it via the REST API or MCP server.
- **Every run is observable** — per-node input, output, duration, and errors, with replay.

Think n8n — but as a Laravel package you own, extend, and host yourself.

## Highlights

### Visual editor — `/workflow-editor`

A full React + React Flow editor ships with the package. No extra install, no separate service. Drag nodes from the palette, connect ports, configure them through dynamic forms, hit **Run**, and watch the graph light up in real time.

- Drag-and-drop canvas with zoom, pan, multi-select
- Auto-generated config forms from each node's schema (18+ field types: code editors, JSON, key-value, model pickers, sliders, color, conditional `show_when`, …)
- **Pin** node outputs for repeatable testing — skip expensive HTTP/AI calls during development
- Per-run timeline with status, duration, expandable I/O, replay, cancel
- Dark/light themes, folders, tags, search

### AI Builder — describe it, agent builds it

Open a workflow → click **AI** → type *"When a user signs up, wait 3 days, check usage, send onboarding or reminder email."* The agent streams its plan and builds the nodes and edges live on the canvas through the package's MCP tools.

Works with OpenAI, Anthropic, Gemini, Groq, Mistral, DeepSeek, Ollama, xAI, and Cohere out of the box.

### 26 built-in nodes

| Category | Nodes |
|---|---|
| **Triggers** | Manual · Model Event · Laravel Event · Schedule · Webhook · Sub-workflow |
| **Actions** | Send Mail · HTTP Request · Update Model · Dispatch Job · Send Notification · Run Command · AI |
| **Logic** | If · Switch · Loop · Merge · Filter · Aggregate |
| **Flow Control** | Delay · Wait/Resume · Sub-workflow · Error Handler |
| **Data** | Set Fields · Parse Data · Code (expression-only) |

### One class = one custom node

```php
#[AsWorkflowNode(key: 'notify_crm', name: 'Notify CRM', type: NodeType::Action)]
class NotifyCrmNode extends BaseNode
{
    public function execute(WorkflowNodeRun $nodeRun, array $input): array
    {
        return ['response' => Http::post($this->config('url'), $input)->json()];
    }
}
```

Auto-discovered, instantly available in the visual editor, REST API, and AI builder.

### Safe expression engine

Custom recursive-descent parser — **no `eval()`, no closures, no arbitrary PHP**. Use `{{ item.email }}`, arithmetic, ternaries, and 30+ helpers (`upper`, `lower`, `now`, `json`, `count`, …) in any config field.

### Human-in-the-loop, retries, observability

- **Wait/Resume** node pauses a run pending external approval — resume via REST or PHP with arbitrary payload.
- **Replay** any run with the original or modified input. Retry individual failed nodes.
- Every run records per-node status, duration, full input/output JSON, and errors.

### Built for AI agents and external tools

- **REST API** for full CRUD, execution, registry, runs, folders, tags.
- **MCP server** with first-class tools an LLM can call directly.
- **Schema validation** middleware ensures inputs and outputs always match each node's contract.

## Installation

```bash
composer require aftandilmmd/laravel-workflow-automation
php artisan vendor:publish --tag=workflow-automation-config --tag=workflow-automation-migrations
php artisan migrate
```

Open `http://your-app.test/workflow-editor` and you're done.

## Quick taste

A complete welcome-email workflow, defined fluently:

```php
$workflow = Workflow::create(['name' => 'Welcome Email']);

$workflow
    ->addNode('User Created', 'model_event', ['model' => User::class, 'events' => ['created']])
    ->connect($workflow->addNode('Send Welcome', 'send_mail', [
        'to'      => '{{ item.email }}',
        'subject' => 'Welcome, {{ item.name }}!',
        'body'    => 'Thanks for signing up.',
    ]));

$workflow->activate();
```

…or build the same thing in the editor, in 20 seconds, without writing a line of PHP.

## Documentation

Full guides, node references, examples, and recipes:

**[laravel-workflow.pilyus.com](https://laravel-workflow.pilyus.com)**

- [Why use this?](https://laravel-workflow.pilyus.com/getting-started/why-use-this) — the full pitch
- [Visual editor](https://laravel-workflow.pilyus.com/ui-editor) — every panel, field type, and shortcut
- [AI builder](https://laravel-workflow.pilyus.com/ai-builder) — provider setup and MCP tools
- [Custom nodes](https://laravel-workflow.pilyus.com/advanced/custom-nodes) and [Plugins](https://laravel-workflow.pilyus.com/advanced/plugins)
- [Examples](https://laravel-workflow.pilyus.com/examples/user-onboarding) — onboarding, Stripe webhooks, drip campaigns, approvals, scheduled reports

## Requirements

- PHP 8.3+
- Laravel 10, 11, 12, or 13
- Zero dependencies beyond `illuminate/*`

## Testing

```bash
composer test
```

## License

MIT © [aftandilmmd](https://github.com/aftandilmmd)
