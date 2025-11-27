<?php

namespace App\Events;

use App\Domain\Finance\Models\Receipt;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReceiptCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Receipt $receipt)
    {
    }
}
