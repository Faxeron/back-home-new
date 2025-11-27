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
            if (!Schema::connection('legacy_new')->hasColumn('spendings', 'payment_date')) {
                $table->date('payment_date')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('spendings', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('spendings', 'payment_date')) {
                $table->dropColumn('payment_date');
            }
        });
    }
};
