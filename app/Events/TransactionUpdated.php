<?php

namespace App\Events;

use App\Domain\Finance\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Transaction $transaction)
    {
    }
}
