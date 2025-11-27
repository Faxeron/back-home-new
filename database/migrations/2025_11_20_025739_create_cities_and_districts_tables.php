<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('cities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        $cities = [
            ['id' => 1, 'name' => 'Сургут'],
            ['id' => 2, 'name' => 'Нефтеюганск'],
            ['id' => 3, 'name' => 'Нижневартовск'],
            ['id' => 4, 'name' => 'Ханты-Мансийск'],
            ['id' => 5, 'name' => 'Нягань'],
            ['id' => 6, 'name' => 'Когалым'],
            ['id' => 7, 'name' => 'Тюмень'],
            ['id' => 8, 'name' => 'Пыть-Ях'],
            ['id' => 9, 'name' => 'Сентябрьский'],
            ['id' => 10, 'name' => 'Югорск'],
            ['id' => 11, 'name' => 'Новый Уренгой'],
            ['id' => 12, 'name' => 'Тюменский район'],
            ['id' => 13, 'name' => 'Тобольск'],
            ['id' => 14, 'name' => 'ЯНАО'],
        ];

        foreach ($cities as $city) {
            DB::connection('legacy_new')->table('cities')->insert([
                'id' => $city['id'],
                'name' => $city['name'],
                'created_at' => '2025-11-20 00:00:00',
                'updated_at' => '2025-11-20 00:00:00',
            ]);
        }

        Schema::connection('legacy_new')->create('cities_districts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('city_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('city_id')->references('id')->on('cities')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('cities_districts');
        Schema::connection('legacy_new')->dropIfExists('cities');
    }
};
