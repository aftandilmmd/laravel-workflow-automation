<?php

namespace Aftandilmmd\WorkflowAutomation\Exceptions;

class RateLimitExceededException extends WorkflowException
{
    public function __construct(
        public readonly int $workflowId,
        public readonly int $currentRuns,
        public readonly int $maxConcurrent,
        public readonly string $scope = 'workflow',
    ) {
        $message = $scope === 'global'
            ? "Global concurrency limit reached: {$currentRuns}/{$maxConcurrent} runs active."
            : "Workflow {$workflowId} concurrency limit reached: {$currentRuns}/{$maxConcurrent} runs active.";

        parent::__construct($message);
    }
}
