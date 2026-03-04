<?php

namespace Aftandilmmd\WorkflowAutomation\Mcp\Prompts;

use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Name('workflow_builder')]
#[Title('Workflow Builder Guide')]
#[Description('A comprehensive guide for building workflows. Explains node types, ports, expressions, and the recommended step-by-step process.')]
class WorkflowBuilderPrompt extends Prompt
{
    public function __construct(
        protected NodeRegistry $registry,
    ) {}

    public function arguments(): array
    {
        return [
            new Argument(
                name: 'goal',
                description: "What the workflow should accomplish, e.g. 'Send welcome email when user registers'",
                required: false,
            ),
        ];
    }

    public function handle(Request $request): array
    {
        $nodes = $this->registry->all();
        $nodeList = $this->buildNodeList($nodes);

        $system = <<<PROMPT
        You are building workflows for a Laravel application using a graph-based workflow automation engine. Workflows are directed graphs where nodes perform actions and edges define execution order. Each workflow needs at least one trigger node.

        ## Available Node Types

        {$nodeList}

        ## Port System

        Ports define how data flows between nodes. When connecting nodes with connect_nodes, you specify source_port and target_port.

        Common port patterns:
        - Most nodes: input "main", output "main" (and "error" for nodes extending BaseNode)
        - IF Condition: input "main", outputs "true" and "false" — items are routed based on the condition
        - Switch: input "main", outputs "case_*" (dynamic, defined in config) and "default"
        - Loop: input "main", outputs "loop_item" (each iteration) and "loop_done" (after all items)
        - Error Handler: input "main", outputs "notify", "retry", "ignore", "stop"
        - Trigger nodes: no input ports, output "main"

        ## Expression Engine

        Use expressions in any config field marked with supports_expression. Expressions are enclosed in {{ }}.

        Syntax:
        - Access current item fields: {{ item.field_name }}
        - Access nested fields: {{ item.user.email }}
        - Access trigger data: {{ trigger.0.name }}
        - Access other node output: {{ node.{node_id}.main.0.field }}
        - String concatenation: {{ "Hello " ~ item.name }}
        - Ternary: {{ item.age >= 18 ? "adult" : "minor" }}
        - Comparisons: ==, !=, >, <, >=, <=
        - Logical: &&, ||, !
        - Math: +, -, *, /, %
        - Functions: upper(), lower(), length(), join(), split(), trim(), abs(), round(), now(), date_format(), contains(), starts_with(), ends_with(), default()

        ## Step-by-Step Process

        1. **create_workflow** — Create a new workflow with a name and optional description
        2. **add_node** — Add the trigger node first (e.g. manual, model_event, webhook, schedule)
        3. **add_node** — Add action, condition, transformer, control, and utility nodes as needed
        4. **connect_nodes** — Connect nodes by specifying source node, source port, target node, and target port
        5. **validate_workflow** — Always validate to catch missing connections, cycles, or config errors
        6. **activate_workflow** — Activate only after validation passes

        ## Best Practices

        - Always run validate_workflow before activate_workflow
        - Use list_node_types to discover available nodes and show_node_type to inspect a node's config schema
        - Give nodes meaningful names that describe their purpose
        - Every workflow must start with exactly one trigger node
        - Connect the "error" port to an error_handler node for robust workflows
        - Use set_fields to reshape data between nodes when needed
        - For conditional branching, prefer if_condition for binary choices and switch for multiple cases
        PROMPT;

        $messages = [
            Response::text($system)->asAssistant(),
        ];

        $goal = $request->get('goal');

        if ($goal) {
            $messages[] = Response::text("Build a workflow that: {$goal}");
        }

        return $messages;
    }

    protected function buildNodeList(array $nodes): string
    {
        $categories = [
            'trigger' => [],
            'action' => [],
            'condition' => [],
            'transformer' => [],
            'control' => [],
            'utility' => [],
            'code' => [],
        ];

        foreach ($nodes as $node) {
            $type = $node['type'];
            $ports = implode(', ', $node['output_ports']);
            $categories[$type][] = "  - {$node['key']} ({$node['label']}) — outputs: {$ports}";
        }

        $sections = [];

        foreach ($categories as $type => $lines) {
            if (empty($lines)) {
                continue;
            }

            $heading = ucfirst($type) . 's';
            $sections[] = "### {$heading}\n" . implode("\n", $lines);
        }

        return implode("\n\n", $sections);
    }
}
