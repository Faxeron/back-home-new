<?php

namespace App\Http\Controllers\API\Finance;

use App\Http\Controllers\Controller;
use App\Domain\Finance\DTO\SpendingFilterDTO;
use App\Domain\Finance\Models\Spending;
use App\Domain\Finance\Models\Transaction;
use App\Http\Requests\Finance\UpdateSpendingRequest;
use App\Http\Resources\SpendingResource;
use App\Http\Requests\Finance\CreateSpendingRequest;
use App\Services\Finance\FinanceService;
use App\Services\Finance\FinanceObjectAssignmentService;
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
        private readonly FinanceObjectAssignmentService $assignmentService,
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

    public function update(UpdateSpendingRequest $request, int $spending): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $record = Spending::query()
            ->where('id', $spending)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Spending not found'], 404);
        }

        $payload = $request->validated();
        if (empty($payload)) {
            return response()->json(['message' => 'No fields provided for update.'], 422);
        }

        DB::connection('legacy_new')->transaction(function () use ($record, $payload, $tenantId, $companyId) {
            $spendingUpdates = [];

            if (array_key_exists('created_at', $payload)) {
                $spendingUpdates['created_at'] = $payload['created_at'];
            }
            if (array_key_exists('updated_at', $payload)) {
                $spendingUpdates['updated_at'] = $payload['updated_at'];
            }

            if (!array_key_exists('updated_at', $spendingUpdates)) {
                $spendingUpdates['updated_at'] = now();
            }

            if (!empty($spendingUpdates)) {
                Spending::query()
                    ->where('id', $record->id)
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)
                    ->update($spendingUpdates);
            }

            if ($record->transaction_id) {
                $transactionUpdates = [];

                if (array_key_exists('created_at', $payload)) {
                    $transactionUpdates['created_at'] = $payload['created_at'];
                }
                if (array_key_exists('updated_at', $payload)) {
                    $transactionUpdates['updated_at'] = $payload['updated_at'];
                }

                if (!empty($transactionUpdates) && !array_key_exists('updated_at', $transactionUpdates)) {
                    $transactionUpdates['updated_at'] = now();
                }

                if (!empty($transactionUpdates)) {
                    Transaction::query()
                        ->where('id', $record->transaction_id)
                        ->where('tenant_id', $tenantId)
                        ->where('company_id', $companyId)
                        ->update($transactionUpdates);
                }
            }
        });

        if (array_key_exists('finance_object_id', $payload)) {
            if ($record->transaction_id) {
                try {
                    $this->assignmentService->assignTransaction(
                        Transaction::query()->findOrFail($record->transaction_id),
                        $payload['finance_object_id'] !== null ? (int) $payload['finance_object_id'] : null,
                        [],
                        (int) $tenantId,
                        (int) $companyId,
                    );
                } catch (RuntimeException $exception) {
                    return response()->json(['message' => $exception->getMessage()], 422);
                }
            } else {
                $record->forceFill([
                    'finance_object_id' => $payload['finance_object_id'] !== null ? (int) $payload['finance_object_id'] : null,
                ])->save();
            }
        }

        $record->refresh()->loadMissing(['cashbox', 'company', 'counterparty', 'contract', 'financeObject', 'item', 'fund', 'creator']);

        return response()->json([
            'data' => (new SpendingResource($record))->toArray($request),
        ]);
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
