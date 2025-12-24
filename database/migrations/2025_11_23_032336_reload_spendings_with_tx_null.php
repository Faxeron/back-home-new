<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $cashBoxMap = [
        1 => 1,
        2 => 2,
        3 => 4,
        4 => 5,
        5 => 9,
        12 => 10,
        13 => 11,
        14 => 3,
        15 => 7,
        16 => 6,
    ];

    private array $spendingTypeMap = [
        3 => 65,
        4 => 66,
        5 => 67,
        6 => 68,
        7 => 69,
        8 => 70,
        9 => 71,
        10 => 72,
        11 => 73,
        12 => 74,
        13 => 75,
        14 => 76,
        15 => 77,
        16 => 78,
        17 => 79,
        18 => 80,
        19 => 81,
        20 => 82,
        21 => 83,
        24 => 84,
    ];

    private array $overrideItemMap = [
        18 => 17,
        19 => 39,
        20 => 40,
        21 => 41,
        48 => 43,
        34 => 74,
    ];

    public function up(): void
    {
        $legacy = DB::connection('legacy');
        $legacyNew = DB::connection('legacy_new');

        $fundMap = $legacyNew->table('spending_funds')->pluck('id', 'id_old')->toArray();
        $itemOldMap = $legacyNew->table('spending_items')->pluck('id', 'old_id')->toArray();
        $contractMap = $legacyNew->table('contracts')->pluck('counterparty_id', 'id')->toArray();
        $existingTx = array_fill_keys($legacyNew->table('transactions')->pluck('id')->toArray(), true);

        $legacyNew->statement('SET FOREIGN_KEY_CHECKS=0');
        $legacyNew->table('spendings')->truncate();
        $legacyNew->statement('SET FOREIGN_KEY_CHECKS=1');

        $legacy->table('spendings')
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows) use ($legacyNew, $fundMap, $itemOldMap, $contractMap, $existingTx): void {
                $batch = [];

                foreach ($rows as $row) {
                    $oldCashBoxId = $row->cashbox_id ?? null;
                    $newCashBoxId = $this->cashBoxMap[$oldCashBoxId] ?? null;

                    $originalItemId = $row->spending_item_id ?? null;
                    $itemId = null;
                    if ($originalItemId && isset($this->overrideItemMap[$originalItemId])) {
                        $itemId = $this->overrideItemMap[$originalItemId];
                    } elseif ($originalItemId == 34 && $row->spending_type_id && isset($this->spendingTypeMap[$row->spending_type_id])) {
                        $itemId = $this->spendingTypeMap[$row->spending_type_id];
                    } elseif ($originalItemId && isset($itemOldMap[$originalItemId])) {
                        $itemId = $itemOldMap[$originalItemId];
                    }

                    if ($row->fond_id == 8) {
                        $fundId = 8;
                    } elseif ($row->fond_id == 2) {
                        $fundId = 3;
                    } elseif ($row->fond_id && isset($fundMap[$row->fond_id])) {
                        $fundId = $fundMap[$row->fond_id];
                    } else {
                        $fundId = null;
                    }

                    $contractId = $row->lead_id ?? null;
                    $counterpartyId = $contractId && isset($contractMap[$contractId]) ? $contractMap[$contractId] : null;

                    $paymentDate = $row->date ? Carbon::parse($row->date)->toDateString() : null;

                    $transactionId = $row->transaction_id ?? null;
                    if ($transactionId && !isset($existingTx[$transactionId])) {
                        $transactionId = null;
                    }

                    $batch[] = [
                        'id' => $row->id,
                        'old_id' => $row->id,
                        'company_id' => 1,
                        'cashbox_id' => $newCashBoxId,
                        'transaction_id' => $transactionId,
                        'spending_item_id' => $itemId,
                        'fond_id' => $fundId,
                        'contract_id' => $contractId,
                        'sum' => $row->summ ?? 0,
                        'description' => $row->description ?? null,
                        'created_by' => null,
                        'updated_by' => null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                        'tenant_id' => 1,
                        'counterparty_id' => $counterpartyId,
                        'spent_to_user_id' => null,
                        'payment_date' => $paymentDate,
                    ];
                }

                if ($batch) {
                    $legacyNew->table('spendings')->insertOrIgnore($batch);
                }
            });
    }

    public function down(): void
    {
        $legacyNew = DB::connection('legacy_new');
        $legacyNew->statement('SET FOREIGN_KEY_CHECKS=0');
        $legacyNew->table('spendings')->truncate();
        $legacyNew->statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
