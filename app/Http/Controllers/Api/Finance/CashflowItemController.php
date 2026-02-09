<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\CashflowItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCashflowItemRequest;
use App\Http\Requests\Finance\UpdateCashflowItemRequest;
use App\Http\Resources\CashflowItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashflowItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $section = strtoupper((string) $request->get('section', ''));
        $direction = strtoupper((string) $request->get('direction', ''));
        $isActive = $request->get('is_active', null);
        $code = trim((string) $request->get('code', ''));
        $name = trim((string) $request->get('name', ''));
        $parentId = $request->integer('parent_id') ?: null;

        $query = CashflowItem::query()
            ->where(function ($builder) use ($tenantId) {
                $builder->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenantId);
            })
            ->where(function ($builder) use ($companyId) {
                $builder->whereNull('company_id')
                    ->orWhere('company_id', $companyId);
            });

        if ($section !== '') {
            $query->where('section', $section);
        }
        if ($direction !== '') {
            $query->where('direction', $direction);
        }
        if ($isActive !== null) {
            $query->where('is_active', (int) $isActive === 1);
        }
        if ($code !== '') {
            $query->where('code', 'like', "%{$code}%");
        }
        if ($name !== '') {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($parentId) {
            $query->where('parent_id', $parentId);
        }

        $items = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CashflowItemResource::collection($items)->toArray($request),
        ]);
    }

    public function store(StoreCashflowItemRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $payload = $request->validated();
        $payload['tenant_id'] = $payload['tenant_id'] ?? $tenantId;
        $payload['company_id'] = $payload['company_id'] ?? $companyId;
        $payload['created_by'] = $payload['created_by'] ?? $user?->id;
        $payload['created_at'] = $payload['created_at'] ?? now();

        $item = CashflowItem::create($payload);

        return response()->json([
            'data' => (new CashflowItemResource($item))->toArray($request),
        ], 201);
    }

    public function update(UpdateCashflowItemRequest $request, CashflowItem $cashflowItem): JsonResponse
    {
        $user = $request->user();
        $payload = $request->validated();
        $payload['updated_by'] = $payload['updated_by'] ?? $user?->id;
        $payload['updated_at'] = $payload['updated_at'] ?? now();

        $cashflowItem->update($payload);

        return response()->json([
            'data' => (new CashflowItemResource($cashflowItem))->toArray($request),
        ]);
    }

    public function destroy(Request $request, CashflowItem $cashflowItem): JsonResponse
    {
        $user = $request->user();
        $cashflowItem->is_active = false;
        $cashflowItem->updated_by = $user?->id;
        $cashflowItem->updated_at = now();
        $cashflowItem->save();

        return response()->json(['status' => 'ok']);
    }
}
