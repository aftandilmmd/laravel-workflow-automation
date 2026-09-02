<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Conditions;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\ConditionOperator;
use Aftandilmmd\WorkflowAutomation\Enums\Operator;

/**
 * Routes items to the 'true' or 'false' port.
 */
class IfConditionNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'if_condition';
    }

    public static function when(string $field, ConditionOperator|Operator|string $operator, mixed $value = null): static
    {
        return static::make()->field($field)->operator($operator)->value($value);
    }

    public function field(string $field): static
    {
        return $this->set('field', $field);
    }

    /**
     * @param  ConditionOperator|Operator|string  $operator  Operator is deprecated; prefer ConditionOperator.
     */
    public function operator(ConditionOperator|Operator|string $operator): static
    {
        return $this->set('operator', $operator);
    }

    public function value(mixed $value): static
    {
        return $this->set('value', $value);
    }
}
