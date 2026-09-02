<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Triggers;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\ScheduleInterval;

class ScheduleTriggerNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'schedule';
    }

    /**
     * Run every $value units, e.g. every(6, ScheduleInterval::Hours).
     */
    public static function every(int $value, ScheduleInterval $unit): static
    {
        return static::make()->intervalType($unit)->intervalValue($value);
    }

    /**
     * A cron expression, e.g. '0 9 * * 1'. Switches the trigger to cron mode.
     */
    public function cron(string $expression): static
    {
        return $this->set('cron', $expression)->intervalType(ScheduleInterval::CustomCron);
    }

    public function intervalType(ScheduleInterval|string $type): static
    {
        return $this->set('interval_type', $type);
    }

    public function intervalValue(int $value): static
    {
        return $this->set('interval_value', $value);
    }
}
