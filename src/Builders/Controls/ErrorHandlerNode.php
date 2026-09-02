<?php

namespace Aftandilmmd\WorkflowAutomation\Builders\Controls;

use Aftandilmmd\WorkflowAutomation\Builders\NodeDefinition;
use Aftandilmmd\WorkflowAutomation\Enums\ErrorRoute;

/**
 * Routes failures to the 'notify', 'retry', 'ignore' or 'stop' port.
 */
class ErrorHandlerNode extends NodeDefinition
{
    public function nodeKey(): string
    {
        return 'error_handler';
    }

    /**
     * @param  array<int, array{match: string, route: ErrorRoute|string}>  $rules
     */
    public function rules(array $rules): static
    {
        return $this->set('rules', $rules);
    }

    /**
     * @param  string  $match  Regex matched against the error message.
     */
    public function rule(string $match, ErrorRoute|string $route): static
    {
        return $this->push('rules', ['match' => $match, 'route' => $route]);
    }

    public function defaultRoute(ErrorRoute|string $route): static
    {
        return $this->set('default_route', $route);
    }
}
