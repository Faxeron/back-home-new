<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Finance\ReportBuilderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BuildCashflowMonthCommand extends Command
{
    protected $signature = 'reports:build-month
                            {--month= : Month in YYYY-MM format (default: current month)}
                            {--company= : Company ID (required)}
                            {--from= : From month for batch (YYYY-MM)}
                            {--to= : To month for batch (YYYY-MM)}
                            {--force : Force rebuild even if period is CLOSED}
                            {--tenant=1 : Tenant ID}';

    protected $description = 'Build monthly cashflow reports from daily aggregations';

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

            $this->info("Building monthly reports from {$from->format('Y-m')} to {$to->format('Y-m')}...");

            $current = $from->copy();
            $count = 0;

            while ($current <= $to) {
                $yearMonth = $current->format('Y-m');
                $result = $service->rebuildCashflowMonth($yearMonth, $force);

                if ($result['skipped'] ?? false) {
                    $this->info("  ✓ $yearMonth: {$result['reason']}");
                } elseif ($result['success'] ?? false) {
                    $this->info("  ✓ $yearMonth ({$result['records']} records)");
                    $count += $result['records'];
                } else {
                    $this->warn("  ✗ $yearMonth: Error");
                }

                $current->addMonth();
            }

            $this->info("Total records: $count");
        } else {
            // Single month
            $yearMonth = $this->option('month') ?? date('Y-m');

            $this->info("Building monthly cashflow for $yearMonth...");

            $result = $service->rebuildCashflowMonth($yearMonth, $force);

            if ($result['skipped'] ?? false) {
                $this->info("Skipped: {$result['reason']}");

                return self::SUCCESS;
            }

            if ($result['success'] ?? false) {
                $this->info("✓ Built {$result['records']} records");
                $this->info("✓ Monthly summary updated");

                return self::SUCCESS;
            }

            $this->error('Failed to build cashflow month');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
