<?php

namespace App\Modules\PublicApi\Controllers;

use App\Domain\Common\Models\PublicLead;
use App\Http\Controllers\Controller;
use App\Modules\PublicApi\Services\PublicContextResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PublicLeadController extends Controller
{
    public function __construct(
        private readonly PublicContextResolver $contextResolver,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $citySlug = trim((string) $request->get('city', ''));
        $citySlug = $citySlug === '' ? null : $citySlug;

        $companyId = $request->integer('company_id');
        $companyId = $companyId > 0 ? $companyId : null;

        $context = $this->contextResolver->resolve($citySlug, $companyId);
        if (isset($context['error'])) {
            return response()->json([
                'error' => $context['error'],
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['nullable', 'string'],
            'product_id' => ['nullable', 'integer', 'min:1'],
            'page_url' => ['nullable', 'string', 'max:500'],
            'source' => ['nullable', 'string', 'max:64'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'validation_failed',
                'fields' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        $knownKeys = [
            'name', 'phone', 'email', 'message', 'product_id', 'page_url', 'source',
            'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
            'city', 'company_id',
        ];
        $payload = array_diff_key($request->all(), array_flip($knownKeys));

        $lead = PublicLead::query()->create([
            'tenant_id' => PublicContextResolver::TENANT_ID,
            'company_id' => (int) $context['company_id'],
            'city_id' => isset($context['city']) ? (int) $context['city']->id : null,
            'product_id' => $validated['product_id'] ?? null,
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'message' => $validated['message'] ?? null,
            'page_url' => $validated['page_url'] ?? null,
            'source' => $validated['source'] ?? 'public_api',
            'utm_source' => $validated['utm_source'] ?? null,
            'utm_medium' => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
            'utm_content' => $validated['utm_content'] ?? null,
            'utm_term' => $validated['utm_term'] ?? null,
            'payload' => empty($payload) ? null : $payload,
        ]);

        return response()->json([
            'data' => [
                'id' => (int) $lead->id,
                'status' => 'created',
            ],
        ], 201);
    }
}
