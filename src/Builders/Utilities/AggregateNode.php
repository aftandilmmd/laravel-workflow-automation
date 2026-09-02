<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Utilities;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\AggregateFunction;

class AggregateNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'aggregate';
    }

    /**
     * Leave unset to aggregate all items into a single row.
     */
    public function groupBy(string $field): static
    {
        return $this->set('group_by', $field);
    }

    /**
     * @param  array<int, array{field: string, function: AggregateFunction|string, alias: string}>  $operations
     */
    public function operations(array $operations): static
    {
        return $this->set('operations', $operations);
    }

    public function operation(string $field, AggregateFunction|string $function, string $alias): static
    {
        return $this->push('operations', ['field' => $field, 'function' => $function, 'alias' => $alias]);
    }
}
