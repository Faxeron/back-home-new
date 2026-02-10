<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Finance\ReportBuilderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class InitializeReportsCommand extends Command
{
    protected $signature = 'reports:initialize
                            {--company= : Company ID (required)}
                            {--days=60 : Days back to initialize (default: 60)}
                            {--force : Force rebuild even if periods are CLOSED}
                            {--tenant=1 : Tenant ID}';

    protected $description = 'Initialize all reports for the current and past months (initial data population)';

    public function handle(): int
    {
        $tenantId = (int)$this->option('tenant');
        $companyId = (int)$this->option('company');
        $daysBack = (int)$this->option('days');
        $force = $this->option('force');

        if (!$companyId) {
            $this->error('--company option is required');

            return self::FAILURE;
        }

        $service = new ReportBuilderService();
        $service->setContext($tenantId, $companyId);

        $fromDate = Carbon::now()->subDays($daysBack);
        $toDate = Carbon::now();

        $this->info("Initializing financial reports for company $companyId");
        $this->info("Period: {$fromDate->toDateString()} to {$toDate->toDateString()}");
        $this->newLine();

        // Step 1: Build daily cashflows
        $this->info('Step 1/5: Building daily cashflows...');
        $current = $fromDate->copy();
        $dayCount = 0;

        while ($current <= $toDate) {
            $result = $service->rebuildCashflowDay($current->toDateString(), $force);

            if ($result['skipped'] ?? false) {
                $this->line("  ✓ {$current->toDateString()}: {$result['reason']}");
            } elseif ($result['success'] ?? false) {
                $this->line("  ✓ {$current->toDateString()} ({$result['records']} records)");
                $dayCount++;
            }

            $current->addDay();
        }

        $this->info("  Completed: $dayCount days\n");

        // Step 2: Build monthly cashflows
        $this->info('Step 2/5: Building monthly cashflows...');
        $fromMonth = $fromDate->copy()->startOfMonth();
        $toMonth = $toDate->copy()->startOfMonth();
        $monthCount = 0;

        $current = $fromMonth->copy();

        while ($current <= $toMonth) {
            $yearMonth = $current->format('Y-m');
            $result = $service->rebuildCashflowMonth($yearMonth, $force);

            if ($result['skipped'] ?? false) {
                $this->line("  ✓ $yearMonth: {$result['reason']}");
            } elseif ($result['success'] ?? false) {
                $this->line("  ✓ $yearMonth ({$result['records']} records)");
                $monthCount++;
            }

            $current->addMonth();
        }

        $this->info("  Completed: $monthCount months\n");

        // Step 3: Build P&L for each month
        $this->info('Step 3/5: Building P&L reports...');
        $pnlCount = 0;

        $current = $fromMonth->copy();

        while ($current <= $toMonth) {
            $yearMonth = $current->format('Y-m');
            $result = $service->rebuildPnLMonth($yearMonth, $force);

            if ($result['skipped'] ?? false) {
                $this->line("  ✓ $yearMonth: {$result['reason']}");
            } elseif ($result['success'] ?? false) {
                $this->line("  ✓ $yearMonth");
                $pnlCount++;
            }

            $current->addMonth();
        }

        $this->info("  Completed: $pnlCount months\n");

        // Step 4: Snapshot debts for last 30 days
        $this->info('Step 4/5: Snapshotting debts...');
        $debtDays = min(30, $daysBack);
        $fromDebtDate = Carbon::now()->subDays($debtDays);
        $debtCount = 0;

        $current = $fromDebtDate->copy();

        while ($current <= $toDate) {
            $result = $service->snapshotDebts($current->toDateString());

            if ($result['success'] ?? false) {
                $arRecords = $result['ar_records'] ?? 0;
                $apRecords = $result['ap_records'] ?? 0;
                $this->line("  ✓ {$current->toDateString()} (AR: $arRecords, AP: $apRecords)");
                $debtCount++;
            }

            $current->addDay();
        }

        $this->info("  Completed: $debtCount days\n");

        // Step 5: Reconcile each month
        $this->info('Step 5/5: Reconciling months...');
        $reconcileCount = 0;

        $current = $fromMonth->copy();

        while ($current <= $toMonth) {
            $yearMonth = $current->format('Y-m');
            $result = $service->reconcileMonth($yearMonth);

            $valid = $result['valid'] ?? false;
            $issues = $result['issues'] ?? [];

            if ($valid) {
                $this->line("  ✓ $yearMonth: Valid");
            } else {
                $this->line("  ⚠ $yearMonth: " . count($issues) . ' issue(s)');
                foreach ($issues as $issue) {
                    $this->line("     - $issue");
                }
            }

            $reconcileCount++;

            $current->addMonth();
        }

        $this->info("  Completed: $reconcileCount months\n");

        $this->info('✓ Financial reports initialization complete!');

        return self::SUCCESS;
    }
}
