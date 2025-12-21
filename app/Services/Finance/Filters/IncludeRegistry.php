<?php

namespace App\Services\Finance\Filters;

class IncludeRegistry
{
    private array $map = [
        'transactions' => ['cashbox', 'cashBox', 'company', 'counterparty', 'contract', 'transactionType', 'paymentMethod'],
        'receipts' => ['cashbox', 'cashBox', 'company', 'counterparty', 'contract', 'transaction'],
        'spendings' => ['cashbox', 'cashBox', 'company', 'counterparty', 'contract', 'item', 'fund', 'transaction', 'spentToUser'],
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
