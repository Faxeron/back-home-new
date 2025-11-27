<?php

namespace App\Events;

use App\Domain\Finance\Models\Spending;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SpendingCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Spending $spending)
    {
    }
}
