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
        Schema::connection('legacy_new')->table('contracts', function (Blueprint $table): void {
            $table->renameColumn('id_contract_status', 'contract_status_id');
            $table->renameColumn('completion_date', 'work_done_date');

            $table->string('old_nomer_dogovora')->nullable()->after('contract_date');
            $table->date('work_start_date')->nullable()->after('work_done_date');
            $table->date('work_end_date')->nullable()->after('work_start_date');
            $table->string('old_pole_filtracii')->nullable()->after('work_end_date');
            $table->unsignedBigInteger('worker_id')->nullable()->after('old_pole_filtracii');
            $table->unsignedBigInteger('manager_id')->nullable()->after('worker_id');
            $table->unsignedBigInteger('sale_type_id')->nullable()->after('manager_id');
            $table->unsignedBigInteger('measurer_id')->nullable()->after('sale_type_id');
            $table->unsignedBigInteger('city_id')->nullable()->after('measurer_id');
            $table->string('address')->nullable()->after('city_id');
            $table->string('old_septic_old_scu')->nullable()->after('address');
            $table->string('old_septik_old_water')->nullable()->after('old_septic_old_scu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('legacy_new')->table('contracts', function (Blueprint $table): void {
            $table->renameColumn('contract_status_id', 'id_contract_status');
            $table->renameColumn('work_done_date', 'completion_date');

            $table->dropColumn([
                'old_nomer_dogovora',
                'work_start_date',
                'work_end_date',
                'old_pole_filtracii',
                'worker_id',
                'manager_id',
                'sale_type_id',
                'measurer_id',
                'city_id',
                'address',
                'old_septic_old_scu',
                'old_septik_old_water',
            ]);
        });
    }
};
