<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Actions;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\HttpMethod;
use Illuminate\Database\Eloquent\Model;

class HttpRequestNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'http_request';
    }

    public static function get(string $url): static
    {
        return static::make()->method(HttpMethod::Get)->url($url);
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    public static function post(string $url, ?array $body = null): static
    {
        $node = static::make()->method(HttpMethod::Post)->url($url);

        return $body === null ? $node : $node->body($body);
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    public static function put(string $url, ?array $body = null): static
    {
        $node = static::make()->method(HttpMethod::Put)->url($url);

        return $body === null ? $node : $node->body($body);
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    public static function patch(string $url, ?array $body = null): static
    {
        $node = static::make()->method(HttpMethod::Patch)->url($url);

        return $body === null ? $node : $node->body($body);
    }

    public static function delete(string $url): static
    {
        return static::make()->method(HttpMethod::Delete)->url($url);
    }

    public function url(string $url): static
    {
        return $this->set('url', $url);
    }

    public function method(HttpMethod|string $method): static
    {
        return $this->set('method', $method);
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function headers(array $headers): static
    {
        return $this->set('headers', $headers);
    }

    public function header(string $name, string $value): static
    {
        return $this->putEntry('headers', $name, $value);
    }

    /**
     * @param  array<string, mixed>|string  $body
     */
    public function body(array|string $body): static
    {
        return $this->set('body', $body);
    }

    public function timeout(int $seconds): static
    {
        return $this->set('timeout', $seconds);
    }

    public function includeResponse(bool $include = true): static
    {
        return $this->set('include_response', $include);
    }

    public function credentialId(int|Model $credential): static
    {
        return $this->set('credential_id', $credential instanceof Model ? $credential->getKey() : $credential);
    }

    public function credential(int|Model $credential): static
    {
        return $this->credentialId($credential);
    }
}
