<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashBoxRequest;
use App\Http\Requests\UpdateCashBoxRequest;
use App\Http\Resources\CashBoxResource;
use App\Domain\Finance\Models\CashBox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CashBoxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 25 : min($perPage, 100);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = CashBox::query()
            ->select('cashboxes.*')
            ->distinct()
            ->with('company')
            ->join('cashbox_company as cc', 'cc.cashbox_id', '=', 'cashboxes.id')
            ->where('cc.company_id', $companyId);

        if ($tenantId) {
            $query->where('cashboxes.tenant_id', $tenantId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy('name');

        $cashBoxes = $query->paginate($perPage);

        return response()->json([
            'data' => CashBoxResource::collection($cashBoxes),
            'meta' => [
                'current_page' => $cashBoxes->currentPage(),
                'per_page' => $cashBoxes->perPage(),
                'total' => $cashBoxes->total(),
                'last_page' => $cashBoxes->lastPage(),
            ],
        ]);
    }

    public function store(StoreCashBoxRequest $request): CashBoxResource
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $payload = $request->validated();
        $payload['tenant_id'] = $tenantId;
        $payload['company_id'] = $companyId;

        $cashBox = CashBox::create($payload);

        DB::connection('legacy_new')->table('cashbox_company')->updateOrInsert([
            'cashbox_id' => $cashBox->id,
            'company_id' => $companyId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return new CashBoxResource($cashBox);
    }

    public function update(UpdateCashBoxRequest $request, CashBox $cashBox): CashBoxResource
    {
        $user = $request->user();
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $allowed = DB::connection('legacy_new')
            ->table('cashbox_company')
            ->where('cashbox_id', $cashBox->id)
            ->where('company_id', $companyId)
            ->exists();

        if (!$allowed) {
            abort(403, 'Cash box access denied.');
        }

        $cashBox->update($request->validated());

        return new CashBoxResource($cashBox);
    }

    public function destroy(Request $request, CashBox $cashBox): JsonResponse
    {
        $user = $request->user();
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $allowed = DB::connection('legacy_new')
            ->table('cashbox_company')
            ->where('cashbox_id', $cashBox->id)
            ->where('company_id', $companyId)
            ->exists();

        if (!$allowed) {
            abort(403, 'Cash box access denied.');
        }

        $cashBox->delete();

        return response()->json(['status' => 'ok']);
    }
}
