<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Services\Finance\CashboxBalanceService;

class RecalcCashboxHistoryListener
{
    public function __construct(private readonly CashboxBalanceService $balanceService)
    {
    }

    public function handle(TransactionCreated $event): void
    {
        // FinanceService is the single writer for balances/history. Legacy recalculation is disabled to avoid double writes.
        return;
    }
}
