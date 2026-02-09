<?php

namespace App\Services\Finance\Filters;

class IncludeRegistry
{
    private array $map = [
        'transactions' => ['cashbox', 'company', 'counterparty', 'contract', 'transactionType', 'paymentMethod', 'cashflowItem'],
        'receipts' => ['cashbox', 'company', 'counterparty', 'contract', 'transaction', 'creator'],
        'spendings' => ['cashbox', 'company', 'counterparty', 'contract', 'item', 'fund', 'transaction', 'spentToUser', 'creator'],
    ];

    public function resolve(string $resource, ?string $includeParam): array
    {
        $allowed = $this->map[$resource] ?? [];
        if (!$includeParam) {
            return [];
        }

        $requested = array_filter(array_map('trim', explode(',', $includeParam)));

        return array_values(array_intersect($allowed, $requested));
    }
}
