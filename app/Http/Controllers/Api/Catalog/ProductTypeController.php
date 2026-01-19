<?php

namespace App\Http\Controllers\Api\Catalog;

use App\Domain\Catalog\Models\ProductType;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $types = ProductType::query()
            ->orderBy('id')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'data' => $types,
        ]);
    }
}
