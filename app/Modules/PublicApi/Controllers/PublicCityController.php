<?php

namespace App\Modules\PublicApi\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicApi\Services\PublicCityService;
use App\Modules\PublicApi\Transformers\CityTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicCityController extends Controller
{
    public function __construct(
        private readonly PublicCityService $cityService,
        private readonly CityTransformer $cityTransformer,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->get('q', ''));
        $search = $search === '' ? null : $search;
        $companyId = $request->integer('company_id');
        $companyId = $companyId > 0 ? $companyId : null;

        $cities = $this->cityService->listCities($search, $companyId);

        $data = $cities
            ->map(fn ($city) => $this->cityTransformer->toDTO($city)->toArray())
            ->values()
            ->all();

        return response()->json([
            'data' => $data,
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
