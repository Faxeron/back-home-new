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

        $cacheKey = sprintf(
            'public:products:list:company:%d:city:%s:page:%d:per:%d:category:%s',
            $companyId,
            $citySlug ?? 'none',
            $filters->page,
            $filters->per_page,
            $filters->category ?? 'none'
        );

        $payload = Cache::remember($cacheKey, now()->addSeconds(300), function () use ($filters, $companyId) {
            $products = $this->productService->paginateProducts($filters, $companyId);

            $cityMap = $this->productService->getCityMapForCompanyIds([$companyId]);

            $data = collect($products->items())
                ->map(fn ($product) => $this->cardTransformer->toDTO($product, $companyId, $cityMap)->toArray())
                ->values()
                ->all();

            return [
                'data' => $data,
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'last_page' => $products->lastPage(),
                ],
            ];
        });

        return response()->json($payload)->header('Cache-Control', 'public, max-age=300');
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

        $payload = Cache::remember($cacheKey, now()->addSeconds(300), function () use ($slug, $resolvedCompanyId) {
            $product = $this->productService->findBySlug($slug, $resolvedCompanyId);
            if (!$product) {
                return null;
            }

            return $this->pageTransformer->toDTO($product, $resolvedCompanyId)->toArray();
        });

        if ($payload === null) {
            return response()->json([
                'error' => 'product_not_found',
            ], 404)->header('Cache-Control', 'public, max-age=60');
        }

        return response()->json([
            'data' => $payload,
        ])->header('Cache-Control', 'public, max-age=300');
    }

}
