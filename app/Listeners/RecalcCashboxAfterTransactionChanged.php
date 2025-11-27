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
        $cashboxId = $event->transaction->cash_box_id;
        if ($cashboxId) {
            $this->balanceService->recalc((int) $cashboxId);
        }
    }
}
