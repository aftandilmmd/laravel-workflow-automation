<?php

namespace Aftandilmmd\WorkflowAutomation\Builders;

use Aftandilmmd\WorkflowAutomation\Enums\NodeType;
use Aftandilmmd\WorkflowAutomation\Exceptions\InvalidNodeConfigException;
use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;
use BackedEnum;
use DateTimeInterface;
use UnitEnum;

/**
 * Base class for fluent node builders.
 *
 * A definition only collects configuration; it is turned into a WorkflowNode
 * by Workflow::addNode(). Setters return $this so calls can be chained.
 */
abstract class NodeDefinition
{
    /** @var array<string, mixed> */
    protected array $config = [];

    protected ?string $name = null;

    protected int $positionX = 0;

    protected int $positionY = 0;

    /**
     * The registry key of the node this definition configures.
     */
    abstract public function nodeKey(): string;

    public static function make(): static
    {
        return new static();
    }

    /**
     * Display name shown in the editor.
     */
    public function title(string $title): static
    {
        $this->name = $title;

        return $this;
    }

    public function position(int $x, int $y): static
    {
        $this->positionX = $x;
        $this->positionY = $y;

        return $this;
    }

    /**
     * Set a raw config key. Useful for keys without a dedicated method.
     */
    public function set(string $key, mixed $value): static
    {
        $this->config[$key] = $this->normalize($value);

        return $this;
    }

    /**
     * Merge multiple raw config keys at once.
     *
     * @param  array<string, mixed>  $config
     */
    public function config(array $config): static
    {
        foreach ($config as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        return $this->config;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function nodeType(): NodeType
    {
        return $this->registry()->getMeta($this->nodeKey())['type'] ?? NodeType::Action;
    }

    /**
     * Attributes used to create the WorkflowNode record.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type'       => $this->nodeType(),
            'node_key'   => $this->nodeKey(),
            'name'       => $this->name,
            'config'     => $this->config,
            'position_x' => $this->positionX,
            'position_y' => $this->positionY,
        ];
    }

    /**
     * Check the collected config against the node's config schema.
     *
     * @throws InvalidNodeConfigException
     */
    public function validate(): void
    {
        $schema = $this->schema();

        if ($schema === []) {
            return;
        }

        $known = array_column($schema, 'key');

        foreach (array_keys($this->config) as $key) {
            if (! in_array($key, $known, true)) {
                throw InvalidNodeConfigException::unknownKey($this->nodeKey(), $key, $this->closestKey($key, $known));
            }
        }

        $missing = [];

        foreach ($schema as $field) {
            if (empty($field['required']) || ! $this->isVisible($field)) {
                continue;
            }

            if (! isset($this->config[$field['key']])) {
                $missing[] = $field['key'];
            }
        }

        if ($missing !== []) {
            throw InvalidNodeConfigException::missingRequired($this->nodeKey(), $missing);
        }
    }

    /**
     * Append an entry to an array config key.
     */
    protected function push(string $key, mixed $value): static
    {
        $existing = $this->config[$key] ?? [];
        $existing[] = $this->normalize($value);
        $this->config[$key] = $existing;

        return $this;
    }

    /**
     * Merge a single pair into a key/value config key.
     */
    protected function putEntry(string $key, string|int $entryKey, mixed $value): static
    {
        $existing = $this->config[$key] ?? [];
        $existing[$entryKey] = $this->normalize($value);
        $this->config[$key] = $existing;

        return $this;
    }

    protected function normalize(mixed $value): mixed
    {
        return match (true) {
            $value instanceof BackedEnum       => $value->value,
            $value instanceof UnitEnum         => $value->name,
            $value instanceof DateTimeInterface => $value->format(DATE_ATOM),
            is_array($value)                   => array_map(fn ($item) => $this->normalize($item), $value),
            default                            => $value,
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function schema(): array
    {
        $meta = $this->registry()->getMeta($this->nodeKey());

        return $meta ? $meta['class']::configSchema() : [];
    }

    /**
     * A field is visible when it has no show_when rule, or the rule matches the current config.
     *
     * @param  array<string, mixed>  $field
     */
    private function isVisible(array $field): bool
    {
        if (empty($field['show_when'])) {
            return true;
        }

        $rule = $field['show_when'];
        $current = $this->config[$rule['key']] ?? null;
        $expected = $rule['value'];

        return is_array($expected)
            ? in_array($current, $expected, true)
            : $current === $expected;
    }

    /**
     * @param  string[]  $known
     */
    private function closestKey(string $key, array $known): ?string
    {
        $best = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($known as $candidate) {
            $distance = levenshtein($key, $candidate);

            if ($distance < $bestDistance) {
                $best = $candidate;
                $bestDistance = $distance;
            }
        }

        return $bestDistance <= 3 ? $best : null;
    }

    private function registry(): NodeRegistry
    {
        return app(NodeRegistry::class);
    }
}
