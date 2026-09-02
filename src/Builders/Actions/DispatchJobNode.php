<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Actions;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;

class DispatchJobNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'dispatch_job';
    }

    /**
     * @param  class-string  $jobClass
     */
    public function jobClass(string $jobClass): static
    {
        return $this->set('job_class', $jobClass);
    }

    public function queue(string $queue): static
    {
        return $this->set('queue', $queue);
    }

    public function delay(int $seconds): static
    {
        return $this->set('delay', $seconds);
    }

    public function withItem(bool $withItem = true): static
    {
        return $this->set('with_item', $withItem);
    }
}
