<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Annotations;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\StickyColor;

/**
 * Editor-only note. Takes no part in execution.
 */
class StickyNoteNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'sticky_note';
    }

    public function content(string $content): static
    {
        return $this->set('content', $content);
    }

    public function color(StickyColor|string $color): static
    {
        return $this->set('color', $color);
    }
}
