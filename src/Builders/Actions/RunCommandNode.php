<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Actions;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\CommandType;

class RunCommandNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'run_command';
    }

    public static function artisan(string $command): static
    {
        return static::make()->commandType(CommandType::Artisan)->command($command);
    }

    public static function shell(string $command): static
    {
        return static::make()->commandType(CommandType::Shell)->command($command);
    }

    public function commandType(CommandType|string $type): static
    {
        return $this->set('command_type', $type);
    }

    public function command(string $command): static
    {
        return $this->set('command', $command);
    }

    /**
     * @param  array<string, string>  $arguments
     */
    public function arguments(array $arguments): static
    {
        return $this->set('arguments', $arguments);
    }

    public function argument(string $name, string $value): static
    {
        return $this->put('arguments', $name, $value);
    }

    public function timeout(int $seconds): static
    {
        return $this->set('timeout', $seconds);
    }

    public function workingDirectory(string $directory): static
    {
        return $this->set('working_directory', $directory);
    }

    public function includeOutput(bool $include = true): static
    {
        return $this->set('include_output', $include);
    }
}
