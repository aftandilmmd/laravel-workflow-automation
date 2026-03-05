<?php

namespace Aftandilmmd\WorkflowAutomation\Http\Controllers;

use Aftandilmmd\WorkflowAutomation\Http\Requests\AiBuildRequest;
use Aftandilmmd\WorkflowAutomation\Models\Workflow;
use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;
use Illuminate\Routing\Controller;

class AiBuilderController extends Controller
{
    public function __construct(
        protected NodeRegistry $registry,
    ) {}

    public function build(AiBuildRequest $request, int $workflow)
    {
        if (! config('workflow-automation.ai_builder.enabled', true)) {
            return response()->json(['message' => 'AI Builder is disabled.'], 403);
        }

        if (! interface_exists(\Laravel\Ai\Contracts\Agent::class)) {
            return response()->json([
                'message' => 'The AI Builder requires the laravel/ai package. Install it with: composer require laravel/ai',
            ], 500);
        }

        if (! class_exists(\Laravel\Mcp\Server\Tool::class)) {
            return response()->json([
                'message' => 'The AI Builder requires the laravel/mcp package. Install it with: composer require laravel/mcp',
            ], 500);
        }

        try {
            $workflow = Workflow::findOrFail($workflow);

            /** @var \Aftandilmmd\WorkflowAutomation\AiBuilder\WorkflowBuilderAgent $agent */
            $agentClass = 'Aftandilmmd\\WorkflowAutomation\\AiBuilder\\WorkflowBuilderAgent';
            $agent = new $agentClass($workflow, $this->registry);

            $provider = $request->validated('provider', config('workflow-automation.ai_builder.default_provider'));
            $model = $request->validated('model', config('workflow-automation.ai_builder.default_model'));

            $args = [];

            if ($provider) {
                $args['provider'] = $this->resolveProvider($provider);
            }

            if ($model) {
                $args['model'] = $model;
            }

            $streamable = $agent->stream($request->validated('prompt'), ...$args);

            return response()->stream(function () use ($streamable) {
                try {
                    foreach ($streamable as $event) {
                        echo 'data: ' . json_encode($event->toArray()) . "\n\n";
                        ob_flush();
                        flush();
                    }
                    echo "data: [DONE]\n\n";
                    ob_flush();
                    flush();
                } catch (\Throwable $e) {
                    $error = json_encode([
                        'type' => 'error',
                        'message' => $e->getMessage() . (config('app.debug') ? ' in ' . $e->getFile() . ':' . $e->getLine() : ''),
                    ]);
                    echo "data: {$error}\n\n";
                    echo "data: [DONE]\n\n";
                    ob_flush();
                    flush();
                }
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);
        } catch (\Throwable $e) {
            $message = $e->getMessage();

            if (config('app.debug')) {
                $message .= ' in ' . $e->getFile() . ':' . $e->getLine();
            }

            return response()->json(['message' => $message], 500);
        }
    }

    protected function resolveProvider(string $provider): mixed
    {
        $map = [
            'openai' => \Laravel\Ai\Enums\Lab::OpenAI,
            'anthropic' => \Laravel\Ai\Enums\Lab::Anthropic,
            'gemini' => \Laravel\Ai\Enums\Lab::Gemini,
            'groq' => \Laravel\Ai\Enums\Lab::Groq,
            'mistral' => \Laravel\Ai\Enums\Lab::Mistral,
            'deepseek' => \Laravel\Ai\Enums\Lab::DeepSeek,
            'ollama' => \Laravel\Ai\Enums\Lab::Ollama,
            'xai' => \Laravel\Ai\Enums\Lab::xAI,
            'cohere' => \Laravel\Ai\Enums\Lab::Cohere,
        ];

        $key = strtolower($provider);

        if (! isset($map[$key])) {
            throw new \InvalidArgumentException(
                "Unknown AI provider: {$provider}. Supported: " . implode(', ', array_keys($map))
            );
        }

        return $map[$key];
    }
}
