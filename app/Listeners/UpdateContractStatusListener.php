<?php

namespace App\Listeners;

use App\Events\ContractRecalculated;
use App\Services\Finance\ContractFinanceService;

class UpdateContractStatusListener
{
    public function __construct(private readonly ContractFinanceService $financeService)
    {
    }

    public function handle(ContractRecalculated $event): void
    {
        $this->financeService->updateContractStatus($event->contractId);
    }
}
