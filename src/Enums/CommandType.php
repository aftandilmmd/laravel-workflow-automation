<?php

namespace Aftandilmmd\WorkflowAutomation\Enums;

enum CommandType: string
{
    case Artisan = 'artisan';
    case Shell   = 'shell';
}
