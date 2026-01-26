<?php

namespace App\Http\Controllers\Api;

use App\Domain\Finance\Models\PayrollAccrual;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollAccrualController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = PayrollAccrual::query()
            ->with(['user', 'contract', 'document'])
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderByDesc('id');

        if ($request->filled('contract_id')) {
            $query->where('contract_id', $request->integer('contract_id'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('document_type')) {
            $query->where('document_type', $request->string('document_type')->toString());
        }

        if ($request->boolean('unpaid_only')) {
            $query->where('status', 'active')
                ->whereRaw('amount > COALESCE(paid_amount, 0)');
        }

        $items = $query->limit(5000)->get();

        foreach ($items as $item) {
            $this->syncAccrualStatus($item);
        }

        return response()->json(['data' => $items]);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Only admins can view payroll accruals.');
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
            abort(403, 'Only admins can view payroll accruals.');
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
