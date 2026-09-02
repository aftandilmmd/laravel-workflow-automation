<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Controls;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\MergeMode;

class MergeNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'merge';
    }

    public function mode(MergeMode|string $mode): static
    {
        return $this->set('mode', $mode);
    }
}
