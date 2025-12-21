<?php

namespace App\Http\Controllers\API\Common;

use App\Domain\Common\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CompanyLookupController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Company::query()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_active']);

        return response()->json(['data' => $data]);
    }
}
