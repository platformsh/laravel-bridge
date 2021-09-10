<?php

namespace Platformsh\LaravelBridge\CronExpression;

use Cron\CronExpression;
use Illuminate\Support\ServiceProvider;

class CronExpressionServiceProvider extends ServiceProvider
{
    public $bindings = [
        CronExpression::class => PSHCronExpression::class,
    ];
}
