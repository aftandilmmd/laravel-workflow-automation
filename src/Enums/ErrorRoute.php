<?php

namespace Aftandilmmd\WorkflowAutomation\Enums;

enum ErrorRoute: string
{
    case Notify = 'notify';
    case Retry  = 'retry';
    case Ignore = 'ignore';
    case Stop   = 'stop';
}
