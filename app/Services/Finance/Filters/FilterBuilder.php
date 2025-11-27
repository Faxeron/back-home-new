<?php

namespace App\Services\Finance\Filters;

use App\Domain\Finance\DTO\ReceiptFilterDTO;
use App\Domain\Finance\DTO\SpendingFilterDTO;
use App\Domain\Finance\DTO\TransactionFilterDTO;
use Illuminate\Database\Eloquent\Builder;

class FilterBuilder
{
    public function applyToTransactions(Builder $query, TransactionFilterDTO $f): Builder
    {
        if ($f->tenantId !== null) {
            $query->where('tenant_id', $f->tenantId);
        }

        if ($f->companyId) {
            $query->where('company_id', $f->companyId);
        }

        if ($f->cashBoxId) {
            $query->where('cash_box_id', $f->cashBoxId);
        }

        if ($f->transactionTypeId) {
            $query->where('transaction_type_id', $f->transactionTypeId);
        }

        if ($f->paymentMethodId) {
            $query->where('payment_method_id', $f->paymentMethodId);
        }

        if ($f->counterpartyId) {
            $query->where('counterparty_id', $f->counterpartyId);
        }

        if ($f->contractId) {
            $query->where('contract_id', $f->contractId);
        }

        if ($f->relatedId) {
            $query->where('related_id', $f->relatedId);
        }

        if ($f->sumMin !== null) {
            $query->where('sum', '>=', $f->sumMin);
        }

        if ($f->sumMax !== null) {
            $query->where('sum', '<=', $f->sumMax);
        }

        if ($f->isPaid !== null) {
            $query->where('is_paid', $f->isPaid);
        }

        if ($f->isCompleted !== null) {
            $query->where('is_completed', $f->isCompleted);
        }

        if ($f->dateFrom) {
            $query->whereDate('created_at', '>=', $f->dateFrom);
        }

        if ($f->dateTo) {
            $query->whereDate('created_at', '<=', $f->dateTo);
        }

        if ($f->search) {
            $search = $f->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('notes', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function applyToReceipts(Builder $query, ReceiptFilterDTO $f): Builder
    {
        if ($f->tenantId !== null) {
            $query->where('tenant_id', $f->tenantId);
        }

        if ($f->companyId) {
            $query->where('company_id', $f->companyId);
        }

        if ($f->cashBoxId) {
            $query->where('cash_box_id', $f->cashBoxId);
        }

        if ($f->contractId) {
            $query->where('contract_id', $f->contractId);
        }

        if ($f->counterpartyId) {
            $query->where('counterparty_id', $f->counterpartyId);
        }

        if ($f->sumMin !== null) {
            $query->where('sum', '>=', $f->sumMin);
        }

        if ($f->sumMax !== null) {
            $query->where('sum', '<=', $f->sumMax);
        }

        if ($f->paymentDateFrom) {
            $query->whereDate('payment_date', '>=', $f->paymentDateFrom);
        }

        if ($f->paymentDateTo) {
            $query->whereDate('payment_date', '<=', $f->paymentDateTo);
        }

        if ($f->dateFrom) {
            $query->whereDate('created_at', '>=', $f->dateFrom);
        }

        if ($f->dateTo) {
            $query->whereDate('created_at', '<=', $f->dateTo);
        }

        if ($f->search) {
            $search = $f->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('description', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function applyToSpendings(Builder $query, SpendingFilterDTO $f): Builder
    {
        if ($f->tenantId !== null) {
            $query->where('tenant_id', $f->tenantId);
        }

        if ($f->companyId) {
            $query->where('company_id', $f->companyId);
        }

        if ($f->cashBoxId) {
            $query->where('cash_box_id', $f->cashBoxId);
        }

        if ($f->fondId) {
            $query->where('fond_id', $f->fondId);
        }

        if ($f->spendingItemId) {
            $query->where('spending_item_id', $f->spendingItemId);
        }

        if ($f->contractId) {
            $query->where('contract_id', $f->contractId);
        }

        if ($f->counterpartyId) {
            $query->where('counterparty_id', $f->counterpartyId);
        }

        if ($f->spentToUserId) {
            $query->where('spent_to_user_id', $f->spentToUserId);
        }

        if ($f->sumMin !== null) {
            $query->where('sum', '>=', $f->sumMin);
        }

        if ($f->sumMax !== null) {
            $query->where('sum', '<=', $f->sumMax);
        }

        if ($f->paymentDateFrom) {
            $query->whereDate('payment_date', '>=', $f->paymentDateFrom);
        }

        if ($f->paymentDateTo) {
            $query->whereDate('payment_date', '<=', $f->paymentDateTo);
        }

        if ($f->dateFrom) {
            $query->whereDate('created_at', '>=', $f->dateFrom);
        }

        if ($f->dateTo) {
            $query->whereDate('created_at', '<=', $f->dateTo);
        }

        if ($f->search) {
            $search = $f->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('description', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
