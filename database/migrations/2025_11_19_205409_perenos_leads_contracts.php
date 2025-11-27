<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected array $workerMap = [
        null => 1,
        5 => 3,
    ];

    protected array $managerMap = [
        null => 1,
        4 => 2,
    ];

    protected array $measurerMap = [
        null => 1,
        5 => 3,
    ];

    protected array $statusMap = [
        5 => 1,
        null => 5,
        4 => 5,
        7 => 4,
        6 => 2,
    ];

    protected array $saleTypeMap = [
        7 => 9,
        6 => 7,
        4 => 6,
        5 => 4,
        null => 4,
        3 => 1,
    ];

    public function up(): void
    {
        DB::connection('legacy')
            ->table('leads')
            ->orderBy('id')
            ->chunkById(500, function ($leads): void {
                $payload = [];

                foreach ($leads as $lead) {
                    $payload[] = [
                        'id' => $lead->id,
                        'company_id' => 1,
                        'old_nomer_dogovora' => $lead->nomer_dogovora,
                        'contract_date' => $lead->data_dogovora,
                        'work_end_date' => $lead->data_konca_montaja,
                        'work_start_date' => $lead->data_nachala_montaja,
                        'work_done_date' => $lead->real_montaj_date,
                        'old_pole_filtracii' => $lead->pole_filtracii,
                        'worker_id' => $this->workerMap[$lead->fitter_id] ?? $lead->fitter_id,
                        'contract_status_id' => $this->statusMap[$lead->lead_status_id] ?? $lead->lead_status_id ?? 1,
                        'total_amount' => $lead->price,
                        'manager_id' => $this->managerMap[$lead->manager_id] ?? $lead->manager_id,
                        'sale_type_id' => $this->saleTypeMap[$lead->sale_type_id] ?? $lead->sale_type_id,
                        'measurer_id' => $this->measurerMap[$lead->measurer_id] ?? $lead->measurer_id,
                        'counterparty_id' => $lead->client_id,
                        'created_at' => $lead->created_at,
                        'updated_at' => $lead->updated_at,
                        'city_id' => $lead->city_id,
                        'address' => $lead->address,
                        'old_septic_old_scu' => $lead->septik_old_scu,
                        'old_septik_old_water' => $lead->septik_old_water,
                        'comment' => property_exists($lead, 'comment') ? $lead->comment : null,
                        'updated_by' => property_exists($lead, 'updated_by') ? $lead->updated_by : null,
                        'created_by' => property_exists($lead, 'created_by') ? $lead->created_by : null,
                        'is_completed' => (bool) (property_exists($lead, 'is_completed') ? $lead->is_completed : false),
                        'paid_amount' => property_exists($lead, 'paid_amount') ? $lead->paid_amount : null,
                        'title' => $lead->title ?? $lead->nomer_dogovora,
                    ];
                }

                if (!empty($payload)) {
                    DB::connection('legacy_new')
                        ->table('contracts')
                        ->insertOrIgnore($payload);
                }
            });
    }

    public function down(): void
    {
        DB::connection('legacy')
            ->table('leads')
            ->orderBy('id')
            ->chunkById(500, function ($leads): void {
                $ids = $leads->pluck('id')->all();

                DB::connection('legacy_new')
                    ->table('contracts')
                    ->whereIn('id', $ids)
                    ->delete();
            });
    }
};
