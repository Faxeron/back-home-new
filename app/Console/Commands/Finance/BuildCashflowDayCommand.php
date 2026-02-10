<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Finance\ReportBuilderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BuildCashflowDayCommand extends Command
{
    protected $signature = 'reports:build-day
                            {--date= : Date in YYYY-MM-DD format (default: today)}
                            {--company= : Company ID (required)}
                            {--from= : From date for batch (YYYY-MM-DD)}
                            {--to= : To date for batch (YYYY-MM-DD)}
                            {--force : Force rebuild even if period is CLOSED}
                            {--tenant=1 : Tenant ID}';

    protected $description = 'Build daily cashflow reports';

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
            $from = Carbon::parse($this->option('from'));
            $to = Carbon::parse($this->option('to'));

            $this->info("Building daily reports from {$from->toDateString()} to {$to->toDateString()}...");

            $current = $from->copy();
            $count = 0;

            while ($current <= $to) {
                $result = $service->rebuildCashflowDay($current->toDateString(), $force);

                if ($result['skipped'] ?? false) {
                    $this->info("  ✓ {$current->toDateString()}: {$result['reason']}");
                } elseif ($result['success'] ?? false) {
                    $this->info("  ✓ {$current->toDateString()} ({$result['records']} records)");
                    $count += $result['records'];
                } else {
                    $this->warn("  ✗ {$current->toDateString()}: Error");
                }

                $current->addDay();
            }

            $this->info("Total records: $count");
        } else {
            // Single day
            $date = $this->option('date') ?? date('Y-m-d');

            $this->info("Building daily cashflow for $date...");

            $result = $service->rebuildCashflowDay($date, $force);

            if ($result['skipped'] ?? false) {
                $this->info("Skipped: {$result['reason']}");

                return self::SUCCESS;
            }

            if ($result['success'] ?? false) {
                $this->info("✓ Built {$result['records']} records");

                return self::SUCCESS;
            }

            $this->error('Failed to build cashflow');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
