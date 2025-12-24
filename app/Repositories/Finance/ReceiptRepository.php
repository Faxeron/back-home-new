<?php

namespace App\Repositories\Finance;

use App\Domain\Finance\Models\Receipt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReceiptRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int)($filters['per_page'] ?? 25), 1), 100);
        $page = max((int)($filters['page'] ?? 1), 1);

        $query = Receipt::query()
            ->with(['company', 'cashBox', 'counterparty', 'contract'])
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at');

        if (!empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        foreach (['cashbox_id', 'company_id', 'contract_id', 'counterparty_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, (int) $filters[$field]);
            }
        }

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getByContract(int $contractId)
    {
        return Receipt::query()
            ->ofContract($contractId)
            ->orderByDesc('payment_date')
            ->get();
    }

    public function sumByContract(int $contractId): float
    {
        return (float) Receipt::query()
            ->ofContract($contractId)
            ->sum('sum');
    }

    public function getByDateRange(?string $dateFrom = null, ?string $dateTo = null)
    {
        $query = Receipt::query()
            ->orderByDesc('payment_date');

        if ($dateFrom) {
            $query->whereDate('payment_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('payment_date', '<=', $dateTo);
        }

        return $query->get();
    }
}
