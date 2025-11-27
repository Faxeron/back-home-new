<?php

namespace App\Services\Finance;

use App\Repositories\Finance\CashboxRepository;
use App\Repositories\Finance\CashboxHistoryRepository;
use Illuminate\Support\Facades\DB;

class CashboxBalanceService
{
    public function __construct(
        private readonly CashboxRepository $cashboxes,
        private readonly CashboxHistoryRepository $history
    )
    {
    }

    public function recalc(int $cashboxId): void
    {
        $balance = $this->cashboxes->getBalance($cashboxId);
        $this->cashboxes->updateBalance($cashboxId, $balance);
        $this->history->add($cashboxId, null, $balance);
    }

    public function applyIncome(int $cashboxId, float $amount, ?int $transactionId = null): void
    {
        DB::transaction(function () use ($cashboxId, $amount, $transactionId) {
            $balance = $this->cashboxes->getBalance($cashboxId) + $amount;
            $this->cashboxes->updateBalance($cashboxId, $balance);
            $this->history->add($cashboxId, $transactionId, $balance);
        });
    }

    public function applyExpense(int $cashboxId, float $amount, ?int $transactionId = null): void
    {
        DB::transaction(function () use ($cashboxId, $amount, $transactionId) {
            $balance = $this->cashboxes->getBalance($cashboxId) - $amount;
            $this->cashboxes->updateBalance($cashboxId, $balance);
            $this->history->add($cashboxId, $transactionId, $balance);
        });
    }

    public function applyTransfer(int $fromCashboxId, int $toCashboxId, float $amount, ?int $transactionId = null): void
    {
        DB::transaction(function () use ($fromCashboxId, $toCashboxId, $amount, $transactionId) {
            $this->applyExpense($fromCashboxId, $amount, $transactionId);
            $this->applyIncome($toCashboxId, $amount, $transactionId);
        });
    }
}
