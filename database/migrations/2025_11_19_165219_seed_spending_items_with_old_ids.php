<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection($this->connection)->getDriverName() === 'pgsql') {
            return;
        }

        $rows = [
            ['old_id' => 1, 'fond_id' => 1, 'name' => 'Андрей'],
            ['old_id' => 3, 'fond_id' => 2, 'name' => 'Септик'],
            ['old_id' => 4, 'fond_id' => 2, 'name' => 'Колодец'],
            ['old_id' => 5, 'fond_id' => 2, 'name' => 'Дренажный тоннель'],
            ['old_id' => 6, 'fond_id' => 2, 'name' => 'Комплектующие'],
            ['old_id' => 7, 'fond_id' => 2, 'name' => 'Доставка до города'],
            ['old_id' => 8, 'fond_id' => 2, 'name' => 'Материалы'],
            ['old_id' => 9, 'fond_id' => 2, 'name' => 'Спецтехника - Экскаватор'],
            ['old_id' => 10, 'fond_id' => 2, 'name' => 'ЗП Менеджер'],
            ['old_id' => 11, 'fond_id' => 2, 'name' => 'ЗП Замерщик'],
            ['old_id' => 12, 'fond_id' => 2, 'name' => 'ЗП Монтажник'],
            ['old_id' => 13, 'fond_id' => 2, 'name' => 'Налоги'],
            ['old_id' => 14, 'fond_id' => 2, 'name' => 'Комиссионное вознаграждение'],
            ['old_id' => 15, 'fond_id' => 2, 'name' => 'Доставка до участка'],
            ['old_id' => 16, 'fond_id' => 2, 'name' => 'Спецтехника - Кран'],
            ['old_id' => 17, 'fond_id' => 2, 'name' => 'Спецтехника - Грузовой авто'],
            ['old_id' => 18, 'fond_id' => 2, 'name' => 'Спецтехника - Водовоз'],
            ['old_id' => 23, 'fond_id' => 2, 'name' => 'АЗС'],
            ['old_id' => 24, 'fond_id' => 3, 'name' => 'Офисные расходы'],
            ['old_id' => 28, 'fond_id' => 5, 'name' => 'Спецодежда'],
            ['old_id' => 29, 'fond_id' => 5, 'name' => 'Воркзилла'],
            ['old_id' => 30, 'fond_id' => 5, 'name' => 'Оргтехника'],
            ['old_id' => 31, 'fond_id' => 5, 'name' => 'Найм сотрудников'],
            ['old_id' => 32, 'fond_id' => 5, 'name' => 'Программист'],
            ['old_id' => 33, 'fond_id' => 5, 'name' => 'Обучение'],
            ['old_id' => 42, 'fond_id' => 5, 'name' => 'Инструменты'],
            ['old_id' => 2, 'fond_id' => 6, 'name' => 'Авито'],
            ['old_id' => 3, 'fond_id' => 6, 'name' => 'Яндекс'],
            ['old_id' => 4, 'fond_id' => 6, 'name' => 'Юла'],
            ['old_id' => 5, 'fond_id' => 6, 'name' => 'Гугл'],
            ['old_id' => 6, 'fond_id' => 6, 'name' => 'Буклеты'],
            ['old_id' => 7, 'fond_id' => 6, 'name' => 'Клик.ру'],
            ['old_id' => 8, 'fond_id' => 6, 'name' => 'Инстаграм таргет'],
            ['old_id' => 9, 'fond_id' => 6, 'name' => 'Видео продакшн'],
            ['old_id' => 10, 'fond_id' => 6, 'name' => 'Баннеры'],
            ['old_id' => 38, 'fond_id' => 6, 'name' => 'Директолог'],
            ['old_id' => 39, 'fond_id' => 6, 'name' => 'ВК Таргет'],
            ['old_id' => 40, 'fond_id' => 6, 'name' => 'Маркетолог'],
            ['old_id' => 41, 'fond_id' => 6, 'name' => 'Елама'],
            ['old_id' => 45, 'fond_id' => 6, 'name' => 'Платформа ЛП'],
            ['old_id' => 46, 'fond_id' => 6, 'name' => '2ГИС'],
            ['old_id' => 49, 'fond_id' => 4, 'name' => 'НекстБот'],
            ['old_id' => 50, 'fond_id' => 4, 'name' => 'Ваззап'],
            ['old_id' => 35, 'fond_id' => 7, 'name' => 'Пополнение складских запасов'],
            ['old_id' => 11, 'fond_id' => 4, 'name' => 'Амоцрм'],
            ['old_id' => 12, 'fond_id' => 3, 'name' => 'Аренда'],
            ['old_id' => 13, 'fond_id' => 4, 'name' => 'АТИ'],
            ['old_id' => 14, 'fond_id' => 4, 'name' => 'Роистат'],
            ['old_id' => 15, 'fond_id' => 4, 'name' => 'Связь'],
            ['old_id' => 16, 'fond_id' => 4, 'name' => 'СМС.ру'],
            ['old_id' => 44, 'fond_id' => 4, 'name' => 'Домент / хостинг'],
            ['old_id' => 47, 'fond_id' => 4, 'name' => 'Контур / Сбис'],
            ['old_id' => 18, 'fond_id' => 8, 'name' => 'ЗП Менеджер - оклад'],
            ['old_id' => 19, 'fond_id' => 8, 'name' => 'ЗП Менеджер - %'],
            ['old_id' => 20, 'fond_id' => 8, 'name' => 'ЗП Замерщик - fix'],
            ['old_id' => 21, 'fond_id' => 8, 'name' => 'ЗП Замерщик - %'],
            ['old_id' => 22, 'fond_id' => 8, 'name' => 'ЗП Монтажник'],
            ['old_id' => 48, 'fond_id' => 8, 'name' => 'Бухгалтерия'],
            ['old_id' => 25, 'fond_id' => 9, 'name' => 'Гарантия'],
            ['old_id' => 26, 'fond_id' => 9, 'name' => 'Ремонт оборудования'],
            ['old_id' => 27, 'fond_id' => 9, 'name' => 'Ремонт авто'],
            ['old_id' => 36, 'fond_id' => 10, 'name' => 'Тюмень'],
            ['old_id' => 37, 'fond_id' => 10, 'name' => 'Домостроение'],
            ['old_id' => 17, 'fond_id' => 11, 'name' => 'Банковское обслуживание'],
            ['old_id' => 51, 'fond_id' => 11, 'name' => 'Комиссия за выдачу наличных'],
            ['old_id' => 52, 'fond_id' => 11, 'name' => 'Комиссия за внесение наличных'],
            ['old_id' => 53, 'fond_id' => 11, 'name' => 'Оплата тарифа'],
            ['old_id' => 54, 'fond_id' => 11, 'name' => 'Оплата обслуживания карты'],
            ['old_id' => 55, 'fond_id' => 11, 'name' => 'Комиссия за перевод средств со счета ЮЛ на счета ФЛ, открытые в других банках'],
            ['old_id' => 56, 'fond_id' => 11, 'name' => 'Комиссия за перевод средств со счета ЮЛ на счета ФЛ, открытые в ПАО Сбербанк'],
            ['old_id' => 57, 'fond_id' => 11, 'name' => 'Комиссия в другие банки (кредитные организации, Банк России) за ПП/ПТ через ДБО'],
            ['old_id' => 64, 'fond_id' => 11, 'name' => 'Эквайринг'],
            ['old_id' => 65, 'fond_id' => 11, 'name' => 'Фискальный накопитель'],
            ['old_id' => 43, 'fond_id' => 12, 'name' => 'Налоги'],
            ['old_id' => 58, 'fond_id' => 12, 'name' => 'Взыскание недоимки'],
            ['old_id' => 59, 'fond_id' => 12, 'name' => 'Взыскание пени'],
            ['old_id' => 60, 'fond_id' => 12, 'name' => 'Взыскание штрафы'],
            ['old_id' => 61, 'fond_id' => 12, 'name' => 'Оплата страховых взносов'],
            ['old_id' => 62, 'fond_id' => 12, 'name' => 'Единый налоговый платеж. Страховые взносы и НДФЛ'],
            ['old_id' => 63, 'fond_id' => 12, 'name' => 'Авансовый платеж'],
        ];

        foreach ($rows as $row) {
            DB::connection('legacy_new')
                ->table('spending_items')
                ->updateOrInsert(
                    ['old_id' => $row['old_id']],
                    [
                        'fond_id' => $row['fond_id'],
                        'name' => $row['name'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
        }
    }

    public function down(): void
    {
        $oldIds = [
            1,2,3,4,5,6,7,8,9,10,
            11,12,13,14,15,16,17,18,
            23,24,25,26,27,28,29,30,31,32,33,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,
            51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,
        ];

        DB::connection('legacy_new')->table('spending_items')->whereIn('old_id', $oldIds)->delete();
    }
};

