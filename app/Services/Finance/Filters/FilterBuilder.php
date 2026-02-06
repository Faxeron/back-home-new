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
            $query->where('transactions.id', $f->id);
        }

        if ($f->idLike) {
            $query->where('transactions.id', 'like', '%' . $f->idLike . '%');
        }

        if ($f->tenantId !== null) {
            $query->where('transactions.tenant_id', $f->tenantId);
        }

        if ($f->companyId) {
            $query->where('transactions.company_id', $f->companyId);
        }

        if ($f->cashBoxId) {
            $query->where('transactions.cashbox_id', $f->cashBoxId);
        }

        if ($f->cashBoxSearch) {
            if (!$joinedCashBoxes) {
                $query->leftJoin('cashboxes as cb', 'cb.id', '=', 'transactions.cashbox_id');
                $joinedCashBoxes = true;
            }
            $query->where('cb.name', 'like', '%' . $f->cashBoxSearch . '%');
        }

        if ($f->transactionTypeId) {
            $query->where('transactions.transaction_type_id', $f->transactionTypeId);
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

        if ($f->transactionSign !== null) {
            if (!$joinedTransactionTypes) {
                $query->leftJoin('transaction_types as tt', 'tt.id', '=', 'transactions.transaction_type_id');
                $joinedTransactionTypes = true;
            }

            // `sign` is used for "incomes vs expenses" filtering.
            // positive -> tt.sign > 0, negative -> tt.sign < 0, zero -> tt.sign = 0
            if ($f->transactionSign > 0) {
                $query->where('tt.sign', '>', 0);
            } elseif ($f->transactionSign < 0) {
                $query->where('tt.sign', '<', 0);
            } else {
                $query->where('tt.sign', '=', 0);
            }
        }

        if ($f->paymentMethodId) {
            $query->where('transactions.payment_method_id', $f->paymentMethodId);
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
            $query->where('transactions.counterparty_id', $f->counterpartyId);
        }

        if ($f->counterpartySearch) {
            if (!$joinedCounterparties) {
                $query->leftJoin('counterparties as cp', 'cp.id', '=', 'transactions.counterparty_id');
                $joinedCounterparties = true;
            }
            $query->where('cp.name', 'like', '%' . $f->counterpartySearch . '%');
        }

        if ($f->contractId) {
            $query->where('transactions.contract_id', $f->contractId);
        }

        if ($f->contractLike) {
            $query->where('transactions.contract_id', 'like', '%' . $f->contractLike . '%');
        }

        if ($f->contractOrCounterparty) {
            if (!$joinedCounterparties) {
                $query->leftJoin('counterparties as cp', 'cp.id', '=', 'transactions.counterparty_id');
                $joinedCounterparties = true;
            }
            $term = $f->contractOrCounterparty;
            $query->where(function (Builder $q) use ($term) {
                $q->where('transactions.contract_id', 'like', '%' . $term . '%')
                    ->orWhere('cp.name', 'like', '%' . $term . '%');
            });
        }

        if ($f->relatedId) {
            $query->where('transactions.related_id', $f->relatedId);
        }

        if ($f->relatedLike) {
            $query->where('transactions.related_id', 'like', '%' . $f->relatedLike . '%');
        }

        if ($f->sumMin !== null) {
            $query->where('transactions.sum', '>=', $f->sumMin);
        }

        if ($f->sumMax !== null) {
            $query->where('transactions.sum', '<=', $f->sumMax);
        }

        if ($f->isPaid !== null) {
            $query->where('transactions.is_paid', $f->isPaid);
        }

        if ($f->notesLike) {
            $query->where('transactions.notes', 'like', '%' . $f->notesLike . '%');
        }

        if ($f->datePaidFrom) {
            $query->whereDate('transactions.date_is_paid', '>=', $f->datePaidFrom);
        }

        if ($f->datePaidTo) {
            $query->whereDate('transactions.date_is_paid', '<=', $f->datePaidTo);
        }

        if ($f->isCompleted !== null) {
            $query->where('transactions.is_completed', $f->isCompleted);
        }

        if ($f->dateCompletedFrom) {
            $query->whereDate('transactions.date_is_completed', '>=', $f->dateCompletedFrom);
        }

        if ($f->dateCompletedTo) {
            $query->whereDate('transactions.date_is_completed', '<=', $f->dateCompletedTo);
        }

        if ($f->dateFrom) {
            $query->whereDate('transactions.created_at', '>=', $f->dateFrom);
        }

        if ($f->dateTo) {
            $query->whereDate('transactions.created_at', '<=', $f->dateTo);
        }

        if ($f->search) {
            $search = $f->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('transactions.notes', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function applyToReceipts(Builder $query, ReceiptFilterDTO $f): Builder
    {
        $query->select('receipts.*');
        $joinedCounterparties = false;
        $joinedContractCounterparties = false;

        if ($f->tenantId !== null) {
            $query->where('receipts.tenant_id', $f->tenantId);
        }

        if ($f->companyId) {
            $query->where('receipts.company_id', $f->companyId);
        }

        if ($f->idLike) {
            $query->where('receipts.id', 'like', '%' . $f->idLike . '%');
        }

        if ($f->cashBoxId) {
            $query->where('receipts.cashbox_id', $f->cashBoxId);
        }

        if ($f->contractId) {
            $query->where('receipts.contract_id', $f->contractId);
        }

        if ($f->counterpartyId) {
            $query->where('receipts.counterparty_id', $f->counterpartyId);
        }

        if ($f->counterpartySearch) {
            if (!$joinedCounterparties) {
                $query->leftJoin('counterparties as cp', 'cp.id', '=', 'receipts.counterparty_id');
                $joinedCounterparties = true;
            }
            if (!$joinedContractCounterparties) {
                $query->leftJoin('contracts as ct', 'ct.id', '=', 'receipts.contract_id');
                $query->leftJoin('counterparties as cpc', 'cpc.id', '=', 'ct.counterparty_id');
                $joinedContractCounterparties = true;
            }
            $term = $f->counterpartySearch;
            $query->where(function (Builder $q) use ($term) {
                $q->where('cp.name', 'like', '%' . $term . '%')
                    ->orWhere('cpc.name', 'like', '%' . $term . '%');
            });
        }

        if ($f->sumMin !== null) {
            $query->where('receipts.sum', '>=', $f->sumMin);
        }

        if ($f->sumMax !== null) {
            $query->where('receipts.sum', '<=', $f->sumMax);
        }

        if ($f->paymentDateFrom) {
            $query->whereDate('receipts.payment_date', '>=', $f->paymentDateFrom);
        }

        if ($f->paymentDateTo) {
            $query->whereDate('receipts.payment_date', '<=', $f->paymentDateTo);
        }

        if ($f->dateFrom) {
            $query->whereDate('receipts.created_at', '>=', $f->dateFrom);
        }

        if ($f->dateTo) {
            $query->whereDate('receipts.created_at', '<=', $f->dateTo);
        }

        if ($f->search) {
            $search = $f->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('receipts.description', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function applyToSpendings(Builder $query, SpendingFilterDTO $f): Builder
    {
        $query->select('spendings.*');
        $joinedCounterparties = false;

        if ($f->tenantId !== null) {
            $query->where('spendings.tenant_id', $f->tenantId);
        }

        if ($f->companyId) {
            $query->where('spendings.company_id', $f->companyId);
        }

        if ($f->idLike) {
            $query->where('spendings.id', 'like', '%' . $f->idLike . '%');
        }

        if ($f->cashBoxId) {
            $query->where('spendings.cashbox_id', $f->cashBoxId);
        }

        if ($f->fondId) {
            $query->where('spendings.fond_id', $f->fondId);
        }

        if ($f->spendingItemId) {
            $query->where('spendings.spending_item_id', $f->spendingItemId);
        }

        if ($f->contractId) {
            $query->where('spendings.contract_id', $f->contractId);
        }

        if ($f->counterpartyId) {
            $query->where('spendings.counterparty_id', $f->counterpartyId);
        }

        if ($f->counterpartySearch) {
            if (!$joinedCounterparties) {
                $query->leftJoin('counterparties as cp', 'cp.id', '=', 'spendings.counterparty_id');
                $joinedCounterparties = true;
            }
            $query->where('cp.name', 'like', '%' . $f->counterpartySearch . '%');
        }

        if ($f->spentToUserId) {
            $query->where('spendings.spent_to_user_id', $f->spentToUserId);
        }

        if ($f->sumMin !== null) {
            $query->where('spendings.sum', '>=', $f->sumMin);
        }

        if ($f->sumMax !== null) {
            $query->where('spendings.sum', '<=', $f->sumMax);
        }

        if ($f->paymentDateFrom) {
            $query->whereDate('spendings.payment_date', '>=', $f->paymentDateFrom);
        }

        if ($f->paymentDateTo) {
            $query->whereDate('spendings.payment_date', '<=', $f->paymentDateTo);
        }

        if ($f->dateFrom) {
            $query->whereDate('spendings.created_at', '>=', $f->dateFrom);
        }

        if ($f->dateTo) {
            $query->whereDate('spendings.created_at', '<=', $f->dateTo);
        }

        if ($f->search) {
            $search = $f->search;
            $query->where(function (Builder $q) use ($search) {
                $q->where('spendings.description', 'like', "%{$search}%");
            });
        }

        return $query;
    }
}
