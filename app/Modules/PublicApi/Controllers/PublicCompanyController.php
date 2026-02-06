<?php

namespace App\Modules\PublicApi\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicApi\Services\PublicCompanyService;
use App\Modules\PublicApi\Services\PublicContextResolver;
use App\Modules\PublicApi\Transformers\CompanyTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicCompanyController extends Controller
{
    public function __construct(
        private readonly PublicCompanyService $companyService,
        private readonly CompanyTransformer $companyTransformer,
        private readonly PublicContextResolver $contextResolver,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $citySlug = trim((string) $request->get('city', ''));
        $citySlug = $citySlug === '' ? null : $citySlug;

        $companyId = $request->integer('company_id');
        $companyId = $companyId > 0 ? $companyId : null;

        $resolvedCompanyId = null;
        if ($citySlug || $companyId) {
            $context = $this->contextResolver->resolve($citySlug, $companyId);
            if (isset($context['error'])) {
                return response()->json([
                    'error' => $context['error'],
                ], 400);
            }

            $resolvedCompanyId = (int) $context['company_id'];
        }

        if ($resolvedCompanyId !== null) {
            $cacheKey = 'public:companies:list:company:' . $resolvedCompanyId . ':city:' . ($citySlug ?? 'none');

            $payload = Cache::remember($cacheKey, now()->addSeconds(300), function () use ($resolvedCompanyId) {
                $companies = $this->companyService->listCompanies($resolvedCompanyId);

                return $companies
                    ->map(fn ($company) => $this->companyTransformer->toDTO($company)->toArray())
                    ->values()
                    ->all();
            });
        } else {
            $companies = $this->companyService->listCompanies();

            $payload = $companies
                ->map(fn ($company) => $this->companyTransformer->toDTO($company)->toArray())
                ->values()
                ->all();
        }

        return response()->json([
            'data' => $payload,
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
