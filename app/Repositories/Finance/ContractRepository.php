<?php

namespace App\Repositories\Finance;

use App\Domain\CRM\Models\Contract;
use Illuminate\Support\Collection;

class ContractRepository
{
    public function findWithRelations(int $id): ?Contract
    {
        return Contract::query()
            ->with(['company', 'counterparty'])
            ->find($id);
    }

    public function updatePaidAmount(int $contractId, float $paidAmount): void
    {
        Contract::query()
            ->whereKey($contractId)
            ->update(['paid_amount' => $paidAmount]);
    }

    public function updateCompletedStatus(int $contractId, bool $isCompleted): void
    {
        Contract::query()
            ->whereKey($contractId)
            ->update(['is_completed' => $isCompleted]);
    }

    public function updateSystemStatus(int $contractId, string $statusCode): void
    {
        Contract::query()
            ->whereKey($contractId)
            ->update(['system_status_code' => $statusCode]);
    }

    public function getContractsWithDebt(): Collection
    {
        return Contract::query()
            ->where('paid_amount', '<', 'total_amount')
            ->get();
    }

    public function getActiveContracts(): Collection
    {
        return Contract::query()
            ->whereNull('deleted_at')
            ->get();
    }
}
