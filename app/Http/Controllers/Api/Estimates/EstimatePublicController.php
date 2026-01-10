<?php

namespace App\Http\Controllers\Api\Estimates;

use App\Domain\Estimates\Models\Estimate;
use App\Http\Controllers\Controller;
use App\Http\Resources\EstimatePublicResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstimatePublicController extends Controller
{
    public function show(Request $request, string $randomId): JsonResponse
    {
        $estimate = $this->loadEstimate($randomId, false);

        return response()->json([
            'data' => (new EstimatePublicResource($estimate))->toArray($request),
        ]);
    }

    public function montaj(Request $request, string $randomId): JsonResponse
    {
        $estimate = $this->loadEstimate($randomId, true);

        return response()->json([
            'data' => (new EstimatePublicResource($estimate, true))->toArray($request),
        ]);
    }

    private function loadEstimate(string $randomId, bool $hidePrices): Estimate
    {
        $estimate = Estimate::query()
            ->where('random_id', $randomId)
            ->firstOrFail();

        if ($estimate->public_revoked_at) {
            throw new HttpResponseException(
                response()->json(['message' => 'Estimate link is no longer active.'], 410)
            );
        }

        if (!$estimate->public_expires_at || $estimate->public_expires_at->isPast()) {
            throw new HttpResponseException(
                response()->json(['message' => 'Estimate link is no longer active.'], 410)
            );
        }

        $estimate->load([
            'items' => function ($query) use ($hidePrices) {
                if ($hidePrices) {
                    $query->select([
                        'id',
                        'estimate_id',
                        'product_id',
                        'qty',
                        'group_id',
                        'sort_order',
                    ]);
                }
            },
            'items.product',
            'items.product.unit',
            'items.group',
        ]);

        $estimate->loadCount('items');
        if (!$hidePrices) {
            $estimate->loadSum('items as total_sum', 'total');
        }

        return $estimate;
    }
}
