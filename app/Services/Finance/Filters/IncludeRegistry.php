<?php

namespace App\Services\Finance\Filters;

class IncludeRegistry
{
    private array $map = [
        'transactions' => ['cashbox', 'company', 'counterparty', 'contract', 'financeObject', 'transactionType', 'paymentMethod', 'cashflowItem', 'financeObjectAllocations'],
        'receipts' => ['cashbox', 'company', 'counterparty', 'contract', 'financeObject', 'transaction', 'creator'],
        'spendings' => ['cashbox', 'company', 'counterparty', 'contract', 'financeObject', 'item', 'fund', 'transaction', 'spentToUser', 'creator'],
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
