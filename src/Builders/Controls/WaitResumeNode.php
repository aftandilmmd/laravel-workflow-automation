<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Controls;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;

/**
 * Pauses the run until resumed. Continues on 'resume', or on 'timeout' when it expires.
 */
class WaitResumeNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'wait_resume';
    }

    /**
     * @param  int  $seconds  0 means no timeout.
     */
    public function timeoutSeconds(int $seconds): static
    {
        return $this->set('timeout_seconds', $seconds);
    }
}
