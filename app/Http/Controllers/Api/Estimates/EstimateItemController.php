<?php

namespace App\Http\Controllers\Api\Estimates;

use App\Domain\Estimates\Models\Estimate;
use App\Domain\Estimates\Models\EstimateItem;
use App\Domain\Catalog\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Requests\Estimates\EstimateItemStoreRequest;
use App\Http\Requests\Estimates\EstimateItemUpdateRequest;
use App\Http\Resources\EstimateItemResource;
use App\Services\Estimates\EstimateTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstimateItemController extends Controller
{
    public function __construct(private readonly EstimateTemplateService $estimateTemplateService)
    {
    }

    public function store(EstimateItemStoreRequest $request, int $estimate): JsonResponse
    {
        $model = $this->resolveEstimate($request, $estimate);
        $data = $request->validated();

        $product = null;
        if (!empty($data['product_id'])) {
            $product = Product::query()
                ->where('id', $data['product_id'])
                ->when($model->tenant_id, fn ($query) => $query->where('tenant_id', $model->tenant_id))
                ->when($model->company_id, function ($query) use ($model) {
                    $query->where(function ($builder) use ($model) {
                        $builder->where('company_id', $model->company_id)
                            ->orWhere('is_global', true);
                    });
                })
                ->first();
        } elseif (!empty($data['scu'])) {
            $product = Product::query()
                ->where('scu', $data['scu'])
                ->when($model->tenant_id, fn ($query) => $query->where('tenant_id', $model->tenant_id))
                ->when($model->company_id, function ($query) use ($model) {
                    $query->where(function ($builder) use ($model) {
                        $builder->where('company_id', $model->company_id)
                            ->orWhere('is_global', true);
                    });
                })
                ->first();
        }

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $item = $this->estimateTemplateService->createManualItem(
            $model,
            $product,
            (float) $data['qty'],
            array_key_exists('price', $data) ? (float) $data['price'] : null,
        );

        $item->load(['product', 'group']);

        return response()->json([
            'data' => (new EstimateItemResource($item))->toArray($request),
        ]);
    }

    public function update(EstimateItemUpdateRequest $request, int $estimate, int $item): JsonResponse
    {
        $model = $this->resolveEstimate($request, $estimate);
        $itemModel = EstimateItem::query()
            ->where('estimate_id', $model->id)
            ->where('id', $item)
            ->firstOrFail();

        $data = $request->validated();

        if (array_key_exists('qty', $data)) {
            $this->estimateTemplateService->updateManualQty($itemModel, (float) $data['qty']);
        }

        if (array_key_exists('price', $data)) {
            $itemModel->price = (float) $data['price'];
            $itemModel->total = (float) $itemModel->qty * (float) $itemModel->price;
        }

        if ($request->user()) {
            $itemModel->updated_by = $request->user()->id;
        }

        $itemModel->save();
        $itemModel->load(['product', 'group']);

        return response()->json([
            'data' => (new EstimateItemResource($itemModel))->toArray($request),
        ]);
    }

    private function resolveEstimate(Request $request, int $estimateId): Estimate
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Estimate::query()->where('id', $estimateId);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }
}
