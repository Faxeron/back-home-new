<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('spending_funds', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_old')->nullable()->after('id');
            $table->unique('id_old');
        });

        $seed = [
            1 => 'Дивиденды',
            2 => 'Расходы на объекты',
            3 => 'Офисные расходы',
            4 => 'IT инфраструктура',
            5 => 'Развитие',
            6 => 'Реклама',
            7 => 'Фонд Складской',
            8 => 'Статические расходы',
            9 => 'ФОТ',
            10 => 'Ремонты',
            11 => 'Филиалы',
            12 => 'Банковские расходы',
            13 => 'Налоги',
        ];

        foreach ($seed as $idOld => $name) {
            DB::connection('legacy_new')
                ->table('spending_funds')
                ->updateOrInsert(
                    ['name' => $name],
                    [
                        'id_old' => $idOld,
                        'is_active' => true,
                        'updated_at' => now(),
                    ]
                );
        }
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('spending_funds', function (Blueprint $table): void {
            $table->dropUnique(['id_old']);
            $table->dropColumn('id_old');
        });
    }
};
