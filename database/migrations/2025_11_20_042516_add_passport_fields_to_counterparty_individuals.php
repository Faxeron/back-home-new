<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('counterparty_individuals', function (Blueprint $table): void {
            $table->string('passport_code', 20)->nullable()->after('passport_number');
            $table->string('passport_whom')->nullable()->after('passport_code');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('counterparty_individuals', function (Blueprint $table): void {
            $table->dropColumn(['passport_code', 'passport_whom']);
        });
    }
};
