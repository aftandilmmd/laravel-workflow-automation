<?php

namespace Aftandilmmd\WorkflowAutomation\Enums;

/**
 * @deprecated Use ConditionOperator instead. Kept for backward compatibility; values are identical.
 */
enum Operator: string
{
    case Equals         = 'equals';
    case NotEquals      = 'not_equals';
    case Contains       = 'contains';
    case NotContains    = 'not_contains';
    case GreaterThan    = 'greater_than';
    case LessThan       = 'less_than';
    case GreaterOrEqual = 'greater_or_equal';
    case LessOrEqual    = 'less_or_equal';
    case IsEmpty        = 'is_empty';
    case IsNotEmpty     = 'is_not_empty';
    case StartsWith     = 'starts_with';
    case EndsWith       = 'ends_with';

    public function evaluate(mixed $fieldValue, mixed $compareValue = null): bool
    {
        return ConditionOperator::from($this->value)->evaluate($fieldValue, $compareValue);
    }
}
