<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTransactionRelatedId extends Command
{
    protected $signature = 'finance:backfill-related-id';

    protected $description = 'Заполнить transactions.related_id из receipts/spendings (по transaction_id) в новой БД';

    public function handle(): int
    {
        $this->info('Читаю receipts/spendings (legacy_new) для маппинга transaction_id -> source id...');

        $receiptsMap = DB::connection('legacy_new')
            ->table('receipts')
            ->whereNotNull('transaction_id')
            ->pluck('id', 'transaction_id')
            ->toArray();

        $spendingsMap = DB::connection('legacy_new')
            ->table('spendings')
            ->whereNotNull('transaction_id')
            ->pluck('id', 'transaction_id')
            ->toArray();

        $map = $receiptsMap + $spendingsMap;

        $this->info('Найдено транзакций с source-id: ' . count($map));

        if (empty($map)) {
            $this->warn('Маппинг пуст. Нечего заполнять.');
            return self::SUCCESS;
        }

        $updated = 0;
        $bar = $this->output->createProgressBar(count($map));
        $bar->start();

        foreach ($map as $transactionId => $sourceId) {
            $affected = DB::connection('legacy_new')
                ->table('transactions')
                ->where('id', $transactionId)
                ->whereNull('related_id')
                ->update(['related_id' => $sourceId]);

            $updated += $affected;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Обновлено транзакций: {$updated}");

        return self::SUCCESS;
    }
}
