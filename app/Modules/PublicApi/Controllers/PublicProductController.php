<?php

namespace App\Modules\PublicApi\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PublicApi\DTO\PublicProductFilterDTO;
use App\Modules\PublicApi\Services\PublicContextResolver;
use App\Modules\PublicApi\Services\PublicProductService;
use App\Modules\PublicApi\Transformers\ProductCardTransformer;
use App\Modules\PublicApi\Transformers\ProductPageTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicProductController extends Controller
{
    public function __construct(
        private readonly PublicProductService $productService,
        private readonly ProductCardTransformer $cardTransformer,
        private readonly ProductPageTransformer $pageTransformer,
        private readonly PublicContextResolver $contextResolver,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = PublicProductFilterDTO::fromRequest($request);
        $context = $this->contextResolver->resolve($filters->city, $filters->company_id);
        if (isset($context['error'])) {
            return response()->json([
                'error' => $context['error'],
            ], 400);
        }

        $companyId = (int) $context['company_id'];
        $citySlug = $filters->city;
        $noCache = $request->boolean('no_cache');

        $cacheKey = sprintf(
            'public:products:list:v2:company:%d:city:%s:page:%d:per:%d:hash:%s',
            $companyId,
            $citySlug ?? 'none',
            $filters->page,
            $filters->per_page,
            sha1(json_encode([
                'category' => $filters->category,
                'category_id' => $filters->category_id,
                'sub_category_id' => $filters->sub_category_id,
                'brand_id' => $filters->brand_id,
                'price_min' => $filters->price_min,
                'price_max' => $filters->price_max,
                'q' => $filters->q,
                'attrs' => $filters->attrs,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'x')
        );

        $buildPayload = function () use ($filters, $companyId) {
            $products = $this->productService->paginateProducts($filters, $companyId);

            $data = collect($products->items())
                ->map(fn ($product) => $this->cardTransformer->toDTO($product, $companyId)->toArray())
                ->values()
                ->all();

            return [
                'data' => $data,
                'meta' => [
                    'page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more' => $products->currentPage() < $products->lastPage(),
                ],
            ];
        };

        $payload = $noCache
            ? $buildPayload()
            : Cache::remember($cacheKey, now()->addSeconds(300), $buildPayload);

        return response()
            ->json($payload)
            ->header('Cache-Control', $noCache ? 'no-store' : 'public, max-age=300');
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $citySlug = trim((string) $request->get('city', ''));
        $citySlug = $citySlug === '' ? null : $citySlug;

        $companyId = $request->integer('company_id');
        $companyId = $companyId > 0 ? $companyId : null;

        $context = $this->contextResolver->resolve($citySlug, $companyId);
        if (isset($context['error'])) {
            return response()->json([
                'error' => $context['error'],
            ], 400);
        }

        $resolvedCompanyId = (int) $context['company_id'];
        $cacheKey = 'public:product:slug:' . $slug . ':company:' . $resolvedCompanyId . ':city:' . ($citySlug ?? 'none');
        $noCache = $request->boolean('no_cache');

        $buildPayload = function () use ($slug, $resolvedCompanyId) {
            $product = $this->productService->findBySlug($slug, $resolvedCompanyId);
            if (!$product) {
                return null;
            }

            $relatedProducts = $this->productService->getRelatedProducts($product, $resolvedCompanyId, 8);
            $relatedDtos = array_map(
                fn ($p) => $this->cardTransformer->toDTO($p, $resolvedCompanyId),
                $relatedProducts,
            );

            return $this->pageTransformer->toDTO($product, $resolvedCompanyId, $relatedDtos)->toArray();
        };

        $payload = $noCache
            ? $buildPayload()
            : Cache::remember($cacheKey, now()->addSeconds(300), $buildPayload);

        if ($payload === null) {
            return response()->json([
                'error' => 'product_not_found',
            ], 404)->header('Cache-Control', 'public, max-age=60');
        }

        return response()->json([
            'data' => $payload,
        ])->header('Cache-Control', $noCache ? 'no-store' : 'public, max-age=300');
    }

}
