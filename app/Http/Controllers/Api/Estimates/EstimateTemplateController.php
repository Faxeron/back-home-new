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
use Illuminate\Support\Facades\Log;

class EstimateTemplateController extends Controller
{
    public function __construct(private readonly EstimateTemplateService $estimateTemplateService)
    {
    }

    public function applyTemplate(EstimateTemplateApplyRequest $request, int $estimate): JsonResponse
    {
        $model = $this->resolveEstimate($request, $estimate);
        $data = $request->validated();

        Log::info('estimate_template.apply.request', [
            'estimate_id' => $model->id,
            'root_scu' => $data['root_scu'] ?? null,
            'root_qty' => $data['root_qty'] ?? null,
            'template_id' => $data['template_id'] ?? null,
            'tenant_id' => $model->tenant_id,
            'company_id' => $model->company_id,
            'user_id' => $request->user()?->id,
        ]);

        $this->estimateTemplateService->applyTemplateBySku(
            $model,
            $data['root_scu'],
            (float) $data['root_qty'],
            isset($data['template_id']) ? (int) $data['template_id'] : null,
        );

        $items = EstimateItem::query()
            ->with(['product', 'group'])
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
