<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\FinanceObject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreFinanceObjectRequest;
use App\Http\Requests\Finance\UpdateFinanceObjectRequest;
use App\Http\Resources\FinanceObjectResource;
use App\Http\Resources\TransactionResource;
use App\Services\Finance\FinanceObjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class FinanceObjectController extends Controller
{
    public function __construct(private readonly FinanceObjectService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        [$tenantId, $companyId] = $this->resolveContext($request);
        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $objects = $this->service->paginate($tenantId, $companyId, $request->all());

        return response()->json([
            'data' => FinanceObjectResource::collection($objects->items())->toArray($request),
            'meta' => [
                'current_page' => $objects->currentPage(),
                'per_page' => $objects->perPage(),
                'total' => $objects->total(),
                'last_page' => $objects->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $financeObject): JsonResponse
    {
        [$tenantId, $companyId] = $this->resolveContext($request);
        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $object = $this->findObject($financeObject, $tenantId, $companyId);
        if (!$object) {
            return response()->json(['message' => 'Finance object not found.'], 404);
        }

        $object->load(['counterparty', 'legalContract']);
        $object->setAttribute('kpi', $this->service->kpi($object));

        return response()->json([
            'data' => (new FinanceObjectResource($object))->toArray($request),
        ]);
    }

    public function store(StoreFinanceObjectRequest $request): JsonResponse
    {
        [$tenantId, $companyId] = $this->resolveContext($request);
        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $payload = $request->validated();
        try {
            $object = $this->service->create($tenantId, $companyId, $payload, $request->user()?->id);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
        $object->load(['counterparty', 'legalContract']);
        $object->setAttribute('kpi', $this->service->kpi($object));

        return response()->json([
            'data' => (new FinanceObjectResource($object))->toArray($request),
        ], 201);
    }

    public function update(UpdateFinanceObjectRequest $request, int $financeObject): JsonResponse
    {
        [$tenantId, $companyId] = $this->resolveContext($request);
        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $object = $this->findObject($financeObject, $tenantId, $companyId);
        if (!$object) {
            return response()->json(['message' => 'Finance object not found.'], 404);
        }

        $payload = $request->validated();
        try {
            $object = $this->service->update($object, $payload, $request->user()?->id);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
        $object->load(['counterparty', 'legalContract']);
        $object->setAttribute('kpi', $this->service->kpi($object));

        return response()->json([
            'data' => (new FinanceObjectResource($object))->toArray($request),
        ]);
    }

    public function transactions(Request $request, int $financeObject): JsonResponse
    {
        [$tenantId, $companyId] = $this->resolveContext($request);
        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $object = $this->findObject($financeObject, $tenantId, $companyId);
        if (!$object) {
            return response()->json(['message' => 'Finance object not found.'], 404);
        }

        $perPage = min(max((int) $request->integer('per_page', 50), 1), 200);
        $rows = $this->service->transactions((int) $object->id, $tenantId, $companyId, $perPage);

        return response()->json([
            'data' => TransactionResource::collection($rows->items())->toArray($request),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
            ],
        ]);
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function resolveContext(Request $request): array
    {
        $tenantId = $request->user()?->tenant_id ? (int) $request->user()?->tenant_id : null;
        $companyId = $request->user()?->default_company_id
            ? (int) $request->user()?->default_company_id
            : ($request->user()?->company_id ? (int) $request->user()?->company_id : null);

        return [$tenantId, $companyId];
    }

    private function findObject(int $id, int $tenantId, int $companyId): ?FinanceObject
    {
        return FinanceObject::query()
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->first();
    }
}
