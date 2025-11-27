<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('legacy_new')->table('spending_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('old_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('legacy_new')->table('spending_items', function (Blueprint $table): void {
            $table->dropColumn('old_id');
        });
    }
};
