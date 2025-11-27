<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractStatusRequest;
use App\Http\Requests\UpdateContractStatusRequest;
use App\Http\Resources\ContractStatusResource;
use App\Domain\CRM\Models\ContractStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractStatusController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 25 : min($perPage, 100);

        $query = ContractStatus::query();

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $query->orderBy('sort_order')->orderBy('id');

        $statuses = $query->paginate($perPage);

        return response()->json([
            'data' => ContractStatusResource::collection($statuses),
            'meta' => [
                'current_page' => $statuses->currentPage(),
                'per_page' => $statuses->perPage(),
                'total' => $statuses->total(),
                'last_page' => $statuses->lastPage(),
            ],
        ]);
    }

    public function store(StoreContractStatusRequest $request): ContractStatusResource
    {
        $status = ContractStatus::create($request->validated());

        return new ContractStatusResource($status);
    }

    public function update(UpdateContractStatusRequest $request, ContractStatus $contractStatus): ContractStatusResource
    {
        $contractStatus->update($request->validated());

        return new ContractStatusResource($contractStatus);
    }

    public function destroy(ContractStatus $contractStatus): JsonResponse
    {
        $contractStatus->delete();

        return response()->json(['status' => 'ok']);
    }
}
