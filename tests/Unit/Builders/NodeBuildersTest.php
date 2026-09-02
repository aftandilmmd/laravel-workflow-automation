<?php

use Aftandilmmd\WorkflowAutomation\Builders\Actions\AiNode;
use Aftandilmmd\WorkflowAutomation\Builders\Actions\DispatchJobNode;
use Aftandilmmd\WorkflowAutomation\Builders\Actions\HttpRequestNode;
use Aftandilmmd\WorkflowAutomation\Builders\Actions\RunCommandNode;
use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendMailNode;
use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendNotificationNode;
use Aftandilmmd\WorkflowAutomation\Builders\Actions\UpdateModelNode;
use Aftandilmmd\WorkflowAutomation\Builders\Annotations\StickyNoteNode;
use Aftandilmmd\WorkflowAutomation\Builders\Conditions\IfConditionNode;
use Aftandilmmd\WorkflowAutomation\Builders\Conditions\SwitchNode;
use Aftandilmmd\WorkflowAutomation\Builders\Controls\DelayNode;
use Aftandilmmd\WorkflowAutomation\Builders\Controls\ErrorHandlerNode;
use Aftandilmmd\WorkflowAutomation\Builders\Controls\LoopNode;
use Aftandilmmd\WorkflowAutomation\Builders\Controls\MergeNode;
use Aftandilmmd\WorkflowAutomation\Builders\Controls\SubWorkflowNode;
use Aftandilmmd\WorkflowAutomation\Builders\Controls\WaitResumeNode;
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\ParseDataNode;
use Aftandilmmd\WorkflowAutomation\Builders\Transformers\SetFieldsNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\EventTriggerNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ManualTriggerNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ModelEventTriggerNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\ScheduleTriggerNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\WebhookTriggerNode;
use Aftandilmmd\WorkflowAutomation\Builders\Triggers\WorkflowTriggerNode;
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\AggregateNode;
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\CodeNode;
use Aftandilmmd\WorkflowAutomation\Builders\Utilities\FilterNode;
use Aftandilmmd\WorkflowAutomation\Enums\AggregateFunction;
use Aftandilmmd\WorkflowAutomation\Enums\ConditionOperator;
use Aftandilmmd\WorkflowAutomation\Enums\ErrorRoute;
use Aftandilmmd\WorkflowAutomation\Enums\FilterLogic;
use Aftandilmmd\WorkflowAutomation\Enums\HttpMethod;
use Aftandilmmd\WorkflowAutomation\Enums\MergeMode;
use Aftandilmmd\WorkflowAutomation\Enums\ModelEvent;
use Aftandilmmd\WorkflowAutomation\Enums\ParseFormat;
use Aftandilmmd\WorkflowAutomation\Enums\ScheduleInterval;
use Aftandilmmd\WorkflowAutomation\Enums\StickyColor;
use Aftandilmmd\WorkflowAutomation\Enums\WebhookAuthType;
use Aftandilmmd\WorkflowAutomation\Enums\WorkflowTriggerOn;
use Aftandilmmd\WorkflowAutomation\Models\Workflow;

it('builds trigger configs', function () {
    expect(ManualTriggerNode::make()->inputSchema(['email' => 'string'])->getConfig())
        ->toBe(['input_schema' => ['email' => 'string']]);

    expect(EventTriggerNode::make()->eventClass('App\\Events\\OrderPlaced')->getConfig())
        ->toBe(['event_class' => 'App\\Events\\OrderPlaced']);

    expect(ModelEventTriggerNode::make()
        ->model('App\\Models\\User')
        ->events([ModelEvent::Created, ModelEvent::Updated])
        ->event(ModelEvent::Deleted)
        ->onlyFields(['email'])
        ->getConfig()
    )->toBe([
        'model'       => 'App\\Models\\User',
        'events'      => ['created', 'updated', 'deleted'],
        'only_fields' => ['email'],
    ]);

    expect(WebhookTriggerNode::make()->method(HttpMethod::Post)->authType(WebhookAuthType::Bearer)->credentialId(7)->getConfig())
        ->toBe(['method' => 'POST', 'auth_type' => 'bearer', 'credential_id' => 7]);
});

it('switches the schedule trigger to cron mode', function () {
    expect(ScheduleTriggerNode::make()->cron('0 9 * * 1')->getConfig())
        ->toBe(['cron' => '0 9 * * 1', 'interval_type' => 'custom_cron']);

    expect(ScheduleTriggerNode::every(6, ScheduleInterval::Hours)->getConfig())
        ->toBe(['interval_type' => 'hours', 'interval_value' => 6]);
});

it('accepts models for id fields', function () {
    $workflow = Workflow::factory()->create();

    expect(WorkflowTriggerNode::make()->sourceWorkflow($workflow)->triggerOn(WorkflowTriggerOn::Completed)->getConfig())
        ->toBe(['source_workflow_id' => $workflow->id, 'trigger_on' => 'completed']);

    expect(SubWorkflowNode::make()->workflow($workflow)->passItems()->waitForResult()->getConfig())
        ->toBe(['workflow_id' => $workflow->id, 'pass_items' => true, 'wait_for_result' => true]);
});

it('picks the mail send mode from the fields used', function () {
    expect(SendMailNode::make()
        ->to('{{ item.email }}')
        ->subject('Welcome')
        ->body('Hi')
        ->attachment('invoice.pdf', '/tmp/invoice.pdf')
        ->isHtml()
        ->getConfig()
    )->toBe([
        'send_mode'   => 'inline',
        'to'          => '{{ item.email }}',
        'subject'     => 'Welcome',
        'body'        => 'Hi',
        'attachments' => ['invoice.pdf' => '/tmp/invoice.pdf'],
        'is_html'     => true,
    ]);

    expect(SendMailNode::make()->mailableClass('App\\Mail\\Welcome')->mailableTo('{{ item.email }}')->getConfig())
        ->toBe([
            'send_mode'      => 'mailable',
            'mailable_class' => 'App\\Mail\\Welcome',
            'mailable_to'    => '{{ item.email }}',
        ]);
});

it('builds action configs', function () {
    expect(HttpRequestNode::post('https://example.test/orders', ['id' => 1])->header('X-Source', 'workflow')->timeout(15)->getConfig())
        ->toBe([
            'method'  => 'POST',
            'url'     => 'https://example.test/orders',
            'body'    => ['id' => 1],
            'headers' => ['X-Source' => 'workflow'],
            'timeout' => 15,
        ]);

    expect(UpdateModelNode::make()->model('App\\Models\\Order')->findBy('id')->findValue('{{ item.id }}')->field('status', 'paid')->getConfig())
        ->toBe([
            'model'      => 'App\\Models\\Order',
            'find_by'    => 'id',
            'find_value' => '{{ item.id }}',
            'fields'     => ['status' => 'paid'],
        ]);

    expect(DispatchJobNode::make()->jobClass('App\\Jobs\\Invoice')->queue('invoices')->delay(30)->withItem()->getConfig())
        ->toBe(['job_class' => 'App\\Jobs\\Invoice', 'queue' => 'invoices', 'delay' => 30, 'with_item' => true]);

    expect(SendNotificationNode::make()->notificationClass('App\\Notifications\\Shipped')->notifiableClass('App\\Models\\User')->notifiableId('{{ item.user_id }}')->getConfig())
        ->toBe([
            'notification_class' => 'App\\Notifications\\Shipped',
            'notifiable_class'   => 'App\\Models\\User',
            'notifiable_id'      => '{{ item.user_id }}',
        ]);

    expect(RunCommandNode::artisan('reports:generate')->argument('--date', '{{ item.date }}')->includeOutput()->getConfig())
        ->toBe([
            'command_type'   => 'artisan',
            'command'        => 'reports:generate',
            'arguments'      => ['--date' => '{{ item.date }}'],
            'include_output' => true,
        ]);

    expect(AiNode::make()->prompt('Classify {{ item.body }}')->temperature(0.2)->maxTokens(200)->outputKey('category')->getConfig())
        ->toBe(['prompt' => 'Classify {{ item.body }}', 'temperature' => '0.2', 'max_tokens' => 200, 'output_key' => 'category']);
});

it('builds condition configs', function () {
    expect(IfConditionNode::when('{{ item.total }}', ConditionOperator::GreaterThan, 500)->getConfig())
        ->toBe(['field' => '{{ item.total }}', 'operator' => 'greater_than', 'value' => 500]);

    expect(SwitchNode::make()
        ->field('{{ item.plan }}')
        ->case('case_premium', ConditionOperator::Equals, 'premium')
        ->case('case_pro', ConditionOperator::Equals, 'pro')
        ->fallthrough()
        ->getConfig()
    )->toBe([
        'field' => '{{ item.plan }}',
        'cases' => [
            ['port' => 'case_premium', 'operator' => 'equals', 'value' => 'premium'],
            ['port' => 'case_pro', 'operator' => 'equals', 'value' => 'pro'],
        ],
        'fallthrough' => true,
    ]);
});

it('builds transformer and control configs', function () {
    expect(SetFieldsNode::make()->field('source', 'signup')->keepExisting()->getConfig())
        ->toBe(['fields' => ['source' => 'signup'], 'keep_existing' => true]);

    expect(ParseDataNode::make()->sourceField('{{ item.raw }}')->format(ParseFormat::Json)->targetField('payload')->getConfig())
        ->toBe(['source_field' => '{{ item.raw }}', 'format' => 'json', 'target_field' => 'payload']);

    expect(LoopNode::make()->sourceField('lines')->getConfig())->toBe(['source_field' => 'lines']);

    expect(MergeNode::make()->mode(MergeMode::WaitAll)->getConfig())->toBe(['mode' => 'wait_all']);

    expect(DelayNode::minutes(15)->getConfig())->toBe(['delay_type' => 'minutes', 'delay_value' => 15]);

    expect(WaitResumeNode::make()->timeoutSeconds(86400)->getConfig())->toBe(['timeout_seconds' => 86400]);

    expect(ErrorHandlerNode::make()
        ->rule('/timeout/i', ErrorRoute::Retry)
        ->defaultRoute(ErrorRoute::Notify)
        ->getConfig()
    )->toBe([
        'rules'         => [['match' => '/timeout/i', 'route' => 'retry']],
        'default_route' => 'notify',
    ]);
});

it('builds utility and annotation configs', function () {
    expect(FilterNode::make()
        ->condition('status', ConditionOperator::Equals, 'active')
        ->condition('total', ConditionOperator::GreaterOrEqual, 1000)
        ->logic(FilterLogic::And)
        ->getConfig()
    )->toBe([
        'conditions' => [
            ['field' => 'status', 'operator' => 'equals', 'value' => 'active'],
            ['field' => 'total', 'operator' => 'greater_or_equal', 'value' => 1000],
        ],
        'logic' => 'and',
    ]);

    expect(AggregateNode::make()->groupBy('country')->operation('total', AggregateFunction::Sum, 'revenue')->getConfig())
        ->toBe([
            'group_by'   => 'country',
            'operations' => [['field' => 'total', 'function' => 'sum', 'alias' => 'revenue']],
        ]);

    expect(CodeNode::transform('{{ item.total - item.cost }}')->getConfig())
        ->toBe(['mode' => 'transform', 'expression' => '{{ item.total - item.cost }}']);

    expect(StickyNoteNode::make()->content('VIP branch')->color(StickyColor::Yellow)->getConfig())
        ->toBe(['content' => 'VIP branch', 'color' => 'yellow']);
});

it('passes validation for fully configured builders', function () {
    IfConditionNode::when('total', ConditionOperator::GreaterThan, 5)->validate();
    SendMailNode::make()->to('a@b.test')->subject('Hi')->body('Body')->validate();
    HttpRequestNode::get('https://example.test')->validate();
    DelayNode::hours(2)->validate();
    ScheduleTriggerNode::every(6, ScheduleInterval::Hours)->validate();
})->throwsNoExceptions();
