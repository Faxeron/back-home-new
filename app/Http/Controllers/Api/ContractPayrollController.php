<?php

namespace App\Http\Controllers\Api;

use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractStatus;
use App\Domain\Finance\Models\PayrollAccrual;
use App\Services\Finance\PayrollService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContractPayrollController extends Controller
{
    public function index(Request $request, int $contract): JsonResponse
    {
        $model = $this->resolveContract($request, $contract);

        $items = PayrollAccrual::query()
            ->where('contract_id', $model->id)
            ->when($model->tenant_id, fn ($q) => $q->where('tenant_id', $model->tenant_id))
            ->when($model->company_id, fn ($q) => $q->where('company_id', $model->company_id))
            ->orderByDesc('id')
            ->get();

        foreach ($items as $item) {
            $this->syncAccrualStatus($item);
        }

        return response()->json(['data' => $items]);
    }

    public function storeManual(Request $request, int $contract): JsonResponse
    {
        $this->ensureAdmin($request);
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:bonus,penalty'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $model = $this->resolveContract($request, $contract);

        $accrual = app(PayrollService::class)->createManualAccrual(
            $model,
            $validated['type'],
            (float) $validated['amount'],
            $validated['comment'] ?? null,
            $request->user()?->id
        );

        return response()->json(['data' => $accrual], 201);
    }

    public function recalc(Request $request, int $contract): JsonResponse
    {
        $this->ensureAdmin($request);

        $model = $this->resolveContract($request, $contract);
        $model->load('status');

        if (!$model->status || !$this->isCompletedStatus($model->status)) {
            return response()->json([
                'message' => 'Договор еще не в статусе "Выполнен".',
            ], 409);
        }

        app(PayrollService::class)->accrueMarginForContract($model, $request->user()?->id);

        return response()->json(['status' => 'ok']);
    }

    private function resolveContract(Request $request, int $contractId): Contract
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()->where('id', $contractId);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    private function isCompletedStatus(ContractStatus $status): bool
    {
        $code = strtoupper((string) $status->code);
        if (in_array($code, ['COMPLETED', 'DONE', 'FINISHED', 'DONE_WORK', 'DONE_MONTAGE'], true)) {
            return true;
        }

        $name = mb_strtolower((string) $status->name);
        return str_contains($name, 'выполн');
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Only admins can manage payroll.');
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
            abort(403, 'Only admins can manage payroll.');
        }
    }

    private function syncAccrualStatus(PayrollAccrual $accrual): void
    {
        if (($accrual->status ?? 'active') === 'cancelled') {
            return;
        }

        $amount = (float) ($accrual->amount ?? 0);
        $paid = (float) ($accrual->paid_amount ?? 0);
        $nextStatus = $paid >= $amount && $amount > 0 ? 'paid' : 'active';

        if ($accrual->status !== $nextStatus) {
            $accrual->status = $nextStatus;
            $accrual->save();
        }
    }
}

