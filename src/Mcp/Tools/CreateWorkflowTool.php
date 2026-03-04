<?php

namespace Aftandilmmd\WorkflowAutomation\Mcp\Tools;

use Aftandilmmd\WorkflowAutomation\Services\WorkflowService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('create_workflow')]
#[Title('Create Workflow')]
#[Description('Create a new workflow. After creating, add nodes with add_node and connect them with connect_nodes.')]
class CreateWorkflowTool extends Tool
{
    public function __construct(
        protected WorkflowService $service,
    ) {}

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('Workflow name'),
            'description' => $schema->string()->description('Workflow description'),
        ];
    }

    public function handle(Request $request): Response
    {
        $workflow = $this->service->create([
            'name' => $request->get('name'),
            'description' => $request->get('description'),
        ]);

        return Response::structured([
            'workflow' => [
                'id' => $workflow->id,
                'name' => $workflow->name,
                'description' => $workflow->description,
                'is_active' => $workflow->is_active,
            ],
        ]);
    }
}
