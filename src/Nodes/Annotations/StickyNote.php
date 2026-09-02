<?php

namespace Aftandilmmd\WorkflowAutomation\Nodes\Annotations;

use Aftandilmmd\WorkflowAutomation\Attributes\AsWorkflowNode;
use Aftandilmmd\WorkflowAutomation\Builders\Annotations\StickyNoteNode;
use Aftandilmmd\WorkflowAutomation\DTOs\NodeInput;
use Aftandilmmd\WorkflowAutomation\DTOs\NodeOutput;
use Aftandilmmd\WorkflowAutomation\Enums\NodeType;
use Aftandilmmd\WorkflowAutomation\Enums\StickyColor;
use Aftandilmmd\WorkflowAutomation\Nodes\BaseNode;

#[AsWorkflowNode(key: 'sticky_note', type: NodeType::Annotation, label: 'Sticky Note', builder: StickyNoteNode::class)]
class StickyNote extends BaseNode
{
    public static function configSchema(): array
    {
        return [
            ['key' => 'content', 'type' => 'textarea', 'label' => 'Content'],
            ['key' => 'color', 'type' => 'select', 'label' => 'Color', 'options' => array_column(StickyColor::cases(), 'value'), 'default' => StickyColor::Yellow->value],
        ];
    }

    public function inputPorts(): array
    {
        return [];
    }

    public function outputPorts(): array
    {
        return [];
    }

    public function execute(NodeInput $input, array $config): NodeOutput
    {
        return NodeOutput::main($input->items);
    }
}
