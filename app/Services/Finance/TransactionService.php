<?php

namespace App\Services\Finance;

use App\Domain\Finance\DTO\TransactionData;
use App\Domain\Finance\DTO\TransactionFilterDTO;
use App\Domain\Finance\Models\Transaction;
use App\Events\TransactionCreated;
use App\Events\TransactionUpdated;
use App\Events\TransactionDeleted;
use App\Services\Finance\Filters\FilterBuilder;
use App\Services\Finance\Filters\IncludeRegistry;
use App\Services\Finance\Filters\SortBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(
        private readonly FilterBuilder $filterBuilder,
        private readonly SortBuilder $sortBuilder,
        private readonly IncludeRegistry $includeRegistry,
    )
    {
    }

    public function paginate(TransactionFilterDTO $filters, ?string $includes = null): LengthAwarePaginator
    {
        $query = Transaction::query();

        $this->filterBuilder->applyToTransactions($query, $filters);

        $with = $this->includeRegistry->resolve('transactions', $includes);
        if (!empty($with)) {
            $query->with($with);
        }

        $this->sortBuilder->apply($query, $filters, ['created_at', 'sum', 'date_is_paid', 'date_is_completed'], 'created_at');

        return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function createIncome(TransactionData|array $payload): Transaction
    {
        return DB::connection('legacy_new')->transaction(function () use ($payload) {
            $transaction = Transaction::create($this->normalize($payload));
            event(new TransactionCreated($transaction));

            return $transaction;
        });
    }

    public function createExpense(TransactionData|array $payload): Transaction
    {
        return DB::connection('legacy_new')->transaction(function () use ($payload) {
            $transaction = Transaction::create($this->normalize($payload));
            event(new TransactionCreated($transaction));

            return $transaction;
        });
    }

    public function createTransfer(TransactionData|array $payload): Transaction
    {
        return DB::connection('legacy_new')->transaction(function () use ($payload) {
            $transaction = Transaction::create($this->normalize($payload));
            event(new TransactionCreated($transaction));

            return $transaction;
        });
    }

    public function attachCounterparty(Transaction $transaction, int $counterpartyId): void
    {
        $transaction->counterparty_id = $counterpartyId;
        $transaction->save();
    }

    public function attachContract(Transaction $transaction, int $contractId): void
    {
        $transaction->contract_id = $contractId;
        $transaction->save();
    }

    public function handleAdvance(Transaction $transaction): void
    {
        // TODO: логика аванса.
    }

    public function markPaid(Transaction $transaction): void
    {
        $transaction->is_paid = true;
        $transaction->date_is_paid = now();
        $transaction->save();
    }

    public function markCompleted(Transaction $transaction): void
    {
        $transaction->is_completed = true;
        $transaction->date_is_completed = now();
        $transaction->save();
    }

    public function update(Transaction $transaction, TransactionData|array $payload): Transaction
    {
        return DB::connection('legacy_new')->transaction(function () use ($transaction, $payload) {
            $transaction->update($this->normalize($payload));
            event(new TransactionUpdated($transaction));

            return $transaction->refresh();
        });
    }

    public function delete(Transaction $transaction): void
    {
        DB::connection('legacy_new')->transaction(function () use ($transaction) {
            $transaction->delete();
            event(new TransactionDeleted($transaction));
        });
    }
    private function normalize(TransactionData|array $payload): array
    {
        if ($payload instanceof TransactionData) {
            return $payload->toArray();
        }

        return TransactionData::fromArray($payload)->toArray();
    }
}
