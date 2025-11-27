<?php

namespace App\Services\Finance;

use App\Domain\CRM\Models\Contract;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function create(array $payload): Contract
    {
        return DB::transaction(fn () => Contract::create($payload));
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
}
