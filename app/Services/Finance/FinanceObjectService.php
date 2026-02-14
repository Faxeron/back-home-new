<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Domain\Finance\Models\FinanceObject;
use App\Domain\Finance\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FinanceObjectService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(int $tenantId, int $companyId, array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), 200);
        $page = max((int) ($filters['page'] ?? 1), 1);

        $query = FinanceObject::query()
            ->with(['counterparty', 'legalContract'])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId);

        if (!empty($filters['type'])) {
            $query->where('type', (string) $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (!empty($filters['counterparty_id'])) {
            $query->where('counterparty_id', (int) $filters['counterparty_id']);
        }

        if (!empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            if ($term !== '') {
                $query->where(function ($builder) use ($term): void {
                    $builder->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhereHas('counterparty', function ($q) use ($term): void {
                            $q->where('name', 'like', "%{$term}%");
                        });
                });
            }
        }

        if (array_key_exists('archived', $filters)) {
            $archived = filter_var($filters['archived'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($archived === true) {
                $query->where('status', 'ARCHIVED');
            } elseif ($archived === false) {
                $query->where('status', '!=', 'ARCHIVED');
            }
        }

        $sort = (string) ($filters['sort'] ?? 'created_at');
        $direction = strtolower((string) ($filters['direction'] ?? 'desc'));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
        $allowedSort = ['id', 'name', 'code', 'type', 'status', 'date_from', 'date_to', 'created_at', 'updated_at'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }

        $query->orderBy($sort, $direction);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(int $tenantId, int $companyId, array $payload, ?int $userId = null): FinanceObject
    {
        $payload['tenant_id'] = $tenantId;
        $payload['company_id'] = $companyId;
        $payload['created_by'] = $userId;
        $payload['updated_by'] = $userId;

        return FinanceObject::query()->create($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(FinanceObject $object, array $payload, ?int $userId = null): FinanceObject
    {
        $payload['updated_by'] = $userId;
        $object->update($payload);

        return $object->refresh();
    }

    /**
     * @return array<string, float>
     */
    public function kpi(FinanceObject $object): array
    {
        $all = $this->aggregate((int) $object->id, null);
        $paid = $this->aggregate((int) $object->id, true);

        $incomePlan = $all['income'];
        $expensePlan = $all['expense'];
        $incomeFact = $paid['income'];
        $expenseFact = $paid['expense'];

        return [
            'income_fact' => round($incomeFact, 2),
            'expense_fact' => round($expenseFact, 2),
            'net_fact' => round($incomeFact - $expenseFact, 2),
            'income_plan' => round($incomePlan, 2),
            'expense_plan' => round($expensePlan, 2),
            'net_plan' => round($incomePlan - $expensePlan, 2),
            'debitor' => round(max($incomePlan - $incomeFact, 0), 2),
            'creditor' => round(max($expensePlan - $expenseFact, 0), 2),
        ];
    }

    public function transactions(int $objectId, int $tenantId, int $companyId, int $perPage = 50): LengthAwarePaginator
    {
        return Transaction::query()
            ->with([
                'cashbox',
                'counterparty',
                'transactionType',
                'paymentMethod',
                'financeObject',
                'financeObjectAllocations' => fn ($q) => $q->where('finance_object_id', $objectId)->with('financeObject'),
            ])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where(function ($query) use ($objectId): void {
                $query->where('finance_object_id', $objectId)
                    ->orWhereExists(function ($sub) use ($objectId): void {
                        $sub->selectRaw('1')
                            ->from('finance_object_allocations as foa')
                            ->whereColumn('foa.transaction_id', 'transactions.id')
                            ->where('foa.finance_object_id', $objectId);
                    });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @return array{income: float, expense: float}
     */
    private function aggregate(int $financeObjectId, ?bool $isPaid): array
    {
        $direct = DB::connection('legacy_new')
            ->table('transactions as t')
            ->join('transaction_types as tt', 'tt.id', '=', 't.transaction_type_id')
            ->leftJoin('finance_object_allocations as foa', 'foa.transaction_id', '=', 't.id')
            ->where('t.finance_object_id', $financeObjectId)
            ->whereNull('foa.id');

        if ($isPaid !== null) {
            $direct->where('t.is_paid', $isPaid ? 1 : 0);
        }

        $directTotals = $direct
            ->selectRaw('COALESCE(SUM(CASE WHEN tt.sign > 0 THEN t.sum ELSE 0 END), 0) as income')
            ->selectRaw('COALESCE(SUM(CASE WHEN tt.sign < 0 THEN t.sum ELSE 0 END), 0) as expense')
            ->first();

        $alloc = DB::connection('legacy_new')
            ->table('finance_object_allocations as foa')
            ->join('transactions as t', 't.id', '=', 'foa.transaction_id')
            ->join('transaction_types as tt', 'tt.id', '=', 't.transaction_type_id')
            ->where('foa.finance_object_id', $financeObjectId);

        if ($isPaid !== null) {
            $alloc->where('t.is_paid', $isPaid ? 1 : 0);
        }

        $allocTotals = $alloc
            ->selectRaw('COALESCE(SUM(CASE WHEN tt.sign > 0 THEN foa.amount ELSE 0 END), 0) as income')
            ->selectRaw('COALESCE(SUM(CASE WHEN tt.sign < 0 THEN foa.amount ELSE 0 END), 0) as expense')
            ->first();

        return [
            'income' => (float) ($directTotals->income ?? 0) + (float) ($allocTotals->income ?? 0),
            'expense' => (float) ($directTotals->expense ?? 0) + (float) ($allocTotals->expense ?? 0),
        ];
    }
}

