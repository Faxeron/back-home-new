<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTransactionClientId extends Command
{
    protected $signature = 'finance:backfill-contract-id';

    protected $description = 'Заполнить contract_id (из lead_id legacy receipts/spendings) в transactions новой БД';

    public function handle(): int
    {
        // Собираем маппинг transaction_id -> lead_id из legacy (back_home)
        $this->info('Читаю маппинг (transaction_id -> lead_id) из legacy.receipts / legacy.spendings...');

        $map = [];

        $receiptsMap = DB::connection('legacy')
            ->table('receipts')
            ->whereNotNull('lead_id')
            ->pluck('lead_id', 'transaction_id')
            ->toArray();

        $spendingsMap = DB::connection('legacy')
            ->table('spendings')
            ->whereNotNull('lead_id')
            ->pluck('lead_id', 'transaction_id')
            ->toArray();

        $map = $receiptsMap + $spendingsMap;

        $this->info('Найдено транзакций с lead_id: ' . count($map));

        if (empty($map)) {
            $this->warn('Маппинг пуст. Нечего заполнять.');
            return self::SUCCESS;
        }

        // Обновляем transactions в новой БД, только где contract_id NULL
        $updated = 0;
        $bar = $this->output->createProgressBar(count($map));
        $bar->start();

        foreach ($map as $transactionId => $leadId) {
            $affected = DB::connection('legacy_new')
                ->table('transactions')
                ->where('id', $transactionId)
                ->whereNull('contract_id')
                ->update(['contract_id' => $leadId]);

            $updated += $affected;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Обновлено записей: {$updated}");

        return self::SUCCESS;
    }
}
