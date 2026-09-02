<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Controls;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\DelayUnit;

class DelayNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'delay';
    }

    public static function seconds(int $value): static
    {
        return static::make()->delayType(DelayUnit::Seconds)->delayValue($value);
    }

    public static function minutes(int $value): static
    {
        return static::make()->delayType(DelayUnit::Minutes)->delayValue($value);
    }

    public static function hours(int $value): static
    {
        return static::make()->delayType(DelayUnit::Hours)->delayValue($value);
    }

    public function delayType(DelayUnit|string $unit): static
    {
        return $this->set('delay_type', $unit);
    }

    public function delayValue(int $value): static
    {
        return $this->set('delay_value', $value);
    }
}
