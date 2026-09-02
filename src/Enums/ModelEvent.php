<?php

namespace Aftandilmmd\WorkflowAutomation\Enums;

enum ModelEvent: string
{
    case Created      = 'created';
    case Updated      = 'updated';
    case Deleted      = 'deleted';
    case Restored     = 'restored';
    case Saving       = 'saving';
    case Saved        = 'saved';
    case Creating     = 'creating';
    case Deleting     = 'deleting';
    case ForceDeleted = 'forceDeleted';
}
