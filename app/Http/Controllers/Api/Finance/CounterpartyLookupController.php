<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\CRM\Models\Counterparty;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CounterpartyLookupController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Counterparty::query()
            ->orderBy('name')
            ->get(['id', 'type', 'name', 'phone', 'email', 'is_active']);

        return response()->json(['data' => $data]);
    }
}
