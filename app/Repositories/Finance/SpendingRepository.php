<?php

namespace App\Repositories\Finance;

use App\Domain\Finance\Models\Spending;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SpendingRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int)($filters['per_page'] ?? 25), 1), 100);
        $page = max((int)($filters['page'] ?? 1), 1);

        $query = Spending::query()
            ->with(['company', 'cashBox', 'fund', 'item'])
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at');

        if (!empty($filters['date_from'])) {
            $query->whereDate('payment_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('payment_date', '<=', $filters['date_to']);
        }

        foreach (['fond_id', 'spending_item_id', 'cashbox_id', 'company_id', 'contract_id', 'counterparty_id', 'spent_to_user_id'] as $field) {
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
}
