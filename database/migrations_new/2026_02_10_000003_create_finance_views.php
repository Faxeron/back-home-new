<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        // Legacy compatibility migration:
        // report_* datasets were moved to materialized tables in 2026_02_11_*.
        // On fresh migrations we must not create views with the same names.
        DB::unprepared('DROP VIEW IF EXISTS report_cashflow_monthly');
        DB::unprepared('DROP VIEW IF EXISTS report_pnl_monthly');
        DB::unprepared('DROP VIEW IF EXISTS report_debts');
        DB::unprepared('DROP VIEW IF EXISTS report_cashbox_balance');
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS report_cashbox_balance');
        DB::unprepared('DROP VIEW IF EXISTS report_debts');
        DB::unprepared('DROP VIEW IF EXISTS report_pnl_monthly');
        DB::unprepared('DROP VIEW IF EXISTS report_cashflow_monthly');
    }
};

