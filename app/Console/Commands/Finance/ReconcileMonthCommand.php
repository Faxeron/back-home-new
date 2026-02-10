<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Finance\ReportBuilderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReconcileMonthCommand extends Command
{
    protected $signature = 'reports:reconcile
                            {--month= : Month in YYYY-MM format (default: current month)}
                            {--company= : Company ID (required)}
                            {--from= : From month for batch (YYYY-MM)}
                            {--to= : To month for batch (YYYY-MM)}
                            {--tenant=1 : Tenant ID}';

    protected $description = 'Reconcile and validate financial data for a month';

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

        if ($this->option('from') && $this->option('to')) {
            // Batch mode
            $from = Carbon::createFromFormat('Y-m', $this->option('from'));
            $to = Carbon::createFromFormat('Y-m', $this->option('to'));

            $this->info("Reconciling months from {$from->format('Y-m')} to {$to->format('Y-m')}...");

            $current = $from->copy();
            $totalIssues = 0;

            while ($current <= $to) {
                $yearMonth = $current->format('Y-m');
                $result = $service->reconcileMonth($yearMonth);

                $valid = $result['valid'] ?? false;
                $issues = $result['issues'] ?? [];
                $issueCount = count($issues);

                if ($valid) {
                    $this->info("  ✓ $yearMonth: Valid (no issues)");
                } else {
                    $this->warn("  ⚠ $yearMonth: $issueCount issues found");
                    foreach ($issues as $issue) {
                        $this->line("     - $issue");
                    }
                    $totalIssues += $issueCount;
                }

                $current->addMonth();
            }

            if ($totalIssues === 0) {
                $this->info("All months are valid ✓");
            } else {
                $this->warn("Total issues found: $totalIssues");
            }
        } else {
            // Single month
            $yearMonth = $this->option('month') ?? date('Y-m');

            $this->info("Reconciling $yearMonth...");

            $result = $service->reconcileMonth($yearMonth);

            $valid = $result['valid'] ?? false;
            $issues = $result['issues'] ?? [];

            if ($valid) {
                $this->info("✓ Month is valid - no issues found");

                return self::SUCCESS;
            }

            $this->warn("⚠ Issues found:");
            foreach ($issues as $issue) {
                $this->line("  - $issue");
            }

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
