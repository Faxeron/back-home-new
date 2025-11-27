<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('spendings', function (Blueprint $table): void {
            $table->unsignedBigInteger('old_id')->nullable()->after('id');
            $table->unique('old_id');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('spendings', function (Blueprint $table): void {
            $table->dropUnique(['old_id']);
            $table->dropColumn('old_id');
        });
    }
};
