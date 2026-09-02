<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Transformers;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;

class SetFieldsNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'set_fields';
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
        return $this->putEntry('fields', $name, $value);
    }

    public function keepExisting(bool $keep = true): static
    {
        return $this->set('keep_existing', $keep);
    }
}
