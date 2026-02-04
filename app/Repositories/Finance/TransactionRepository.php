<?php

namespace App\Repositories\Finance;

use App\Domain\Finance\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionRepository
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $perPage = min(max((int)($filters['per_page'] ?? 25), 1), 100);
        $page = max((int)($filters['page'] ?? 1), 1);

        $query = Transaction::query()
            ->with(['company', 'cashBox.logoPreset', 'counterparty', 'transactionType', 'paymentMethod'])
            ->orderByDesc('created_at');

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        foreach (['transaction_type_id', 'company_id', 'cashbox_id', 'contract_id', 'counterparty_id', 'payment_method_id'] as $field) {
            if (!empty($filters[$field])) {
                $query->where($field, (int) $filters[$field]);
            }
        }

        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getByContract(int $contractId)
    {
        return Transaction::query()
            ->ofContract($contractId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function sumIncomeByContract(int $contractId): float
    {
        return (float) Transaction::query()
            ->ofContract($contractId)
            ->where('sum', '>', 0)
            ->sum('sum');
    }

    public function sumExpenseByContract(int $contractId): float
    {
        return (float) Transaction::query()
            ->ofContract($contractId)
            ->where('sum', '<', 0)
            ->sum('sum');
    }

    public function sumByCashbox(int $cashboxId): float
    {
        return (float) Transaction::query()
            ->ofCashbox($cashboxId)
            ->sum('sum');
    }

    public function getTransfers()
    {
        return Transaction::query()
            ->where('transaction_type_id', 3)
            ->get();
    }

    public function getDailyTotals(?string $dateFrom = null, ?string $dateTo = null)
    {
        $query = Transaction::query()
            ->selectRaw('DATE(created_at) as date, SUM(sum) as total')
            ->groupBy('date')
            ->orderBy('date');

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query->get();
    }
}
