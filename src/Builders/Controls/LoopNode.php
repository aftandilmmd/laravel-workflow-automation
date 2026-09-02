<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Controls;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;

/**
 * Iterates an array field. Emits each entry on 'loop_item' and finishes on 'loop_done'.
 */
class LoopNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'loop';
    }

    public function sourceField(string $field): static
    {
        return $this->set('source_field', $field);
    }
}
