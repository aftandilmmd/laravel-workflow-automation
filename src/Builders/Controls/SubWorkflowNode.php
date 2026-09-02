<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Controls;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Illuminate\Database\Eloquent\Model;

class SubWorkflowNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'sub_workflow';
    }

    public function workflowId(int|Model $workflow): static
    {
        return $this->set('workflow_id', $workflow instanceof Model ? $workflow->getKey() : $workflow);
    }

    public function workflow(int|Model $workflow): static
    {
        return $this->workflowId($workflow);
    }

    public function passItems(bool $pass = true): static
    {
        return $this->set('pass_items', $pass);
    }

    public function waitForResult(bool $wait = true): static
    {
        return $this->set('wait_for_result', $wait);
    }
}
