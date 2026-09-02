<?php

namespace Aftandilmmd\WorkflowAutomation\Console\Commands;

use Aftandilmmd\WorkflowAutomation\Registry\NodeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeNodeBuilderCommand extends Command
{
    protected $signature = 'workflow:make-node-builder
        {node : The registered node key, e.g. send_mail}
        {--class= : Builder class name (defaults to the node key in PascalCase plus "Node")}
        {--namespace=App\\Workflows\\Builders : Namespace of the generated class}
        {--path= : Target directory (defaults to app/Workflows/Builders)}
        {--force : Overwrite an existing file}';

    protected $description = 'Generate a fluent builder class from a node config schema.';

    public function handle(NodeRegistry $registry): int
    {
        $key = $this->argument('node');
        $meta = $registry->getMeta($key);

        if (! $meta) {
            $this->components->error("Node [{$key}] is not registered.");

            return self::FAILURE;
        }

        $class = $this->option('class') ?: Str::studly($key).'Node';
        $namespace = trim($this->option('namespace'), '\\');
        $path = rtrim($this->option('path') ?: base_path('app/Workflows/Builders'), '/')."/{$class}.php";

        if (file_exists($path) && ! $this->option('force')) {
            $this->components->error("File already exists: {$path}. Use --force to overwrite.");

            return self::FAILURE;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $this->render($namespace, $class, $key, $meta['class']::configSchema()));

        $this->components->info("Builder created: {$path}");

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $schema
     */
    private function render(string $namespace, string $class, string $key, array $schema): string
    {
        $methods = implode("\n\n", array_map(fn (array $field) => $this->renderMethod($field), $schema));

        return <<<PHP
        <?php

        namespace {$namespace};

        use Aftandilmmd\\WorkflowAutomation\\Builders\\NodeDefinition;

        class {$class} extends NodeDefinition
        {
            public function nodeKey(): string
            {
                return '{$key}';
            }

        {$methods}
        }

        PHP;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function renderMethod(array $field): string
    {
        $key = $field['key'];
        $method = Str::camel($key);
        $type = $this->parameterType($field);
        $doc = $this->docBlock($field);
        $signature = $type === 'bool'
            ? "public function {$method}(bool \$value = true): static"
            : "public function {$method}({$type} \$value): static";

        return <<<PHP
        {$doc}    {$signature}
            {
                return \$this->set('{$key}', \$value);
            }
        PHP;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function parameterType(array $field): string
    {
        return match ($field['type'] ?? 'string') {
            'boolean'                                     => 'bool',
            'integer'                                     => 'int',
            'keyvalue', 'array', 'json', 'array_of_objects', 'multiselect' => 'array',
            'credential'                                  => 'int',
            default                                       => 'string',
        };
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function docBlock(array $field): string
    {
        $lines = [];

        if (! empty($field['options'])) {
            $options = array_map(
                fn ($option) => is_array($option) ? $option['value'] : $option,
                $field['options'],
            );
            $lines[] = 'Allowed values: '.implode(', ', $options).'.';
        }

        if (! empty($field['supports_expression'])) {
            $lines[] = 'Supports expressions, e.g. {{ item.field }}.';
        }

        if ($lines === []) {
            return '';
        }

        $body = implode("\n     * ", $lines);

        return "    /**\n     * {$body}\n     */\n";
    }
}
