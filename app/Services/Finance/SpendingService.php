<?php

namespace App\Services\Finance;

use App\Domain\Finance\DTO\SpendingData;
use App\Domain\Finance\DTO\SpendingFilterDTO;
use App\Domain\Finance\Models\Spending;
use App\Events\SpendingCreated;
use App\Services\Finance\Filters\FilterBuilder;
use App\Services\Finance\Filters\IncludeRegistry;
use App\Services\Finance\Filters\SortBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SpendingService
{
    public function __construct(
        private readonly FilterBuilder $filterBuilder,
        private readonly SortBuilder $sortBuilder,
        private readonly IncludeRegistry $includeRegistry,
    )
    {
    }

    public function paginate(SpendingFilterDTO $filters, ?string $includes = null): LengthAwarePaginator
    {
        $query = Spending::query();

        $this->filterBuilder->applyToSpendings($query, $filters);

        $with = $this->includeRegistry->resolve('spendings', $includes);
        if (!empty($with)) {
            $query->with($with);
        }

        $this->sortBuilder->apply($query, $filters, ['payment_date', 'sum', 'created_at'], 'created_at');

        return $query->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function create(SpendingData|array $payload): Spending
    {
        return DB::transaction(function () use ($payload) {
            $spending = Spending::create($this->normalize($payload));
            event(new SpendingCreated($spending));

            return $spending;
        });
    }

    public function allocate(Spending $spending, SpendingData|array $payload): Spending
    {
        return DB::transaction(function () use ($spending, $payload) {
            $spending->update($this->normalize($payload));

            return $spending->refresh();
        });
    }

    public function delete(Spending $spending): void
    {
        DB::transaction(fn () => $spending->delete());
    }

    public function attachTransaction(Spending $spending, int $transactionId): void
    {
        $spending->transaction_id = $transactionId;
        $spending->save();
    }
    private function normalize(SpendingData|array $payload): array
    {
        if ($payload instanceof SpendingData) {
            return $payload->toArray();
        }

        return SpendingData::fromArray($payload)->toArray();
    }
}
