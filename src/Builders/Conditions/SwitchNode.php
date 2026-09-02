<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Conditions;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\ConditionOperator;
use Aftandilmmd\WorkflowAutomation\Enums\Operator;

/**
 * Routes items to a port per matching case, plus a 'default' port.
 */
class SwitchNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'switch';
    }

    public function field(string $field): static
    {
        return $this->set('field', $field);
    }

    /**
     * @param  array<int, array{port: string, operator: ConditionOperator|string, value: mixed}>  $cases
     */
    public function cases(array $cases): static
    {
        return $this->set('cases', $cases);
    }

    /**
     * Add one case. The port name is where matching items are routed.
     */
    public function case(string $port, ConditionOperator|Operator|string $operator, mixed $value = null): static
    {
        return $this->push('cases', ['port' => $port, 'operator' => $operator, 'value' => $value]);
    }

    /**
     * Route unmatched items to the 'default' port.
     */
    public function fallthrough(bool $fallthrough = true): static
    {
        return $this->set('fallthrough', $fallthrough);
    }
}
