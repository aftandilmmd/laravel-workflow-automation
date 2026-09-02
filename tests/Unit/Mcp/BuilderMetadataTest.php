<?php

use Aftandilmmd\WorkflowAutomation\Builders\Actions\SendMailNode;
use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;

it('mentions the builder class in the workflow builder prompt', function () {
    $prompt = Aftandilmmd\WorkflowAutomation\Mcp\Prompts\WorkflowBuilderPrompt::buildSystemPromptText(
        app(NodeRegistry::class)->all()
    );

    expect($prompt)->toContain('PHP builder: '.SendMailNode::class);
})->skip(fn () => ! class_exists(Laravel\Mcp\Server\Prompt::class), 'laravel/mcp is not installed.');

it('exposes the builder class for every node in the registry listing', function () {
    $nodes = app(NodeRegistry::class)->all();

    $sendMail = collect($nodes)->firstWhere('key', 'send_mail');

    expect($sendMail['builder_class'])->toBe(SendMailNode::class);
});
