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
        $query->select('transactions.*');
        $joinedCashBoxes = false;
        $joinedTransactionTypes = false;
        $joinedPaymentMethods = false;
        $joinedCounterparties = false;

        if ($f->id) {
            $query->where('id', $f->id);
        }

        if ($f->idLike) {
            $query->where('id', 'like', '%' . $f->idLike . '%');
        }

        if ($f->tenantId !== null) {
            $query->where('tenant_id', $f->tenantId);
        }

        if ($f->companyId) {
            $query->where('company_id', $f->companyId);
        }

        if ($f->cashBoxId) {
            $query->where('cashbox_id', $f->cashBoxId);
        }

        if ($f->cashBoxSearch) {
            if (!$joinedCashBoxes) {
                $query->leftJoin('cashboxes as cb', 'cb.id', '=', 'transactions.cashbox_id');
                $joinedCashBoxes = true;
            }
            $query->where('cb.name', 'like', '%' . $f->cashBoxSearch . '%');
        }

        if ($f->transactionTypeId) {
            $query->where('transaction_type_id', $f->transactionTypeId);
        }

        if ($f->transactionTypeSearch) {
            if (!$joinedTransactionTypes) {
                $query->leftJoin('transaction_types as tt', 'tt.id', '=', 'transactions.transaction_type_id');
                $joinedTransactionTypes = true;
            }
            $query->where(function (Builder $q) use ($f) {
                $q->where('tt.name', 'like', '%' . $f->transactionTypeSearch . '%')
                    ->orWhere('tt.code', 'like', '%' . $f->transactionTypeSearch . '%');
            });
        }

        if ($f->paymentMethodId) {
            $query->where('payment_method_id', $f->paymentMethodId);
        }

        if ($f->paymentMethodSearch) {
            if (!$joinedPaymentMethods) {
                $query->leftJoin('payment_methods as pm', 'pm.id', '=', 'transactions.payment_method_id');
                $joinedPaymentMethods = true;
            }
            $query->where(function (Builder $q) use ($f) {
                $q->where('pm.name', 'like', '%' . $f->paymentMethodSearch . '%')
                    ->orWhere('pm.code', 'like', '%' . $f->paymentMethodSearch . '%');
            });
        }

        if ($f->counterpartyId) {
            $query->where('counterparty_id', $f->counterpartyId);
        }

        if ($f->counterpartySearch) {
            if (!$joinedCounterparties) {
                $query->leftJoin('counterparties as cp', 'cp.id', '=', 'transactions.counterparty_id');
                $joinedCounterparties = true;
            }
            $query->where('cp.name', 'like', '%' . $f->counterpartySearch . '%');
        }

        if ($f->contractId) {
            $query->where('contract_id', $f->contractId);
        }

        if ($f->contractLike) {
            $query->where('contract_id', 'like', '%' . $f->contractLike . '%');
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

        if ($f->datePaidFrom) {
            $query->whereDate('date_is_paid', '>=', $f->datePaidFrom);
        }

        if ($f->datePaidTo) {
            $query->whereDate('date_is_paid', '<=', $f->datePaidTo);
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
            $query->where('cashbox_id', $f->cashBoxId);
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
            $query->where('cashbox_id', $f->cashBoxId);
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
