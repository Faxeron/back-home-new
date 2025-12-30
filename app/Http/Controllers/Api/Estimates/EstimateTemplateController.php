<?php

namespace App\Http\Controllers\Api\Estimates;

use App\Domain\Estimates\Models\Estimate;
use App\Domain\Estimates\Models\EstimateItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Estimates\EstimateTemplateApplyRequest;
use App\Http\Resources\EstimateItemResource;
use App\Services\Estimates\EstimateTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstimateTemplateController extends Controller
{
    public function __construct(private readonly EstimateTemplateService $estimateTemplateService)
    {
    }

    public function applyTemplate(EstimateTemplateApplyRequest $request, int $estimate): JsonResponse
    {
        $model = $this->resolveEstimate($request, $estimate);
        $data = $request->validated();

        $this->estimateTemplateService->applyTemplateBySku(
            $model,
            $data['root_scu'],
            (float) $data['root_qty']
        );

        $items = EstimateItem::query()
            ->with('product')
            ->where('estimate_id', $model->id)
            ->orderBy('group_id')
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'data' => EstimateItemResource::collection($items)->toArray($request),
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
