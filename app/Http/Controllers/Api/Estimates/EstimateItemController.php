<?php

namespace App\Http\Controllers\Api\Estimates;

use App\Domain\Estimates\Models\Estimate;
use App\Domain\Estimates\Models\EstimateItem;
use App\Http\Controllers\Controller;
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
        $itemModel->load('product');

        return response()->json([
            'data' => (new EstimateItemResource($itemModel))->toArray($request),
        ]);
    }

    private function resolveEstimate(Request $request, int $estimateId): Estimate
    {
        $tenantId = $request->user()?->tenant_id ?? $request->integer('tenant_id') ?: null;
        $companyId = $request->user()?->company_id ?? $request->integer('company_id') ?: null;

        $query = Estimate::query()->where('id', $estimateId);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where(function ($builder) use ($companyId) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });
        }

        return $query->firstOrFail();
    }
}
