<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Domain\Common\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 100);
        $page = (int) $request->integer('page', 1);
        $search = trim((string) $request->get('q', ''));

        $query = City::query();

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $cities = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($cities->items())->map(
                fn (City $city) => (new CityResource($city))->toArray($request),
            ),
            'meta' => [
                'current_page' => $cities->currentPage(),
                'per_page' => $cities->perPage(),
                'total' => $cities->total(),
                'last_page' => $cities->lastPage(),
            ],
        ]);
    }
}
