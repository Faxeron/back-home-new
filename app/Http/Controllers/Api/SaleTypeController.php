<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleTypeResource;
use App\Domain\CRM\Models\SaleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 100);
        $page = (int) $request->integer('page', 1);
        $search = trim((string) $request->get('q', ''));

        $query = SaleType::query();

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $items = $query->orderBy('id')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($items->items())->map(
                fn (SaleType $saleType) => (new SaleTypeResource($saleType))->toArray($request),
            ),
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $saleType = SaleType::create($validated);

        return response()->json(new SaleTypeResource($saleType), 201);
    }

    public function update(Request $request, SaleType $saleType): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $saleType->update($validated);

        return response()->json(new SaleTypeResource($saleType));
    }

    public function destroy(SaleType $saleType): JsonResponse
    {
        $saleType->delete();

        return response()->json([], 204);
    }
}
