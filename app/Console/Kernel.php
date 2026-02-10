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
        // Financial Reports - Materialized Tables Scheduler
        // Default to company_id = 1 (can be extended to multiple companies)

        // Hourly: Rebuild today + yesterday cashflow (for dashboard realtime updates)
        $schedule->command('reports:build-day --company=1 --date=' . date('Y-m-d'))
            ->hourly()
            ->withoutOverlapping(10)
            ->onSuccess(function () {
                \Log::channel('finance')->info('Hourly cashflow rebuild completed');
            })
            ->onFailure(function () {
                \Log::channel('finance')->error('Hourly cashflow rebuild failed');
            });

        // Nightly (00:05): Rebuild current month cashflow
        $schedule->command('reports:build-month --company=1 --month=' . date('Y-m'))
            ->dailyAt('00:05')
            ->withoutOverlapping(20)
            ->onSuccess(function () {
                \Log::channel('finance')->info('Nightly monthly cashflow rebuild completed');
            });

        // Nightly (00:10): Rebuild P&L for current month
        $schedule->command('reports:build-pnl --company=1 --month=' . date('Y-m'))
            ->dailyAt('00:10')
            ->withoutOverlapping(20)
            ->onSuccess(function () {
                \Log::channel('finance')->info('Nightly P&L rebuild completed');
            });

        // Daily (03:00): Snapshot AR/AP debt state
        $schedule->command('reports:snapshot-debts --company=1 --date=' . date('Y-m-d'))
            ->dailyAt('03:00')
            ->withoutOverlapping(15)
            ->onSuccess(function () {
                \Log::channel('finance')->info('Daily debt snapshot completed');
            });
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
