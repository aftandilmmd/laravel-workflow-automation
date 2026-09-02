<?php

namespace Aftandilmmd\WorkflowAutomation\Enums;

enum ScheduleInterval: string
{
    case Minutes    = 'minutes';
    case Hours      = 'hours';
    case Days       = 'days';
    case CustomCron = 'custom_cron';
}
