<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('counterparty_individuals', function (Blueprint $table): void {
            $table->string('passport_address')->nullable()->after('passport_number');
        });

        Schema::connection('legacy_new')->table('counterparties', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('counterparties', 'address')) {
                $table->dropColumn('address');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('counterparty_individuals', function (Blueprint $table): void {
            $table->dropColumn('passport_address');
        });

        Schema::connection('legacy_new')->table('counterparties', function (Blueprint $table): void {
            $table->string('address')->nullable();
        });
    }
};
