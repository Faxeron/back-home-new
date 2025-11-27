<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('transactions', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'id_cash_box')) {
                $table->renameColumn('id_cash_box', 'cash_box_id');
            }

            if (Schema::connection('legacy_new')->hasColumn('transactions', 'id_project')) {
                $table->renameColumn('id_project', 'company_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('transactions', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'cash_box_id')) {
                $table->renameColumn('cash_box_id', 'id_cash_box');
            }

            if (Schema::connection('legacy_new')->hasColumn('transactions', 'company_id')) {
                $table->renameColumn('company_id', 'id_project');
            }
        });
    }
};
