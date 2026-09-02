<?php

namespace Aftandilmmd\WorkflowAutomation\Attributes;

use Aftandilmmd\WorkflowAutomation\Enums\NodeType;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class AsWorkflowNode
{
    /**
     * @param  class-string<\Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition>|null  $builder
     */
    public function __construct(
        public readonly string   $key,
        public readonly NodeType $type,
        public readonly string   $label = '',
        public readonly ?string  $builder = null,
    ) {}
}
