<?php

namespace App\Services\Finance;

use App\Domain\CRM\Models\Contract;
use App\Domain\Finance\Models\FinanceObject;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function create(array $payload): Contract
    {
        return DB::transaction(function () use ($payload) {
            $contract = Contract::create($payload);
            $this->ensureFinanceObject($contract);

            return $contract->refresh();
        });
    }

    public function update(Contract $contract, array $payload): Contract
    {
        return DB::transaction(function () use ($contract, $payload) {
            $contract->update($payload);

            return $contract->refresh();
        });
    }

    public function attachCounterparty(Contract $contract, int $counterpartyId): void
    {
        $contract->counterparty_id = $counterpartyId;
        $contract->save();
    }

    public function updateWorkDates(Contract $contract, ?string $start, ?string $end): void
    {
        $contract->work_start = $start;
        $contract->work_end = $end;
        $contract->save();
    }

    public function close(Contract $contract): void
    {
        $contract->is_completed = true;
        $contract->save();
    }

    public function reopen(Contract $contract): void
    {
        $contract->is_completed = false;
        $contract->save();
    }

    public function softDelete(Contract $contract): void
    {
        $contract->delete();
    }

    private function ensureFinanceObject(Contract $contract): void
    {
        if (!empty($contract->finance_object_id)) {
            return;
        }

        $financeObject = FinanceObject::query()->create([
            'tenant_id' => $contract->tenant_id,
            'company_id' => $contract->company_id,
            'type' => 'CONTRACT',
            'name' => $contract->title ?: ('Договор #' . $contract->id),
            'code' => 'CTR-' . $contract->id,
            'status' => 'DRAFT',
            'date_from' => $contract->contract_date?->toDateString() ?? now()->toDateString(),
            'date_to' => $contract->work_end_date?->toDateString(),
            'counterparty_id' => $contract->counterparty_id,
            'legal_contract_id' => $contract->id,
            'description' => $contract->address,
            'created_by' => $contract->created_by,
            'updated_by' => $contract->updated_by,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contract->forceFill([
            'finance_object_id' => $financeObject->id,
        ])->save();
    }
}
