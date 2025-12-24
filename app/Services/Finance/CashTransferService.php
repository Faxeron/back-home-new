<?php

namespace App\Services\Finance;

use App\Domain\Finance\Models\CashTransfer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CashTransferService
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 25), 1), 100);
        $page = max((int) ($filters['page'] ?? 1), 1);

        $query = CashTransfer::query()
            ->with(['fromCashBox', 'toCashBox'])
            ->orderByDesc('created_at');

        foreach (['company_id', 'from_cashbox_id', 'to_cashbox_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, (int) $filters[$field]);
            }
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
