<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContractRecalculated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public int $contractId)
    {
    }
}
