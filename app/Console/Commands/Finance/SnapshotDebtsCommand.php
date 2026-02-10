<?php

declare(strict_types=1);

namespace App\Console\Commands\Finance;

use App\Services\Finance\ReportBuilderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SnapshotDebtsCommand extends Command
{
    protected $signature = 'reports:snapshot-debts
                            {--date= : Date in YYYY-MM-DD format (default: today)}
                            {--company= : Company ID (required)}
                            {--from= : From date for batch (YYYY-MM-DD)}
                            {--to= : To date for batch (YYYY-MM-DD)}
                            {--tenant=1 : Tenant ID}';

    protected $description = 'Snapshot AR/AP (Accounts Receivable/Payable) daily';

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
            $from = Carbon::parse($this->option('from'));
            $to = Carbon::parse($this->option('to'));

            $this->info("Snapshotting debts from {$from->toDateString()} to {$to->toDateString()}...");

            $current = $from->copy();
            $totalRecords = 0;

            while ($current <= $to) {
                $result = $service->snapshotDebts($current->toDateString());

                if ($result['success'] ?? false) {
                    $arRecords = $result['ar_records'] ?? 0;
                    $apRecords = $result['ap_records'] ?? 0;
                    $this->info("  ✓ {$current->toDateString()} (AR: $arRecords, AP: $apRecords)");
                    $totalRecords += $arRecords + $apRecords;
                } else {
                    $this->warn("  ✗ {$current->toDateString()}: Error");
                }

                $current->addDay();
            }

            $this->info("Total debt records: $totalRecords");
        } else {
            // Single day
            $date = $this->option('date') ?? date('Y-m-d');

            $this->info("Snapshotting debts for $date...");

            $result = $service->snapshotDebts($date);

            if ($result['success'] ?? false) {
                $arRecords = $result['ar_records'] ?? 0;
                $apRecords = $result['ap_records'] ?? 0;
                $this->info("✓ Snapshot complete");
                $this->info("  AR records: $arRecords");
                $this->info("  AP records: $apRecords");

                return self::SUCCESS;
            }

            $this->error('Failed to snapshot debts');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
