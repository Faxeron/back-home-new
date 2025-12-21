<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\Finance\Models\SpendingItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class SpendingItemLookupController extends Controller
{
    public function index(): JsonResponse
    {
        $data = SpendingItem::query()
            ->orderBy('name')
            ->get(['id', 'name', 'fond_id', 'description', 'is_active']);

        return response()->json(['data' => $data]);
    }
}
