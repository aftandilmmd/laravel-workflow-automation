<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Triggers;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;

class EventTriggerNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'event';
    }

    /**
     * @param  class-string  $eventClass
     */
    public function eventClass(string $eventClass): static
    {
        return $this->set('event_class', $eventClass);
    }
}
