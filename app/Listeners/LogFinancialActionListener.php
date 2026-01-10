<?php

namespace App\Listeners;

use App\Domain\Finance\Models\FinanceAuditLog;
use App\Events\FinancialActionLogged;

class LogFinancialActionListener
{
    public function handle(FinancialActionLogged $event): void
    {
        $payload = $event->payload;

        FinanceAuditLog::create([
            'tenant_id' => $payload['tenant_id'] ?? null,
            'company_id' => $payload['company_id'] ?? null,
            'user_id' => $payload['user_id'] ?? null,
            'action' => $event->action,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
