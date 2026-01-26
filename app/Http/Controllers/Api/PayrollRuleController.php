<?php

namespace App\Http\Controllers\Api;

use App\Domain\Finance\Models\PayrollRule;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PayrollRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = PayrollRule::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('user_id')
            ->orderBy('document_type');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:legacy_new.users,id'],
            'document_type' => ['required', 'string', 'max:20', 'in:supply,install,combined'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'margin_percent' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $rule = PayrollRule::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'user_id' => $validated['user_id'],
            'document_type' => $validated['document_type'],
            'fixed_amount' => $validated['fixed_amount'] ?? 0,
            'margin_percent' => $validated['margin_percent'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);

        return response()->json(['data' => $rule], 201);
    }

    public function update(Request $request, int $rule): JsonResponse
    {
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'document_type' => ['sometimes', 'string', 'max:20', 'in:supply,install,combined'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'margin_percent' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $model = PayrollRule::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('id', $rule)
            ->firstOrFail();

        $model->fill($validated);
        $model->updated_by = $user?->id;
        $model->save();

        return response()->json(['data' => $model]);
    }

    public function destroy(Request $request, int $rule): JsonResponse
    {
        $this->ensureAdmin($request);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        PayrollRule::query()
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('id', $rule)
            ->delete();

        return response()->json(['status' => 'ok']);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Only admins can manage payroll rules.');
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
            abort(403, 'Only admins can manage payroll rules.');
        }
    }
}
