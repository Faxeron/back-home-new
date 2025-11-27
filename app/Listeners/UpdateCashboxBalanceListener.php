<?php

namespace App\Listeners;

use App\Events\CashboxBalanceChanged;
use App\Services\Finance\CashboxBalanceService;

class UpdateCashboxBalanceListener
{
    public function __construct(private readonly CashboxBalanceService $balanceService)
    {
    }

    public function handle(CashboxBalanceChanged $event): void
    {
        $this->balanceService->recalc($event->cashboxId);
    }
}
