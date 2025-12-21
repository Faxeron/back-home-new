<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('receipts', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('receipts', 'is_director_loan')) {
                $table->dropColumn('is_director_loan');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('receipts', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('receipts', 'is_director_loan')) {
                $table->boolean('is_director_loan')->default(0)->after('contract_id');
            }
        });
    }
};
