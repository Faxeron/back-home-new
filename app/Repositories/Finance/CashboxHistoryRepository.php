<?php

namespace App\Repositories\Finance;

use Illuminate\Support\Facades\DB;

class CashboxHistoryRepository
{
    public function add(int $cashboxId, ?int $transactionId, float $balanceAfter): void
    {
        DB::connection('legacy_new')->table('cashbox_history')->insert([
            'cashbox_id' => $cashboxId,
            'transaction_id' => $transactionId,
            'balance_after' => $balanceAfter,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
