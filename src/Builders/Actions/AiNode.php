<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Actions;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\AiProvider;

class AiNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'ai';
    }

    public function prompt(string $prompt): static
    {
        return $this->set('prompt', $prompt);
    }

    public function systemPrompt(string $prompt): static
    {
        return $this->set('system_prompt', $prompt);
    }

    public function provider(AiProvider|string $provider): static
    {
        return $this->set('provider', $provider);
    }

    /**
     * Model name for the chosen provider, e.g. 'claude-sonnet-4-5-20250514' or 'gpt-4.1'.
     */
    public function model(string $model): static
    {
        return $this->set('model', $model);
    }

    public function temperature(float|string $temperature): static
    {
        return $this->set('temperature', (string) $temperature);
    }

    public function maxTokens(int $maxTokens): static
    {
        return $this->set('max_tokens', $maxTokens);
    }

    /**
     * Item key the answer is written to.
     */
    public function outputKey(string $key): static
    {
        return $this->set('output_key', $key);
    }
}
