<?php

use Aftandilmmd\WorkflowAutomation\Enums\AiProvider;
use Aftandilmmd\WorkflowAutomation\Enums\CodeMode;
use Aftandilmmd\WorkflowAutomation\Enums\CommandType;
use Aftandilmmd\WorkflowAutomation\Enums\ConditionOperator;
use Aftandilmmd\WorkflowAutomation\Enums\DelayUnit;
use Aftandilmmd\WorkflowAutomation\Enums\ErrorRoute;
use Aftandilmmd\WorkflowAutomation\Enums\FilterLogic;
use Aftandilmmd\WorkflowAutomation\Enums\HttpMethod;
use Aftandilmmd\WorkflowAutomation\Enums\MailSendMode;
use Aftandilmmd\WorkflowAutomation\Enums\MergeMode;
use Aftandilmmd\WorkflowAutomation\Enums\ModelEvent;
use Aftandilmmd\WorkflowAutomation\Enums\Operator;
use Aftandilmmd\WorkflowAutomation\Enums\ParseFormat;
use Aftandilmmd\WorkflowAutomation\Enums\ScheduleInterval;
use Aftandilmmd\WorkflowAutomation\Enums\StickyColor;
use Aftandilmmd\WorkflowAutomation\Enums\WebhookAuthType;
use Aftandilmmd\WorkflowAutomation\Enums\WorkflowTriggerOn;
use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;

function schemaOptions(string $nodeKey, string $fieldKey): array
{
    $schema = app(NodeRegistry::class)->getMeta($nodeKey)['class']::configSchema();
    $field = collect($schema)->firstWhere('key', $fieldKey);

    return array_map(
        fn ($option) => is_array($option) ? $option['value'] : $option,
        $field['options'] ?? [],
    );
}

dataset('enum backed fields', [
    ['send_mail', 'send_mode', MailSendMode::class],
    ['http_request', 'method', HttpMethod::class],
    ['run_command', 'command_type', CommandType::class],
    ['ai', 'provider', AiProvider::class],
    ['model_event', 'events', ModelEvent::class],
    ['schedule', 'interval_type', ScheduleInterval::class],
    ['webhook', 'auth_type', WebhookAuthType::class],
    ['workflow', 'trigger_on', WorkflowTriggerOn::class],
    ['delay', 'delay_type', DelayUnit::class],
    ['error_handler', 'default_route', ErrorRoute::class],
    ['merge', 'mode', MergeMode::class],
    ['parse_data', 'format', ParseFormat::class],
    ['code', 'mode', CodeMode::class],
    ['filter', 'logic', FilterLogic::class],
    ['sticky_note', 'color', StickyColor::class],
    ['if_condition', 'operator', ConditionOperator::class],
]);

it('exposes enum values as schema options', function (string $nodeKey, string $fieldKey, string $enum) {
    expect(schemaOptions($nodeKey, $fieldKey))->toBe(array_column($enum::cases(), 'value'));
})->with('enum backed fields');

it('keeps the webhook method subset unchanged', function () {
    expect(schemaOptions('webhook', 'method'))->toBe(['GET', 'POST', 'PUT', 'PATCH']);
});

it('keeps the model events endpoint in sync with the enum', function () {
    $this->getJson('/workflow-engine/metadata/model-events')
        ->assertOk()
        ->assertJson(['data' => array_column(ModelEvent::cases(), 'value')]);
});

it('keeps the deprecated Operator enum aligned with ConditionOperator', function () {
    expect(array_column(Operator::cases(), 'value'))->toBe(array_column(ConditionOperator::cases(), 'value'))
        ->and(Operator::GreaterThan->evaluate(10, 5))->toBeTrue()
        ->and(Operator::Contains->evaluate('workflow', 'flow'))->toBeTrue()
        ->and(Operator::IsEmpty->evaluate(''))->toBeTrue();
});
