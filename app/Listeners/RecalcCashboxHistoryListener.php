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
        $cashboxId = $event->transaction->cash_box_id;
        if ($cashboxId) {
            $amount = abs((float) $event->transaction->sum);
            $type = $event->transaction->transaction_type_id;
            $delta = method_exists($type, 'sign') ? $type->sign() * $amount : (float) $event->transaction->sum;

            if ($delta >= 0) {
                $this->balanceService->applyIncome((int) $cashboxId, $delta, (int) $event->transaction->id);
            } else {
                $this->balanceService->applyExpense((int) $cashboxId, abs($delta), (int) $event->transaction->id);
            }
        }
    }
}
