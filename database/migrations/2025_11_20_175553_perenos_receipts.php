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

    public function up(): void
    {
        DB::connection('legacy')
            ->table('receipts')
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows): void {
                $batch = [];

                foreach ($rows as $row) {
                    $oldCashBoxId = $row->cashbox_id ?? null;
                    $newCashBoxId = $this->cashBoxMap[$oldCashBoxId] ?? null;

                    $batch[] = [
                        'id' => $row->id,
                        'company_id' => 1,
                        'cashbox_id' => $newCashBoxId,
                        'transaction_id' => $row->transaction_id ?? null,
                        'contract_id' => $row->lead_id ?? null,
                        'summ' => $row->summ ?? 0,
                        'description' => $row->date_income ?? null,
                        'created_by' => null,
                        'updated_by' => null,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ];
                }

                if ($batch) {
                    DB::connection('legacy_new')->table('receipts')->insertOrIgnore($batch);
                }
            });
    }

    public function down(): void
    {
        DB::connection('legacy')
            ->table('receipts')
            ->orderBy('id')
            ->chunkById(500, function (Collection $rows): void {
                $ids = $rows->pluck('id')->all();
                DB::connection('legacy_new')->table('receipts')->whereIn('id', $ids)->delete();
            });
    }
};
