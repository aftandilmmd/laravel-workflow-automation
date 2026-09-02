<?php

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;
use Illuminate\Support\Str;

it('registers a builder for every built-in node', function () {
    $registry = app(NodeRegistry::class);

    $missing = array_values(array_filter(
        array_column($registry->all(), 'key'),
        fn (string $key) => $registry->builderFor($key) === null,
    ));

    expect($missing)->toBe([]);
});

it('exposes a setter for every config key', function () {
    $registry = app(NodeRegistry::class);
    $gaps = [];

    foreach ($registry->all() as $node) {
        $builder = $registry->builderFor($node['key']);

        if ($builder === null) {
            continue;
        }

        $methods = array_map(
            fn (ReflectionMethod $method) => $method->getName(),
            (new ReflectionClass($builder))->getMethods(ReflectionMethod::IS_PUBLIC),
        );

        foreach (array_column($node['config_schema'], 'key') as $key) {
            if (! in_array(Str::camel($key), $methods, true)) {
                $gaps[] = "{$node['key']}.{$key}";
            }
        }
    }

    expect($gaps)->toBe([]);
});

it('builds a definition whose node key matches the registry', function () {
    $registry = app(NodeRegistry::class);
    $mismatches = [];

    foreach (array_column($registry->all(), 'key') as $key) {
        $definition = $registry->builderFor($key)::make();

        expect($definition)->toBeInstanceOf(NodeDefinition::class);

        if ($definition->nodeKey() !== $key) {
            $mismatches[] = "{$key} => {$definition->nodeKey()}";
        }
    }

    expect($mismatches)->toBe([]);
});
