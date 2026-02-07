<?php

namespace App\Modules\PublicApi\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicApi\Services\PublicCatalogService;
use App\Modules\PublicApi\Services\PublicContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicCatalogController extends Controller
{
    public function __construct(
        private readonly PublicCatalogService $catalogService,
        private readonly PublicContextResolver $contextResolver,
    ) {
    }

    private function resolveContext(Request $request): array
    {
        $citySlug = trim((string) $request->get('city', ''));
        $citySlug = $citySlug === '' ? null : $citySlug;

        $companyId = $request->integer('company_id');
        $companyId = $companyId > 0 ? $companyId : null;

        $context = $this->contextResolver->resolve($citySlug, $companyId);

        return [$context, $citySlug];
    }

    public function tree(Request $request): JsonResponse
    {
        [$context, $citySlug] = $this->resolveContext($request);
        if (isset($context['error'])) {
            return response()->json([
                'error' => $context['error'],
            ], 400);
        }

        $resolvedCompanyId = (int) $context['company_id'];
        $noCache = $request->boolean('no_cache');

        $cacheKey = 'public:catalog:tree:company:' . $resolvedCompanyId . ':city:' . ($citySlug ?? 'none');

        $buildPayload = fn () => $this->catalogService->getTree($resolvedCompanyId);

        $payload = $noCache
            ? $buildPayload()
            : Cache::remember($cacheKey, now()->addSeconds(600), $buildPayload);

        return response()
            ->json(['data' => $payload])
            ->header('Cache-Control', $noCache ? 'no-store' : 'public, max-age=600');
    }

    public function category(Request $request, string $slug): JsonResponse
    {
        [$context, $citySlug] = $this->resolveContext($request);
        if (isset($context['error'])) {
            return response()->json(['error' => $context['error']], 400);
        }

        $companyId = (int) $context['company_id'];
        $noCache = $request->boolean('no_cache');

        $cacheKey = 'public:catalog:category:' . $slug . ':company:' . $companyId . ':city:' . ($citySlug ?? 'none');
        $buildPayload = fn () => $this->catalogService->getCategoryPage($companyId, $slug);

        $payload = $noCache
            ? $buildPayload()
            : Cache::remember($cacheKey, now()->addSeconds(600), $buildPayload);

        if ($payload === null) {
            return response()->json(['error' => 'category_not_found'], 404)
                ->header('Cache-Control', $noCache ? 'no-store' : 'public, max-age=300');
        }

        return response()->json(['data' => $payload])
            ->header('Cache-Control', $noCache ? 'no-store' : 'public, max-age=600');
    }

    public function subcategory(Request $request, string $slug): JsonResponse
    {
        [$context, $citySlug] = $this->resolveContext($request);
        if (isset($context['error'])) {
            return response()->json(['error' => $context['error']], 400);
        }

        $companyId = (int) $context['company_id'];
        $noCache = $request->boolean('no_cache');

        $cacheKey = 'public:catalog:subcategory:' . $slug . ':company:' . $companyId . ':city:' . ($citySlug ?? 'none');
        $buildPayload = fn () => $this->catalogService->getSubcategoryPage($companyId, $slug);

        $payload = $noCache
            ? $buildPayload()
            : Cache::remember($cacheKey, now()->addSeconds(600), $buildPayload);

        if ($payload === null) {
            return response()->json(['error' => 'subcategory_not_found'], 404)
                ->header('Cache-Control', $noCache ? 'no-store' : 'public, max-age=300');
        }

        return response()->json(['data' => $payload])
            ->header('Cache-Control', $noCache ? 'no-store' : 'public, max-age=600');
    }

    public function brand(Request $request, string $slug): JsonResponse
    {
        [$context, $citySlug] = $this->resolveContext($request);
        if (isset($context['error'])) {
            return response()->json(['error' => $context['error']], 400);
        }

        $companyId = (int) $context['company_id'];
        $noCache = $request->boolean('no_cache');

        $cacheKey = 'public:catalog:brand:' . $slug . ':company:' . $companyId . ':city:' . ($citySlug ?? 'none');
        $buildPayload = fn () => $this->catalogService->getBrandPage($companyId, $slug);

        $payload = $noCache
            ? $buildPayload()
            : Cache::remember($cacheKey, now()->addSeconds(600), $buildPayload);

        if ($payload === null) {
            return response()->json(['error' => 'brand_not_found'], 404)
                ->header('Cache-Control', $noCache ? 'no-store' : 'public, max-age=300');
        }

        return response()->json(['data' => $payload])
            ->header('Cache-Control', $noCache ? 'no-store' : 'public, max-age=600');
    }
}
