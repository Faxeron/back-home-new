<?php

namespace App\Listeners;

use App\Events\TransactionDeleted;
use App\Events\TransactionUpdated;
use App\Services\Finance\CashboxBalanceService;

class RecalcCashboxAfterTransactionChanged
{
    public function __construct(private readonly CashboxBalanceService $balanceService)
    {
    }

    public function handle(TransactionUpdated|TransactionDeleted $event): void
    {
        // Balances/history recalculation handled exclusively in FinanceService; legacy recalcs are disabled.
        return;
    }
}
