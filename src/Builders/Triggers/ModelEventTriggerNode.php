<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Triggers;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\ModelEvent;

class ModelEventTriggerNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'model_event';
    }

    /**
     * @param  class-string  $model
     */
    public function model(string $model): static
    {
        return $this->set('model', $model);
    }

    /**
     * @param  array<int, ModelEvent|string>  $events
     */
    public function events(array $events): static
    {
        return $this->set('events', $events);
    }

    public function event(ModelEvent|string $event): static
    {
        return $this->push('events', $event);
    }

    /**
     * Only fire on update when one of these fields changed. Empty means any field.
     *
     * @param  string[]  $fields
     */
    public function onlyFields(array $fields): static
    {
        return $this->set('only_fields', $fields);
    }
}
