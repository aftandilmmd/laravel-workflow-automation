<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Triggers;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;

class ManualTriggerNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'manual';
    }

    /**
     * Shape of the payload this workflow expects, used for editor hints.
     *
     * @param  array<string, mixed>  $schema
     */
    public function inputSchema(array $schema): static
    {
        return $this->set('input_schema', $schema);
    }
}
