<?php

namespace App\Events;

use App\Domain\Finance\Models\Receipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentAppliedToContract
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public int $contractId, public Receipt $receipt)
    {
    }
}
