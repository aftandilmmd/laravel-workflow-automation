<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Actions;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;

class UpdateModelNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'update_model';
    }

    /**
     * @param  class-string  $model
     */
    public function model(string $model): static
    {
        return $this->set('model', $model);
    }

    public function findBy(string $field): static
    {
        return $this->set('find_by', $field);
    }

    public function findValue(string $value): static
    {
        return $this->set('find_value', $value);
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    public function fields(array $fields): static
    {
        return $this->set('fields', $fields);
    }

    public function field(string $name, mixed $value): static
    {
        return $this->put('fields', $name, $value);
    }
}
