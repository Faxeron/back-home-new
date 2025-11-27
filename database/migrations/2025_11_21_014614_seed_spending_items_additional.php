<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $items = [
            ['fond_id' => 2, 'name' => 'Септик'],
            ['fond_id' => 2, 'name' => 'Колодец'],
            ['fond_id' => 2, 'name' => 'Дренажный тоннель'],
            ['fond_id' => 2, 'name' => 'Комплектующие'],
            ['fond_id' => 2, 'name' => 'Доставка до города'],
            ['fond_id' => 2, 'name' => 'Материалы'],
            ['fond_id' => 2, 'name' => 'Спецтехника - Экскаватор'],
            ['fond_id' => 2, 'name' => 'ЗП Менеджер'],
            ['fond_id' => 2, 'name' => 'ЗП Замерщик'],
            ['fond_id' => 2, 'name' => 'ЗП Монтажник'],
            ['fond_id' => 2, 'name' => 'Налоги'],
            ['fond_id' => 2, 'name' => 'Комиссионное вознаграждение'],
            ['fond_id' => 2, 'name' => 'Доставка до участка'],
            ['fond_id' => 2, 'name' => 'Спецтехника - Кран'],
            ['fond_id' => 2, 'name' => 'Спецтехника - Грузовой авто'],
            ['fond_id' => 2, 'name' => 'Спецтехника - Водовоз'],
            ['fond_id' => 2, 'name' => 'Тест Инструмент'],
            ['fond_id' => 2, 'name' => 'Тест Комплектующие'],
            ['fond_id' => 2, 'name' => 'Тест Работы по монтажу'],
            ['fond_id' => 2, 'name' => 'Тест Работа монтажной группы'],
        ];

        $payload = array_map(fn ($item) => [
            'fond_id' => $item['fond_id'],
            'name' => $item['name'],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $items);

        DB::connection('legacy_new')->table('spending_items')->insertOrIgnore($payload);
    }

    public function down(): void
    {
        DB::connection('legacy_new')
            ->table('spending_items')
            ->where('fond_id', 2)
            ->whereIn('name', [
                'Септик',
                'Колодец',
                'Дренажный тоннель',
                'Комплектующие',
                'Доставка до города',
                'Материалы',
                'Спецтехника - Экскаватор',
                'ЗП Менеджер',
                'ЗП Замерщик',
                'ЗП Монтажник',
                'Налоги',
                'Комиссионное вознаграждение',
                'Доставка до участка',
                'Спецтехника - Кран',
                'Спецтехника - Грузовой авто',
                'Спецтехника - Водовоз',
                'Тест Инструмент',
                'Тест Комплектующие',
                'Тест Работы по монтажу',
                'Тест Работа монтажной группы',
            ])->delete();
    }
};
