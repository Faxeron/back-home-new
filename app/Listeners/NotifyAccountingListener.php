<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyAccountingListener implements ShouldQueue
{
    public function handle($event): void
    {
        // TODO: уведомление бухгалтерии (email/queue).
    }
}
