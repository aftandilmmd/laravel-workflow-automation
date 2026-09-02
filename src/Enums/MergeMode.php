<?php

namespace Aftandilmmd\WorkflowAutomation\Enums;

enum MergeMode: string
{
    case Append  = 'append';
    case Zip     = 'zip';
    case WaitAll = 'wait_all';
}
