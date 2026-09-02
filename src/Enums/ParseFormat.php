<?php

namespace Aftandilmmd\WorkflowAutomation\Enums;

enum ParseFormat: string
{
    case Json     = 'json';
    case Csv      = 'csv';
    case KeyValue = 'key_value';
}
