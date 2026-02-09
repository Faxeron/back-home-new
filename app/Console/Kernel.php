<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array<class-string>
     */
    protected $commands = [
        \App\Console\Commands\BackfillTransactionClientId::class,
        \App\Console\Commands\BackfillTransactionCounterparty::class,
        \App\Console\Commands\BackfillTransactionRelatedId::class,
        \App\Console\Commands\BackfillTransactionCashflowItems::class,
        \App\Console\Commands\SyncUsersToLegacyNew::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
