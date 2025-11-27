<?php

namespace App\Repositories\Finance;

use App\Domain\Finance\Models\CashBox;
use App\Domain\Finance\Models\Transaction;
use Illuminate\Support\Collection;

class CashboxRepository
{
    public function getBalance(int $cashboxId): float
    {
        return (float) Transaction::query()
            ->ofCashbox($cashboxId)
            ->sum('sum');
    }

    public function updateBalance(int $cashboxId, float $balance): void
    {
        CashBox::query()
            ->whereKey($cashboxId)
            ->update(['balance' => $balance]);
    }

    public function getHistory(int $cashboxId): Collection
    {
        return Transaction::query()
            ->ofCashbox($cashboxId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function sumIncome(int $cashboxId): float
    {
        return (float) Transaction::query()
            ->ofCashbox($cashboxId)
            ->where('sum', '>', 0)
            ->sum('sum');
    }

    public function sumExpense(int $cashboxId): float
    {
        return (float) Transaction::query()
            ->ofCashbox($cashboxId)
            ->where('sum', '<', 0)
            ->sum('sum');
    }
}
