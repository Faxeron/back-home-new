<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\PaymentMethod;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PaymentMethodController extends Controller
{
    public function index(): JsonResponse
    {
        $data = PaymentMethod::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'is_active', 'sort_order']);

        return response()->json(['data' => $data]);
    }
}
