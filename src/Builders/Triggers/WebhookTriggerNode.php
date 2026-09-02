<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Triggers;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\HttpMethod;
use Aftandilmmd\WorkflowAutomation\Enums\WebhookAuthType;
use Illuminate\Database\Eloquent\Model;

class WebhookTriggerNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'webhook';
    }

    /**
     * Public webhook path. Generated automatically when left unset.
     */
    public function path(string $path): static
    {
        return $this->set('path', $path);
    }

    public function method(HttpMethod|string $method): static
    {
        return $this->set('method', $method);
    }

    public function authType(WebhookAuthType|string $type): static
    {
        return $this->set('auth_type', $type);
    }

    public function credentialId(int|Model $credential): static
    {
        return $this->set('credential_id', $credential instanceof Model ? $credential->getKey() : $credential);
    }

    public function credential(int|Model $credential): static
    {
        return $this->credentialId($credential);
    }

    /**
     * @deprecated Store secrets as credentials instead.
     */
    public function authValue(string $value): static
    {
        return $this->set('auth_value', $value);
    }
}
