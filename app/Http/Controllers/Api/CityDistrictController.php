<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityDistrictResource;
use App\Domain\Common\Models\CityDistrict;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityDistrictController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cityId = $request->integer('city_id');
        if (!$cityId) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'per_page' => 0,
                    'total' => 0,
                    'last_page' => 0,
                ],
            ]);
        }

        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 100);
        $page = (int) $request->integer('page', 1);
        $search = trim((string) $request->get('q', ''));

        $query = CityDistrict::query()->where('city_id', $cityId);

        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $districts = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($districts->items())->map(
                fn (CityDistrict $district) => (new CityDistrictResource($district))->toArray($request),
            ),
            'meta' => [
                'current_page' => $districts->currentPage(),
                'per_page' => $districts->perPage(),
                'total' => $districts->total(),
                'last_page' => $districts->lastPage(),
            ],
        ]);
    }
}
