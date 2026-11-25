<?php

declare(strict_types=1);

use App\Console\Commands\CheckProxyNodesCommand;
use App\Console\Commands\CleanupLogsCommand;
use App\Console\Commands\PruneProxyNodeHealthChecksCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command(CleanupLogsCommand::class)->dailyAt('03:00');
Schedule::command(CheckProxyNodesCommand::class)
    ->everyFiveMinutes()
    ->withoutOverlapping();
Schedule::command(PruneProxyNodeHealthChecksCommand::class)
    ->dailyAt('03:30')
    ->withoutOverlapping();
