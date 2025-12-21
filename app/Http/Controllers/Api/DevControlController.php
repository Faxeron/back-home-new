<?php

namespace App\Http\Controllers\Api;

use App\Domain\System\Models\DevControl;
use App\Http\Controllers\Controller;
use App\Http\Requests\DevControlUpdateRequest;
use App\Http\Resources\DevControlResource;
use Database\Seeders\DevControlSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevControlController extends Controller
{
    public function index(): JsonResponse
    {
        $items = DevControl::query()->orderBy('sort_index')->orderBy('module')->get();

        return response()->json([
            'data' => DevControlResource::collection($items),
        ]);
    }

    public function update(DevControlUpdateRequest $request, int $id): JsonResponse
    {
        $item = DevControl::query()->findOrFail($id);
        $item->update($request->validated());

        return response()->json(new DevControlResource($item));
    }

    public function syncDefaults(Request $request): JsonResponse
    {
        $defaults = DevControlSeeder::defaults();

        foreach ($defaults as $index => $payload) {
            DevControl::query()->updateOrCreate(
                ['module' => $payload['module']],
                array_merge($payload, ['sort_index' => $index + 1]),
            );
        }

        $items = DevControl::query()->orderBy('sort_index')->orderBy('module')->get();

        return response()->json([
            'data' => DevControlResource::collection($items),
        ]);
    }
}
