<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Utilities;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\CodeMode;

class CodeNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'code';
    }

    public static function transform(string $expression): static
    {
        return static::make()->mode(CodeMode::Transform)->expression($expression);
    }

    public static function filter(string $expression): static
    {
        return static::make()->mode(CodeMode::Filter)->expression($expression);
    }

    public function mode(CodeMode|string $mode): static
    {
        return $this->set('mode', $mode);
    }

    public function expression(string $expression): static
    {
        return $this->set('expression', $expression);
    }
}
