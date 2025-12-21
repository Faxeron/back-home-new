<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTransactionCounterparty extends Command
{
    protected $signature = 'finance:backfill-counterparty';

    protected $description = 'Заполнить counterparty_id в transactions по contract_id из contracts (legacy_new)';

    public function handle(): int
    {
        $this->info('Читаю contracts (legacy_new) для маппинга contract_id -> counterparty_id...');

        $map = DB::connection('legacy_new')
            ->table('contracts')
            ->whereNotNull('counterparty_id')
            ->pluck('counterparty_id', 'id')
            ->toArray();

        $this->info('Найдено контрактов с counterparty_id: ' . count($map));

        if (empty($map)) {
            $this->warn('Контракты с counterparty_id не найдены. Нечего заполнять.');
            return self::SUCCESS;
        }

        $updated = 0;
        $bar = $this->output->createProgressBar(count($map));
        $bar->start();

        foreach ($map as $contractId => $counterpartyId) {
            $affected = DB::connection('legacy_new')
                ->table('transactions')
                ->where('contract_id', $contractId)
                ->whereNull('counterparty_id')
                ->update(['counterparty_id' => $counterpartyId]);

            $updated += $affected;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Обновлено транзакций: {$updated}");

        return self::SUCCESS;
    }
}
