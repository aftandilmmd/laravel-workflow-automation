<?php

namespace Aftandilmmd\WorkflowAutomation\Enums;

enum WebhookAuthType: string
{
    case None      = 'none';
    case Basic     = 'basic';
    case Bearer    = 'bearer';
    case HeaderKey = 'header_key';
}
