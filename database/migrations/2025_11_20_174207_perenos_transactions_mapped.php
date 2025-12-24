<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $cashBoxMap = [
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

    protected array $cardCashBoxes = [1, 5, 15, 16];

    public function up(): void
    {
        DB::connection('legacy')
            ->table('transactions')
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows): void {
                $batch = [];
                foreach ($rows as $row) {
                    $oldCashBoxId = $row->cashbox_id ?? null;
                    $newCashBoxId = $this->cashBoxMap[$oldCashBoxId] ?? null;
                    $isIncome = ($row->summ ?? 0) >= 0;

                    $batch[] = [
                        'id' => $row->id,
                        'is_paid' => 1,
                        'date_is_paid' => null,
                        'is_completed' => 0,
                        'date_is_completed' => null,
                        'sum' => abs((float) ($row->summ ?? 0)),
                        'id_cash_box' => $newCashBoxId,
                        'transaction_type_id' => $isIncome ? 1 : 2,
                        'payment_method_id' => in_array($oldCashBoxId, $this->cardCashBoxes, true) ? 2 : 1,
                        'id_project' => 1,
                        'notes' => $row->description ?? null,
                        'created_by' => $row->user_id ?? null,
                        'updated_by' => $row->user_id ?? null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ];
                }

                if ($batch) {
                    DB::connection('legacy_new')->table('transactions')->insertOrIgnore($batch);
                }
            });
    }

    public function down(): void
    {
        DB::connection('legacy')
            ->table('transactions')
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows): void {
                $ids = $rows->pluck('id')->all();
                DB::connection('legacy_new')->table('transactions')->whereIn('id', $ids)->delete();
            });
    }
};
