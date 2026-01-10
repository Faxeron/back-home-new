<?php

namespace App\Services\Finance;

use App\Domain\Finance\DTO\ReceiptData;
use App\Domain\Finance\DTO\ReceiptFilterDTO;
use App\Domain\Finance\Models\Receipt;
use App\Events\PaymentAppliedToContract;
use App\Events\ReceiptCreated;
use App\Services\Finance\Filters\FilterBuilder;
use App\Services\Finance\Filters\IncludeRegistry;
use App\Services\Finance\Filters\SortBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReceiptService
{
    public function __construct(
        private readonly FilterBuilder $filterBuilder,
        private readonly SortBuilder $sortBuilder,
        private readonly IncludeRegistry $includeRegistry,
    )
    {
    }

    public function paginate(ReceiptFilterDTO $filters, ?string $includes = null): LengthAwarePaginator
    {
        $query = Receipt::query();

        $this->filterBuilder->applyToReceipts($query, $filters);

        $with = $this->includeRegistry->resolve('receipts', $includes);
        if (!empty($with)) {
            $query->with($with);
        }

        $this->sortBuilder->apply($query, $filters, ['payment_date', 'sum', 'created_at'], 'created_at');

        return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function create(ReceiptData|array $payload): Receipt
    {
        return DB::connection('legacy_new')->transaction(function () use ($payload) {
            $receipt = Receipt::create($this->normalize($payload));
            event(new ReceiptCreated($receipt));

            if (!empty($receipt->contract_id)) {
                event(new PaymentAppliedToContract((int) $receipt->contract_id, $receipt));
            }

            return $receipt;
        });
    }

    public function update(Receipt $receipt, ReceiptData|array $payload): Receipt
    {
        return DB::connection('legacy_new')->transaction(function () use ($receipt, $payload) {
            $receipt->update($this->normalize($payload));

            return $receipt->refresh();
        });
    }

    public function delete(Receipt $receipt): void
    {
        DB::connection('legacy_new')->transaction(fn () => $receipt->delete());
    }

    public function attachTransaction(Receipt $receipt, int $transactionId): void
    {
        $receipt->transaction_id = $transactionId;
        $receipt->save();
    }
    private function normalize(ReceiptData|array $payload): array
    {
        if ($payload instanceof ReceiptData) {
            return $payload->toArray();
        }

        return ReceiptData::fromArray($payload)->toArray();
    }
}
