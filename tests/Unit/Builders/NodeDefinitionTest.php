<?php

use Aftandilmmd\WorkflowAutomation\Builders\GenericNode;
use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\AggregateFunction;
use Aftandilmmd\WorkflowAutomation\Enums\NodeType;
use Aftandilmmd\WorkflowAutomation\Enums\Operator;
use Aftandilmmd\WorkflowAutomation\Exceptions\InvalidNodeConfigException;

/**
 * Minimal definition bound to the built-in if_condition node.
 */
class IfConditionDefinitionStub extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'if_condition';
    }

    public function field(string $field): static
    {
        return $this->set('field', $field);
    }

    public function operator(Operator|string $operator): static
    {
        return $this->set('operator', $operator);
    }

    public function value(mixed $value): static
    {
        return $this->set('value', $value);
    }
}

it('collects config, name and position', function () {
    $definition = IfConditionDefinitionStub::make()
        ->title('VIP Check')
        ->field('{{ item.total }}')
        ->operator(Operator::GreaterThan)
        ->value(500)
        ->position(120, 40);

    expect($definition->toArray())->toBe([
        'type'       => NodeType::Condition,
        'node_key'   => 'if_condition',
        'name'       => 'VIP Check',
        'config'     => [
            'field'    => '{{ item.total }}',
            'operator' => 'greater_than',
            'value'    => 500,
        ],
        'position_x' => 120,
        'position_y' => 40,
    ]);
});

it('normalizes enums, nested enums and dates', function () {
    $definition = GenericNode::make('aggregate')
        ->set('operations', [
            ['field' => 'total', 'function' => AggregateFunction::Sum, 'alias' => 'revenue'],
        ])
        ->set('group_by', new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('UTC')));

    expect($definition->getConfig()['operations'][0]['function'])->toBe('sum')
        ->and($definition->getConfig()['group_by'])->toBe('2026-01-01T00:00:00+00:00');
});

it('merges raw config through config()', function () {
    $definition = IfConditionDefinitionStub::make()
        ->config(['field' => 'status', 'operator' => Operator::Equals]);

    expect($definition->getConfig())->toBe(['field' => 'status', 'operator' => 'equals']);
});

it('defaults to an empty name and zero position', function () {
    $definition = IfConditionDefinitionStub::make();

    expect($definition->getName())->toBeNull()
        ->and($definition->toArray()['position_x'])->toBe(0)
        ->and($definition->toArray()['position_y'])->toBe(0);
});

it('passes validation when required fields are present', function () {
    IfConditionDefinitionStub::make()
        ->field('{{ item.total }}')
        ->operator(Operator::GreaterThan)
        ->validate();
})->throwsNoExceptions();

it('rejects missing required fields', function () {
    IfConditionDefinitionStub::make()->field('{{ item.total }}')->validate();
})->throws(InvalidNodeConfigException::class, 'Missing required config for node [if_condition]: operator.');

it('rejects unknown config keys and suggests the closest one', function () {
    GenericNode::make('if_condition')->set('operatr', 'equals')->validate();
})->throws(InvalidNodeConfigException::class, 'Unknown config key [operatr] for node [if_condition]. Did you mean [operator]?');

it('omits a suggestion when no key is close enough', function () {
    GenericNode::make('if_condition')->set('completely_unrelated', 1)->validate();
})->throws(InvalidNodeConfigException::class, 'Unknown config key [completely_unrelated] for node [if_condition].');

it('skips required fields hidden by show_when', function () {
    GenericNode::make('send_mail')
        ->set('send_mode', 'mailable')
        ->set('mailable_class', 'App\\Mail\\Welcome')
        ->set('mailable_to', '{{ item.email }}')
        ->validate();
})->throwsNoExceptions();

it('requires fields visible under the active show_when branch', function () {
    GenericNode::make('send_mail')->set('send_mode', 'inline')->validate();
})->throws(InvalidNodeConfigException::class, 'Missing required config for node [send_mail]: to, subject, body.');

it('resolves the node type from the registry', function () {
    expect(GenericNode::make('send_mail')->nodeType())->toBe(NodeType::Action)
        ->and(GenericNode::make('manual')->nodeType())->toBe(NodeType::Trigger)
        ->and(GenericNode::make('loop')->nodeType())->toBe(NodeType::Control);
});

it('skips validation for unregistered node keys', function () {
    GenericNode::make('not_registered')->set('anything', true)->validate();
})->throwsNoExceptions();

it('requires a node key', function () {
    GenericNode::make();
})->throws(InvalidArgumentException::class, 'GenericNode requires a node key.');
