<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashBoxRequest;
use App\Http\Requests\UpdateCashBoxRequest;
use App\Http\Resources\CashBoxResource;
use App\Domain\Finance\Models\CashBox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashBoxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 25 : min($perPage, 100);

        $query = CashBox::query()->with('company');

        if ($search = $request->string('q')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy('name');

        $cashBoxes = $query->paginate($perPage);

        return response()->json([
            'data' => CashBoxResource::collection($cashBoxes),
            'meta' => [
                'current_page' => $cashBoxes->currentPage(),
                'per_page' => $cashBoxes->perPage(),
                'total' => $cashBoxes->total(),
                'last_page' => $cashBoxes->lastPage(),
            ],
        ]);
    }

    public function store(StoreCashBoxRequest $request): CashBoxResource
    {
        $cashBox = CashBox::create($request->validated());

        return new CashBoxResource($cashBox);
    }

    public function update(UpdateCashBoxRequest $request, CashBox $cashBox): CashBoxResource
    {
        $cashBox->update($request->validated());

        return new CashBoxResource($cashBox);
    }

    public function destroy(CashBox $cashBox): JsonResponse
    {
        $cashBox->delete();

        return response()->json(['status' => 'ok']);
    }
}
