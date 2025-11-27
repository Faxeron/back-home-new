<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = Schema::connection('legacy_new');

        $schema->create('sale_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        DB::connection('legacy_new')->table('sale_types')->insert(array_map(function (string $name): array {
            return [
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, [
            'Продажа септика',
            'Продажа погреба',
            'Продажа комплектующих',
            'Септик + Монтаж',
            'Погреб + Монтаж',
            'Монтажные работы',
            'Ремонт',
            'Обслуживание',
            'Прочее',
        ]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('sale_types');
    }
};
