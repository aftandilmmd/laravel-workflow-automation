<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Transformers;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\ParseFormat;

class ParseDataNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'parse_data';
    }

    public function sourceField(string $field): static
    {
        return $this->set('source_field', $field);
    }

    public function format(ParseFormat|string $format): static
    {
        return $this->set('format', $format);
    }

    public function targetField(string $field): static
    {
        return $this->set('target_field', $field);
    }
}
