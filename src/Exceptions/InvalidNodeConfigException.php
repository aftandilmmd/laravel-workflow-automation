<?php

namespace Aftandilmmd\WorkflowAutomation\Exceptions;

class InvalidNodeConfigException extends WorkflowException
{
    /**
     * @param  string[]  $missing
     */
    public static function missingRequired(string $nodeKey, array $missing): self
    {
        return new self(sprintf(
            'Missing required config for node [%s]: %s.',
            $nodeKey,
            implode(', ', $missing),
        ));
    }

    public static function unknownKey(string $nodeKey, string $key, ?string $suggestion = null): self
    {
        $message = sprintf('Unknown config key [%s] for node [%s].', $key, $nodeKey);

        if ($suggestion) {
            $message .= sprintf(' Did you mean [%s]?', $suggestion);
        }

        return new self($message);
    }
}
