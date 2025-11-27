<?php

namespace App\Listeners;

use App\Events\PaymentAppliedToContract;
use App\Services\Finance\ContractFinanceService;

class RecalcContractBalanceListener
{
    public function __construct(private readonly ContractFinanceService $financeService)
    {
    }

    public function handle(PaymentAppliedToContract $event): void
    {
        $this->financeService->recalc($event->contractId);
    }
}
