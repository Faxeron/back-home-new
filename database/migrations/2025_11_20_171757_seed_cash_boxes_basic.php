<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rows = [
            ['name' => 'Стройдвор', 'is_active' => true, 'balance' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Касса Тюмень', 'is_active' => true, 'balance' => 0, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Копилка', 'is_active' => true, 'balance' => 0, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::connection('legacy_new')->table('cash_boxes')->insert($rows);
    }

    public function down(): void
    {
        DB::connection('legacy_new')
            ->table('cash_boxes')
            ->whereIn('name', ['Стройдвор', 'Касса Тюмень', 'Копилка'])
            ->delete();
    }
};
