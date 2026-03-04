<?php

namespace Aftandilmmd\WorkflowAutomation\Mcp\Tools;

use Aftandilmmd\WorkflowAutomation\Models\Workflow;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_workflows')]
#[Title('List Workflows')]
#[Description('List all workflows with their status. Returns id, name, active status, and node/edge counts.')]
#[IsReadOnly]
class ListWorkflowsTool extends Tool
{
    public function schema(JsonSchema $schema): array
    {
        return [
            'page' => $schema->integer()->description('Page number')->default(1),
            'per_page' => $schema->integer()->description('Items per page')->default(15),
        ];
    }

    public function handle(Request $request): Response
    {
        $page = $request->get('page') ?? 1;
        $perPage = $request->get('per_page') ?? 15;

        $paginator = Workflow::withCount(['nodes', 'edges'])
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(fn (Workflow $w) => [
            'id' => $w->id,
            'name' => $w->name,
            'description' => $w->description,
            'is_active' => $w->is_active,
            'nodes_count' => $w->nodes_count,
            'edges_count' => $w->edges_count,
        ])->all();

        return Response::structured([
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
