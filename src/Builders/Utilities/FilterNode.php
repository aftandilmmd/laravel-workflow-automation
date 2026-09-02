<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Utilities;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\ConditionOperator;
use Aftandilmmd\WorkflowAutomation\Enums\FilterLogic;

class FilterNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'filter';
    }

    /**
     * @param  array<int, array{field: string, operator: ConditionOperator|string, value: mixed}>  $conditions
     */
    public function conditions(array $conditions): static
    {
        return $this->set('conditions', $conditions);
    }

    public function condition(string $field, ConditionOperator|string $operator, mixed $value = null): static
    {
        return $this->push('conditions', ['field' => $field, 'operator' => $operator, 'value' => $value]);
    }

    public function logic(FilterLogic|string $logic): static
    {
        return $this->set('logic', $logic);
    }
}
