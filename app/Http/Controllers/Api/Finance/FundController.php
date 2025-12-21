<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\SpendingFund;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class FundController extends Controller
{
    public function index(): JsonResponse
    {
        $data = SpendingFund::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description', 'is_active']);

        return response()->json(['data' => $data]);
    }
}
