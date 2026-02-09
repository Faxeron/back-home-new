<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpendingItemRequest;
use App\Http\Requests\UpdateSpendingItemRequest;
use App\Http\Resources\SpendingItemResource;
use App\Domain\Finance\Models\SpendingItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpendingItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 25 : min($perPage, 100);

        $query = SpendingItem::query();

        if ($fundId = $request->integer('fund_id')) {
            $query->where('fond_id', $fundId);
        }

        if ($cashflowId = $request->integer('cashflow_item_id')) {
            $query->where('cashflow_item_id', $cashflowId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy('name');

        $items = $query->paginate($perPage);

        return response()->json([
            'data' => SpendingItemResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function store(StoreSpendingItemRequest $request): SpendingItemResource
    {
        $item = SpendingItem::create($request->validated());

        return new SpendingItemResource($item);
    }

    public function update(UpdateSpendingItemRequest $request, SpendingItem $spendingItem): SpendingItemResource
    {
        $spendingItem->update($request->validated());

        return new SpendingItemResource($spendingItem);
    }

    public function destroy(SpendingItem $spendingItem): JsonResponse
    {
        $spendingItem->delete();

        return response()->json(['status' => 'ok']);
    }
}
