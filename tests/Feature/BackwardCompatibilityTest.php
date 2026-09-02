<?php

use Aftandilmmd\WorkflowAutomation\Builders\Conditions\IfConditionNode;
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\SetFieldsNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ManualTriggerNode;
use Aftandilmmd\WorkflowAutomation\Enums\ConditionOperator;
use Aftandilmmd\WorkflowAutomation\Enums\Operator;
use Aftandilmmd\WorkflowAutomation\Enums\RunStatus;
use Aftandilmmd\WorkflowAutomation\Facades\Workflow as WorkflowFacade;
use Aftandilmmd\WorkflowAutomation\Models\Workflow;

/**
 * The array syntax predates the builder API and stays supported.
 */
it('runs a workflow built with the array syntax', function () {
    $workflow = Workflow::factory()->create(['is_active' => true]);

    $trigger = $workflow->addNode('Start', 'manual', []);
    $tag = $workflow->addNode('Tag VIP', 'set_fields', ['fields' => ['tier' => 'vip']]);
    $trigger->connect($tag);

    $run = WorkflowFacade::run($workflow, ['items' => [['id' => 1]]]);

    expect($run->status)->toBe(RunStatus::Completed);
});

it('runs the same workflow built with builders', function () {
    $workflow = Workflow::factory()->create(['is_active' => true]);

    $trigger = $workflow->addNode(ManualTriggerNode::make()->title('Start'));
    $tag = $workflow->addNode(SetFieldsNode::make()->title('Tag VIP')->field('tier', 'vip'));
    $trigger->connect($tag);

    $run = WorkflowFacade::run($workflow, ['items' => [['id' => 1]]]);

    expect($run->status)->toBe(RunStatus::Completed);
});

it('still accepts the deprecated Operator enum in builder setters', function () {
    $legacy = IfConditionNode::make()->field('total')->operator(Operator::GreaterThan)->value(5);
    $current = IfConditionNode::when('total', ConditionOperator::GreaterThan, 5);

    expect($legacy->getConfig())->toBe($current->getConfig());
});

it('still accepts plain strings for enum backed setters', function () {
    expect(IfConditionNode::make()->field('total')->operator('greater_than')->value(5)->getConfig())
        ->toBe(['field' => 'total', 'operator' => 'greater_than', 'value' => 5]);
});
