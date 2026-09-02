<?php

namespace Aftandilmmd\WorkflowAutomation\Builders;

/**
 * Builder for nodes that have no dedicated builder class, such as custom or plugin nodes.
 *
 * GenericNode::make('my_custom_node')->title('Custom Step')->set('some_key', 'value');
 */
class GenericNode extends NodeDefinition
{
    private string $nodeKey;

    public function __construct(string $nodeKey)
    {
        $this->nodeKey = $nodeKey;
    }

    public static function make(?string $nodeKey = null): static
    {
        return new static($nodeKey ?? throw new \InvalidArgumentException('GenericNode requires a node key.'));
    }

    public function nodeKey(): string
    {
        return $this->nodeKey;
    }
}
