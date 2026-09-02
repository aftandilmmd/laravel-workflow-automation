<?php

namespace Aftandilmmd\WorkflowAutomation\Enums;

enum WorkflowTriggerOn: string
{
    case Completed = 'completed';
    case Failed    = 'failed';
    case Any       = 'any';
}
