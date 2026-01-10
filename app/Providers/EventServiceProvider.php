<?php

namespace App\Providers;

use App\Events\CashboxBalanceChanged;
use App\Events\ContractRecalculated;
use App\Events\FinancialActionLogged;
use App\Events\PaymentAppliedToContract;
use App\Events\ReceiptCreated;
use App\Events\SpendingCreated;
use App\Events\TransactionCreated;
use App\Events\TransactionUpdated;
use App\Events\TransactionDeleted;
use App\Listeners\LogFinancialActionListener;
use App\Listeners\NotifyAccountingListener;
use App\Listeners\RecalcCashboxHistoryListener;
use App\Listeners\RecalcCashboxAfterTransactionChanged;
use App\Listeners\RecalcContractBalanceListener;
use App\Listeners\UpdateCashboxBalanceListener;
use App\Listeners\UpdateContractStatusListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TransactionCreated::class => [
            RecalcCashboxHistoryListener::class,
            NotifyAccountingListener::class,
        ],
        TransactionUpdated::class => [
            RecalcCashboxAfterTransactionChanged::class,
        ],
        TransactionDeleted::class => [
            RecalcCashboxAfterTransactionChanged::class,
        ],
        ReceiptCreated::class => [
            NotifyAccountingListener::class,
        ],
        SpendingCreated::class => [
            NotifyAccountingListener::class,
        ],
        PaymentAppliedToContract::class => [
            RecalcContractBalanceListener::class,
        ],
        ContractRecalculated::class => [
            UpdateContractStatusListener::class,
        ],
        CashboxBalanceChanged::class => [
            UpdateCashboxBalanceListener::class,
        ],
        FinancialActionLogged::class => [
            LogFinancialActionListener::class,
        ],
    ];
}
