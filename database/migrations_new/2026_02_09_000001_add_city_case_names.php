<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('cities')) {
            return;
        }

        if (!$schema->hasColumn('cities', 'name_prepositional')) {
            $schema->table('cities', function (Blueprint $table): void {
                $table->string('name_prepositional')->nullable()->after('name');
            });
        }

        if (!$schema->hasColumn('cities', 'name_genitive')) {
            $schema->table('cities', function (Blueprint $table): void {
                $table->string('name_genitive')->nullable()->after('name_prepositional');
            });
        }

        $mapByName = [
            'Сургут' => ['prep' => 'Сургуте', 'gen' => 'Сургута'],
            'Нефтеюганск' => ['prep' => 'Нефтеюганске', 'gen' => 'Нефтеюганска'],
            'Нижневартовск' => ['prep' => 'Нижневартовске', 'gen' => 'Нижневартовска'],
            'Ханты-Мансийск' => ['prep' => 'Ханты-Мансийске', 'gen' => 'Ханты-Мансийска'],
            'Нягань' => ['prep' => 'Нягани', 'gen' => 'Нягани'],
            'Когалым' => ['prep' => 'Когалыме', 'gen' => 'Когалыма'],
            'Тюмень' => ['prep' => 'Тюмени', 'gen' => 'Тюмени'],
            'Пыть-Ях' => ['prep' => 'Пыть-Яхе', 'gen' => 'Пыть-Яха'],
            'Сентябрьский' => ['prep' => 'Сентябрьском', 'gen' => 'Сентябрьского'],
            'Югорск' => ['prep' => 'Югорске', 'gen' => 'Югорска'],
            'Новый Уренгой' => ['prep' => 'Новом Уренгое', 'gen' => 'Нового Уренгоя'],
            'Тюменский район' => ['prep' => 'Тюменском районе', 'gen' => 'Тюменского района'],
            'Тобольск' => ['prep' => 'Тобольске', 'gen' => 'Тобольска'],
            'ЯНАО' => ['prep' => 'ЯНАО', 'gen' => 'ЯНАО'],
        ];

        $db = DB::connection($this->connection);

        foreach ($mapByName as $name => $cases) {
            $db->table('cities')
                ->where('name', $name)
                ->where(function ($q) {
                    $q->whereNull('name_prepositional')->orWhere('name_prepositional', '');
                })
                ->update(['name_prepositional' => $cases['prep']]);

            $db->table('cities')
                ->where('name', $name)
                ->where(function ($q) {
                    $q->whereNull('name_genitive')->orWhere('name_genitive', '');
                })
                ->update(['name_genitive' => $cases['gen']]);
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('cities') && $schema->hasColumn('cities', 'name_genitive')) {
            $schema->table('cities', function (Blueprint $table): void {
                $table->dropColumn('name_genitive');
            });
        }

        if ($schema->hasTable('cities') && $schema->hasColumn('cities', 'name_prepositional')) {
            $schema->table('cities', function (Blueprint $table): void {
                $table->dropColumn('name_prepositional');
            });
        }
    }
};
