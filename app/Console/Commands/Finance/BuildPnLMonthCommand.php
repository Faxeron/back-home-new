<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Finance\ReportBuilderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BuildPnLMonthCommand extends Command
{
    protected $signature = 'reports:build-pnl
                            {--month= : Month in YYYY-MM format (default: current month)}
                            {--company= : Company ID (required)}
                            {--from= : From month for batch (YYYY-MM)}
                            {--to= : To month for batch (YYYY-MM)}
                            {--force : Force rebuild even if period is CLOSED}
                            {--tenant=1 : Tenant ID}';

    protected $description = 'Build P&L (Profit & Loss) monthly reports';

    public function handle(): int
    {
        $tenantId = (int)$this->option('tenant');
        $companyId = (int)$this->option('company');

        if (!$companyId) {
            $this->error('--company option is required');

            return self::FAILURE;
        }

        $service = new ReportBuilderService();
        $service->setContext($tenantId, $companyId);
        $force = $this->option('force');

        if ($this->option('from') && $this->option('to')) {
            // Batch mode
            $from = Carbon::createFromFormat('Y-m', $this->option('from'));
            $to = Carbon::createFromFormat('Y-m', $this->option('to'));

            $this->info("Building P&L reports from {$from->format('Y-m')} to {$to->format('Y-m')}...");

            $current = $from->copy();
            $count = 0;

            while ($current <= $to) {
                $yearMonth = $current->format('Y-m');
                $result = $service->rebuildPnLMonth($yearMonth, $force);

                if ($result['skipped'] ?? false) {
                    $this->info("  ✓ $yearMonth: {$result['reason']}");
                } elseif ($result['success'] ?? false) {
                    $revenue = $result['revenue'] ?? 0;
                    $expenses = $result['expenses'] ?? 0;
                    $profit = $result['profit'] ?? 0;
                    $this->info("  ✓ $yearMonth - Revenue: $revenue, Expenses: $expenses, Profit: $profit");
                    $count++;
                } else {
                    $this->warn("  ✗ $yearMonth: Error");
                }

                $current->addMonth();
            }

            $this->info("Total months processed: $count");
        } else {
            // Single month
            $yearMonth = $this->option('month') ?? date('Y-m');

            $this->info("Building P&L for $yearMonth...");

            $result = $service->rebuildPnLMonth($yearMonth, $force);

            if ($result['skipped'] ?? false) {
                $this->info("Skipped: {$result['reason']}");

                return self::SUCCESS;
            }

            if ($result['success'] ?? false) {
                $revenue = $result['revenue'] ?? 0;
                $expenses = $result['expenses'] ?? 0;
                $profit = $result['profit'] ?? 0;
                $this->info("✓ P&L Built");
                $this->info("  Revenue: $revenue");
                $this->info("  Expenses: $expenses");
                $this->info("  Profit: $profit");

                return self::SUCCESS;
            }

            $this->error('Failed to build P&L');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
