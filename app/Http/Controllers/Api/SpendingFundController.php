<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSpendingFundRequest;
use App\Http\Requests\UpdateSpendingFundRequest;
use App\Http\Resources\SpendingFundResource;
use App\Domain\Finance\Models\SpendingFund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpendingFundController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 25 : min($perPage, 100);

        $query = SpendingFund::query()->withCount('items');

        if ($search = $request->string('q')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy('name');

        $funds = $query->paginate($perPage);

        return response()->json([
            'data' => SpendingFundResource::collection($funds),
            'meta' => [
                'current_page' => $funds->currentPage(),
                'per_page' => $funds->perPage(),
                'total' => $funds->total(),
                'last_page' => $funds->lastPage(),
            ],
        ]);
    }

    public function store(StoreSpendingFundRequest $request): SpendingFundResource
    {
        $fund = SpendingFund::create($request->validated());

        return new SpendingFundResource($fund);
    }

    public function update(UpdateSpendingFundRequest $request, SpendingFund $spendingFund): SpendingFundResource
    {
        $spendingFund->update($request->validated());

        return new SpendingFundResource($spendingFund->loadCount('items'));
    }

    public function destroy(SpendingFund $spendingFund): JsonResponse
    {
        $spendingFund->delete();

        return response()->json(['status' => 'ok']);
    }
}
