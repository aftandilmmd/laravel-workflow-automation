<?php

use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendMailNode;
use Aftandilmmd\WorkflowAutomation\Builders\GenericNode;
use Aftandilmmd\WorkflowAutomation\Enums\NodeType;
use Aftandilmmd\WorkflowAutomation\Exceptions\InvalidNodeConfigException;
use Aftandilmmd\WorkflowAutomation\Models\Workflow;
use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;

beforeEach(function () {
    $this->workflow = Workflow::factory()->create();
});

it('adds a node from a definition', function () {
    $node = $this->workflow->addNode(
        GenericNode::make('if_condition')
            ->title('VIP Check')
            ->set('field', 'total')
            ->set('operator', 'greater_than')
            ->set('value', 500)
            ->position(120, 40)
    );

    expect($node->exists)->toBeTrue()
        ->and($node->name)->toBe('VIP Check')
        ->and($node->node_key)->toBe('if_condition')
        ->and($node->type)->toBe(NodeType::Condition)
        ->and($node->config)->toBe(['field' => 'total', 'operator' => 'greater_than', 'value' => 500])
        ->and($node->position_x)->toBe(120)
        ->and($node->position_y)->toBe(40);
});

it('produces the same record for both syntaxes', function () {
    $fromArray = $this->workflow->addNode('Welcome', 'send_mail', [
        'send_mode' => 'inline',
        'to'        => '{{ item.email }}',
        'subject'   => 'Welcome!',
        'body'      => 'Hi there.',
    ]);

    $fromBuilder = $this->workflow->addNode(
        GenericNode::make('send_mail')
            ->title('Welcome')
            ->set('send_mode', 'inline')
            ->set('to', '{{ item.email }}')
            ->set('subject', 'Welcome!')
            ->set('body', 'Hi there.')
    );

    expect($fromBuilder->only(['type', 'node_key', 'name', 'config']))
        ->toBe($fromArray->only(['type', 'node_key', 'name', 'config']));
});

it('keeps working with named arguments on the array syntax', function () {
    $node = $this->workflow->addNode(name: 'Manual Start', nodeKey: 'manual', config: []);

    expect($node->node_key)->toBe('manual')
        ->and($node->name)->toBe('Manual Start');
});

it('adds several definitions at once', function () {
    $nodes = $this->workflow->addNodes(
        GenericNode::make('manual')->title('Start'),
        GenericNode::make('set_fields')->title('Prepare')->set('fields', ['source' => 'test']),
    );

    expect($nodes)->toHaveCount(2)
        ->and($nodes[0]->node_key)->toBe('manual')
        ->and($nodes[1]->node_key)->toBe('set_fields');
});

it('adds a sibling node from an existing node', function () {
    $trigger = $this->workflow->addNode(GenericNode::make('manual')->title('Start'));

    $next = $trigger->addNode(GenericNode::make('set_fields')->set('fields', ['a' => 1]));

    expect($next->workflow_id)->toBe($this->workflow->id)
        ->and($next->node_key)->toBe('set_fields');
});

it('validates the definition before persisting', function () {
    expect(fn () => $this->workflow->addNode(GenericNode::make('if_condition')->set('field', 'total')))
        ->toThrow(InvalidNodeConfigException::class);

    expect($this->workflow->nodes()->count())->toBe(0);
});

it('does not validate the array syntax', function () {
    $node = $this->workflow->addNode('Loose', 'if_condition', ['field' => 'total']);

    expect($node->exists)->toBeTrue();
});

it('requires a node key with the array syntax', function () {
    $this->workflow->addNode('No Key');
})->throws(InvalidArgumentException::class, 'A node key is required when adding a node by name.');

it('exposes the builder class through the registry', function () {
    $registry = app(NodeRegistry::class);

    expect($registry->builderFor('send_mail'))->toBe(SendMailNode::class);

    $sendMail = collect($registry->all())->firstWhere('key', 'send_mail');

    expect($sendMail['builder_class'])->toBe(SendMailNode::class);
});
