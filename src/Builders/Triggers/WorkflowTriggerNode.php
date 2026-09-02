<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Triggers;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\WorkflowTriggerOn;
use Illuminate\Database\Eloquent\Model;

class WorkflowTriggerNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'workflow';
    }

    /**
     * Listen to a single workflow. Leave unset to listen to any workflow.
     */
    public function sourceWorkflowId(int|Model $workflow): static
    {
        return $this->set('source_workflow_id', $workflow instanceof Model ? $workflow->getKey() : $workflow);
    }

    public function sourceWorkflow(int|Model $workflow): static
    {
        return $this->sourceWorkflowId($workflow);
    }

    public function triggerOn(WorkflowTriggerOn|string $trigger): static
    {
        return $this->set('trigger_on', $trigger);
    }
}
