<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Domain\CRM\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 100);
        $page = (int) $request->integer('page', 1);

        $query = Contract::query()
            ->with(['counterparty', 'status'])
            ->orderByDesc('contract_date');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status_id'))
            $query->where('contract_status_id', $request->integer('status_id'));

        if ($request->filled('counterparty_id'))
            $query->where('counterparty_id', $request->integer('counterparty_id'));

        if ($request->filled('company_id'))
            $query->where('company_id', $request->integer('company_id'));

        $contracts = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($contracts->items())->map(
                fn (Contract $contract) => (new ContractResource($contract))->toArray($request),
            ),
            'meta' => [
                'current_page' => $contracts->currentPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
                'last_page' => $contracts->lastPage(),
            ],
        ]);
    }
}
