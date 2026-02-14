<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\FinanceObject;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceObjectLookupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id ? (int) $request->user()?->tenant_id : null;
        $companyId = $request->user()?->default_company_id
            ? (int) $request->user()?->default_company_id
            : ($request->user()?->company_id ? (int) $request->user()?->company_id : null);

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $limit = min(max((int) $request->integer('limit', 20), 1), 100);
        $q = trim((string) $request->input('q', ''));

        $query = FinanceObject::query()
            ->with(['counterparty:id,name'])
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId);

        if ($q !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhereHas('counterparty', function ($counterpartyQuery) use ($q): void {
                        $counterpartyQuery->where('name', 'like', "%{$q}%");
                    });
            });
        }

        if ($request->filled('type')) {
            $query->where('type', (string) $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->boolean('recent')) {
            $query->where('created_by', (int) $request->user()?->id);
        }

        $rows = $query
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'type', 'name', 'code', 'status', 'counterparty_id']);

        return response()->json([
            'data' => $rows->map(static fn (FinanceObject $object) => [
                'id' => $object->id,
                'type' => $object->type?->value ?? $object->type,
                'name' => $object->name,
                'code' => $object->code,
                'status' => $object->status?->value ?? $object->status,
                'counterparty_id' => $object->counterparty_id,
                'counterparty_name' => $object->counterparty?->name,
            ])->values(),
        ]);
    }
}
