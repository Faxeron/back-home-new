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
            if (!Schema::connection('legacy_new')->hasColumn('spendings', 'spending_type_id')) {
                $table->unsignedBigInteger('spending_type_id')->nullable()->after('spending_item_id');
            }
            if (!Schema::connection('legacy_new')->hasColumn('spendings', 'spending_type_name')) {
                $table->string('spending_type_name')->nullable()->after('spending_type_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('spendings', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('spendings', 'spending_type_name')) {
                $table->dropColumn('spending_type_name');
            }
            if (Schema::connection('legacy_new')->hasColumn('spendings', 'spending_type_id')) {
                $table->dropColumn('spending_type_id');
            }
        });
    }
};
