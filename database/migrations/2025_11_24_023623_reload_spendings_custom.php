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

    private array $specialTypeMap = [
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

    private array $comboMap = [
        '2-1' => ['fond' => 1, 'item' => 1],
        '3-23' => ['fond' => 2, 'item' => 18],
        '3-24' => ['fond' => 3, 'item' => 19],
        '3-28' => ['fond' => 5, 'item' => 20],
        '4-29' => ['fond' => 5, 'item' => 21],
        '4-30' => ['fond' => 5, 'item' => 22],
        '4-31' => ['fond' => 5, 'item' => 23],
        '4-32' => ['fond' => 5, 'item' => 24],
        '4-33' => ['fond' => 5, 'item' => 25],
        '4-42' => ['fond' => 5, 'item' => 26],
        '5-2'  => ['fond' => 6, 'item' => 27],
        '5-3'  => ['fond' => 6, 'item' => 2],
        '5-4'  => ['fond' => 6, 'item' => 3],
        '5-5'  => ['fond' => 6, 'item' => 4],
        '5-6'  => ['fond' => 6, 'item' => 5],
        '5-7'  => ['fond' => 6, 'item' => 6],
        '5-8'  => ['fond' => 6, 'item' => 7],
        '5-9'  => ['fond' => 6, 'item' => 8],
        '5-10' => ['fond' => 6, 'item' => 9],
        '5-38' => ['fond' => 6, 'item' => 28],
        '5-39' => ['fond' => 6, 'item' => 29],
        '5-40' => ['fond' => 6, 'item' => 30],
        '5-41' => ['fond' => 6, 'item' => 31],
        '5-45' => ['fond' => 6, 'item' => 32],
        '5-46' => ['fond' => 6, 'item' => 33],
        '5-49' => ['fond' => 4, 'item' => 34],
        '5-50' => ['fond' => 4, 'item' => 35],
        '6-35' => ['fond' => 7, 'item' => 36],
        '7-11' => ['fond' => 4, 'item' => 10],
        '7-12' => ['fond' => 3, 'item' => 11],
        '7-13' => ['fond' => 4, 'item' => 12],
        '7-14' => ['fond' => 4, 'item' => 13],
        '7-15' => ['fond' => 4, 'item' => 14],
        '7-16' => ['fond' => 4, 'item' => 15],
        '7-44' => ['fond' => 4, 'item' => 37],
        '7-47' => ['fond' => 4, 'item' => 38],
        '8-18' => ['fond' => 8, 'item' => 17],
        '8-19' => ['fond' => 8, 'item' => 39],
        '8-20' => ['fond' => 8, 'item' => 40],
        '8-21' => ['fond' => 8, 'item' => 41],
        '8-22' => ['fond' => 8, 'item' => 42],
        '8-48' => ['fond' => 8, 'item' => 43],
        '9-25' => ['fond' => 9, 'item' => 44],
        '9-26' => ['fond' => 9, 'item' => 45],
        '9-27' => ['fond' => 9, 'item' => 46],
        '10-36' => ['fond' => 10, 'item' => 47],
        '10-37' => ['fond' => 10, 'item' => 48],
        '11-17' => ['fond' => 11, 'item' => 16],
        '11-51' => ['fond' => 11, 'item' => 49],
        '11-52' => ['fond' => 11, 'item' => 50],
        '11-53' => ['fond' => 11, 'item' => 51],
        '11-54' => ['fond' => 11, 'item' => 52],
        '11-55' => ['fond' => 11, 'item' => 53],
        '11-56' => ['fond' => 11, 'item' => 54],
        '11-57' => ['fond' => 11, 'item' => 55],
        '11-64' => ['fond' => 11, 'item' => 56],
        '11-65' => ['fond' => 11, 'item' => 57],
        '12-43' => ['fond' => 12, 'item' => 58],
        '12-58' => ['fond' => 12, 'item' => 59],
        '12-59' => ['fond' => 12, 'item' => 60],
        '12-60' => ['fond' => 12, 'item' => 61],
        '12-61' => ['fond' => 12, 'item' => 62],
        '12-62' => ['fond' => 12, 'item' => 63],
        '12-63' => ['fond' => 12, 'item' => 64],
    ];

    public function up(): void
    {
        if (DB::connection($this->connection)->getDriverName() === 'pgsql') {
            return;
        }

        $legacy = DB::connection('legacy');
        $legacyNew = DB::connection('legacy_new');

        $itemOldMap = $legacyNew->table('spending_items')->pluck('id', 'old_id')->toArray();
        $fundMap = $legacyNew->table('spending_funds')->pluck('id', 'id_old')->toArray();
        $contractMap = $legacyNew->table('contracts')->pluck('counterparty_id', 'id')->toArray();
        $spendingTypeNameMap = $legacy->table('spending_types')->pluck('name', 'id')->toArray();
        $existingTx = array_fill_keys($legacyNew->table('transactions')->pluck('id')->toArray(), true);

        $legacyNew->statement('SET FOREIGN_KEY_CHECKS=0');
        $legacyNew->table('spendings')->truncate();
        $legacyNew->statement('SET FOREIGN_KEY_CHECKS=1');

        $legacy->table('spendings')
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows) use ($legacyNew, $itemOldMap, $fundMap, $contractMap, $spendingTypeNameMap, $existingTx): void {
                $batch = [];

                foreach ($rows as $row) {
                    $newCashBox = $this->cashBoxMap[$row->cashbox_id] ?? null;

                    $newItem = null;
                    $newFond = null;

                    if (($row->fond_id ?? null) == 3 && ($row->spending_item_id ?? null) == 34 && $row->spending_type_id && isset($this->specialTypeMap[$row->spending_type_id])) {
                        $newItem = $this->specialTypeMap[$row->spending_type_id];
                        $newFond = 2;
                    } else {
                        $key = ($row->fond_id ?? '') . '-' . ($row->spending_item_id ?? '');
                        if (isset($this->comboMap[$key])) {
                            $newItem = $this->comboMap[$key]['item'];
                            $newFond = $this->comboMap[$key]['fond'];
                        } else {
                            if ($row->spending_item_id && isset($itemOldMap[$row->spending_item_id])) {
                                $newItem = $itemOldMap[$row->spending_item_id];
                            }
                            if ($row->fond_id && isset($fundMap[$row->fond_id])) {
                                $newFond = $fundMap[$row->fond_id];
                            }
                        }
                    }

                    $contractId = $row->lead_id ?? null;
                    $counterpartyId = $contractId && isset($contractMap[$contractId]) ? $contractMap[$contractId] : null;

                    $paymentDate = $row->date ? Carbon::parse($row->date)->toDateString() : null;

                    $transactionId = $row->transaction_id ?? null;
                    if ($transactionId && !isset($existingTx[$transactionId])) {
                        $transactionId = null;
                    }

                    $spendingTypeId = $row->spending_type_id ?? null;
                    $spendingTypeName = $spendingTypeId && isset($spendingTypeNameMap[$spendingTypeId]) ? $spendingTypeNameMap[$spendingTypeId] : null;

                    $batch[] = [
                        'id' => $row->id,
                        'old_id' => $row->id,
                        'company_id' => 1,
                        'tenant_id' => 1,
                        'cashbox_id' => $newCashBox,
                        'transaction_id' => $transactionId,
                        'spending_item_id' => $newItem,
                        'fond_id' => $newFond,
                        'contract_id' => $contractId,
                        'counterparty_id' => $counterpartyId,
                        'sum' => $row->summ ?? 0,
                        'description' => $row->description ?? null,
                        'created_by' => null,
                        'updated_by' => null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                        'payment_date' => $paymentDate,
                        'spending_type_id' => $spendingTypeId,
                        'spending_type_name' => $spendingTypeName,
                        'spent_to_user_id' => null,
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

