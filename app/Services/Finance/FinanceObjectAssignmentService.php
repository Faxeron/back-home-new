<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Domain\Finance\Models\FinanceObject;
use App\Domain\Finance\Models\FinanceObjectAllocation;
use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\Models\Spending;
use App\Domain\Finance\Models\Transaction;
use App\Domain\Finance\ValueObjects\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FinanceObjectAssignmentService
{
    /**
     * @param array<int, array{finance_object_id?: int|string|null, amount?: float|int|string|null, comment?: string|null}> $allocations
     */
    public function assignTransaction(
        Transaction $transaction,
        ?int $financeObjectId,
        array $allocations,
        int $tenantId,
        int $companyId,
    ): void {
        $normalized = $this->normalizeAllocations($allocations);
        $hasDirect = $financeObjectId !== null;
        $hasAllocations = $normalized !== [];

        if ($hasDirect === $hasAllocations) {
            throw new RuntimeException('Provide either finance_object_id or allocations.');
        }

        DB::connection('legacy_new')->transaction(function () use (
            $transaction,
            $financeObjectId,
            $normalized,
            $tenantId,
            $companyId,
            $hasDirect,
        ): void {
            if ($hasDirect) {
                $object = $this->loadFinanceObject((int) $financeObjectId, $tenantId, $companyId);
                $this->assertAssignable($object, (int) $transaction->finance_object_id);

                FinanceObjectAllocation::query()
                    ->where('transaction_id', $transaction->id)
                    ->delete();

                $compatContractId = $this->resolveCompatContractId($object, []);

                $transaction->forceFill([
                    'finance_object_id' => $object->id,
                    'contract_id' => $compatContractId,
                ]);
                $transaction->save();

                $this->syncRelatedRows(
                    (int) $transaction->id,
                    $tenantId,
                    $companyId,
                    $object->id,
                    $compatContractId,
                );

                return;
            }

            $objectIds = array_values(array_unique(array_map(
                static fn (array $item) => (int) $item['finance_object_id'],
                $normalized
            )));
            $objects = $this->loadFinanceObjects($objectIds, $tenantId, $companyId);

            foreach ($objects as $object) {
                $this->assertAssignable($object, (int) $transaction->finance_object_id);
            }

            if ($objects->count() !== count($objectIds)) {
                throw new RuntimeException('One or more finance objects were not found in current tenant/company.');
            }

            $sum = array_reduce($normalized, static fn (float $carry, array $item) => $carry + (float) $item['amount'], 0.0);
            $expected = $this->moneyToFloat($transaction->sum);
            if (abs($sum - $expected) > 0.009) {
                throw new RuntimeException('Allocations total must match transaction amount.');
            }

            FinanceObjectAllocation::query()
                ->where('transaction_id', $transaction->id)
                ->delete();

            $rows = [];
            $now = now();
            foreach ($normalized as $item) {
                $rows[] = [
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'transaction_id' => $transaction->id,
                    'finance_object_id' => (int) $item['finance_object_id'],
                    'amount' => (float) $item['amount'],
                    'comment' => $item['comment'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::connection('legacy_new')->table('finance_object_allocations')->insert($rows);

            $compatContractId = $this->resolveCompatContractId(null, $objects);
            $transaction->forceFill([
                'finance_object_id' => null,
                'contract_id' => $compatContractId,
            ]);
            $transaction->save();

            $this->syncRelatedRows(
                (int) $transaction->id,
                $tenantId,
                $companyId,
                null,
                $compatContractId,
            );
        });
    }

    /**
     * @param array<int, int> $transactionIds
     */
    public function bulkAssignTransactions(
        array $transactionIds,
        int $financeObjectId,
        int $tenantId,
        int $companyId
    ): int {
        $ids = array_values(array_unique(array_filter(array_map('intval', $transactionIds))));
        if ($ids === []) {
            return 0;
        }

        $object = $this->loadFinanceObject($financeObjectId, $tenantId, $companyId);
        $this->assertAssignable($object, null);

        return DB::connection('legacy_new')->transaction(function () use ($ids, $object, $tenantId, $companyId): int {
            $transactions = Transaction::query()
                ->whereIn('id', $ids)
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->get(['id', 'contract_id', 'finance_object_id']);

            if ($transactions->isEmpty()) {
                return 0;
            }

            $txIds = $transactions->pluck('id')->map(static fn ($id) => (int) $id)->all();

            FinanceObjectAllocation::query()
                ->whereIn('transaction_id', $txIds)
                ->delete();

            $compatContractId = $this->resolveCompatContractId($object, collect());

            Transaction::query()
                ->whereIn('id', $txIds)
                ->update([
                    'finance_object_id' => $object->id,
                    'contract_id' => $compatContractId,
                    'updated_at' => now(),
                ]);

            Receipt::query()
                ->whereIn('transaction_id', $txIds)
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->update([
                    'finance_object_id' => $object->id,
                    'contract_id' => $compatContractId,
                    'updated_at' => now(),
                ]);

            Spending::query()
                ->whereIn('transaction_id', $txIds)
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->update([
                    'finance_object_id' => $object->id,
                    'contract_id' => $compatContractId,
                    'updated_at' => now(),
                ]);

            return count($txIds);
        });
    }

    private function loadFinanceObject(int $id, int $tenantId, int $companyId): FinanceObject
    {
        $object = FinanceObject::query()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->first();

        if (!$object) {
            throw new RuntimeException('Finance object not found.');
        }

        return $object;
    }

    /**
     * @param array<int, int> $ids
     * @return Collection<int, FinanceObject>
     */
    private function loadFinanceObjects(array $ids, int $tenantId, int $companyId): Collection
    {
        return FinanceObject::query()
            ->whereIn('id', $ids)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->get();
    }

    private function assertAssignable(FinanceObject $object, ?int $currentObjectId): void
    {
        if ($currentObjectId !== null && (int) $object->id === (int) $currentObjectId) {
            return;
        }

        if (!$object->canAcceptNewMoney()) {
            throw new RuntimeException('Selected finance object cannot accept new operations.');
        }
    }

    /**
     * @param array<int, array{finance_object_id?: int|string|null, amount?: float|int|string|null, comment?: string|null}> $allocations
     * @return array<int, array{finance_object_id: int, amount: float, comment: string|null}>
     */
    private function normalizeAllocations(array $allocations): array
    {
        $normalized = [];
        $seen = [];
        foreach ($allocations as $allocation) {
            $objectId = isset($allocation['finance_object_id']) ? (int) $allocation['finance_object_id'] : 0;
            $amount = isset($allocation['amount']) ? (float) $allocation['amount'] : 0.0;
            $comment = isset($allocation['comment']) ? trim((string) $allocation['comment']) : null;

            if ($objectId <= 0) {
                throw new RuntimeException('Allocation finance_object_id is required.');
            }
            if ($amount <= 0) {
                throw new RuntimeException('Allocation amount must be greater than zero.');
            }
            if (isset($seen[$objectId])) {
                throw new RuntimeException('Duplicate finance object in allocations.');
            }

            $seen[$objectId] = true;
            $normalized[] = [
                'finance_object_id' => $objectId,
                'amount' => $amount,
                'comment' => $comment ?: null,
            ];
        }

        return $normalized;
    }

    /**
     * @param Collection<int, FinanceObject> $allocationObjects
     */
    private function resolveCompatContractId(?FinanceObject $directObject, Collection $allocationObjects): ?int
    {
        if ($directObject) {
            $type = $directObject->type?->value ?? (string) $directObject->type;
            if ($type === 'CONTRACT' && $directObject->legal_contract_id) {
                return (int) $directObject->legal_contract_id;
            }

            return null;
        }

        if ($allocationObjects->count() === 1) {
            $object = $allocationObjects->first();
            $type = $object->type?->value ?? (string) $object->type;
            if ($type === 'CONTRACT' && $object->legal_contract_id) {
                return (int) $object->legal_contract_id;
            }
        }

        return null;
    }

    private function syncRelatedRows(
        int $transactionId,
        int $tenantId,
        int $companyId,
        ?int $financeObjectId,
        ?int $compatContractId
    ): void {
        Receipt::query()
            ->where('transaction_id', $transactionId)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->update([
                'finance_object_id' => $financeObjectId,
                'contract_id' => $compatContractId,
                'updated_at' => now(),
            ]);

        Spending::query()
            ->where('transaction_id', $transactionId)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->update([
                'finance_object_id' => $financeObjectId,
                'contract_id' => $compatContractId,
                'updated_at' => now(),
            ]);
    }

    private function moneyToFloat($value): float
    {
        if ($value instanceof Money) {
            return abs($value->toFloat());
        }

        return abs((float) $value);
    }
}

