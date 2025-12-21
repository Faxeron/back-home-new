<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\TransactionType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TransactionTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $data = TransactionType::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'sign', 'is_active', 'sort_order']);

        return response()->json(['data' => $data]);
    }
}
