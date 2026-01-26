<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\DTO\SpendingFilterDTO;
use App\Http\Resources\SpendingResource;
use App\Http\Requests\Finance\CreateSpendingRequest;
use App\Services\Finance\FinanceService;
use App\Services\Finance\SpendingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SpendingController extends Controller
{
    public function __construct(
        private readonly SpendingService $spendingService,
        private readonly FinanceService $financeService,
    )
    {
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $filter = SpendingFilterDTO::fromRequest($request, $tenantId);
        $filter->tenantId = $tenantId;
        $filter->companyId = $companyId;
        $includes = $request->string('include')->toString() ?: null;

        $spendings = $this->spendingService->paginate($filter, $includes);

        return response()->json([
            'data' => SpendingResource::collection($spendings->items())->toArray($request),
            'meta' => [
                'current_page' => $spendings->currentPage(),
                'per_page' => $spendings->perPage(),
                'total' => $spendings->total(),
                'last_page' => $spendings->lastPage(),
            ],
        ]);
    }

    public function store(CreateSpendingRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['created_by_user_id'] = $request->user()?->id ?? null;
        $payload['tenant_id'] = $request->user()?->tenant_id;
        $payload['company_id'] = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$payload['tenant_id'] || !$payload['company_id']) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $spending = $this->financeService->createSpending($payload);

        return response()->json($spending, 201);
    }

    public function destroy(Request $request, int $spending): JsonResponse
    {
        $this->ensureAdmin($request);

        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        try {
            $this->financeService->deleteSpending($spending, (int) $tenantId, (int) $companyId, $request->user()?->id);
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() === 'Spending not found') {
                return response()->json(['message' => 'Spending not found'], 404);
            }
            throw $exception;
        }

        return response()->json(['status' => 'ok']);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Only admins can delete.');
        }

        $userId = (int) $user->id;
        $db = DB::connection('legacy_new');
        $isAdmin = false;

        if (Schema::connection('legacy_new')->hasTable('role_users') && Schema::connection('legacy_new')->hasTable('roles')) {
            $isAdmin = $db->table('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->where('role_users.user_id', $userId)
                ->where(function ($query) {
                    $query->where('roles.code', 'admin');
                })
                ->exists();
        }

        $isOwner = false;
        if (Schema::connection('legacy_new')->hasTable('user_company')) {
            $isOwner = $db->table('user_company')
                ->where('user_id', $userId)
                ->where('role', 'owner')
                ->exists();
        }

        if (!$isAdmin && !$isOwner && $userId !== 1) {
            abort(403, 'Only admins can delete.');
        }
    }
}
