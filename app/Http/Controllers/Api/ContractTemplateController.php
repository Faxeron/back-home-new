<?php

namespace App\Http\Controllers\Api;

use App\Domain\CRM\Models\ContractTemplate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contracts\StoreContractTemplateRequest;
use App\Http\Requests\Contracts\UpdateContractTemplateRequest;
use App\Http\Resources\ContractTemplateResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 200);
        $page = (int) $request->integer('page', 1);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$companyId) {
            return response()->json(['message' => 'Missing company context.'], 403);
        }

        $query = ContractTemplate::query()
            ->with('productTypes')
            ->where('company_id', $companyId)
            ->orderBy('name');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('short_name', 'like', "%{$search}%");
            });
        }

        $templates = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($templates->items())->map(
                fn (ContractTemplate $template) => (new ContractTemplateResource($template))->toArray($request),
            ),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
                'last_page' => $templates->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $contractTemplate): JsonResponse
    {
        $template = $this->resolveTemplate($request, $contractTemplate);

        return response()->json([
            'data' => (new ContractTemplateResource($template))->toArray($request),
        ]);
    }

    public function store(StoreContractTemplateRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$companyId) {
            return response()->json(['message' => 'Missing company context.'], 403);
        }

        $data = $request->validated();

        $template = ContractTemplate::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'name' => $data['name'],
            'short_name' => $data['short_name'],
            'docx_template_path' => $data['docx_template_path'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'document_type' => $data['document_type'] ?? 'combined',
            'advance_mode' => $data['advance_mode'] ?? null,
            'advance_percent' => $data['advance_percent'] ?? null,
            'advance_product_type_ids' => $data['advance_product_type_ids'] ?? null,
            'created_by' => $user?->id,
            'updated_by' => $user?->id,
        ]);

        $this->syncProductTypes($template, $data['product_type_ids'] ?? [], $tenantId, $companyId, $user?->id);
        $template->load('productTypes');

        return response()->json([
            'data' => (new ContractTemplateResource($template))->toArray($request),
        ]);
    }

    public function update(UpdateContractTemplateRequest $request, int $contractTemplate): JsonResponse
    {
        $template = $this->resolveTemplate($request, $contractTemplate);
        $data = $request->validated();

        $template->fill([
            'name' => $data['name'] ?? $template->name,
            'short_name' => $data['short_name'] ?? $template->short_name,
            'docx_template_path' => $data['docx_template_path'] ?? $template->docx_template_path,
            'is_active' => $data['is_active'] ?? $template->is_active,
            'document_type' => $data['document_type'] ?? $template->document_type,
            'advance_mode' => $data['advance_mode'] ?? $template->advance_mode,
            'advance_percent' => array_key_exists('advance_percent', $data)
                ? $data['advance_percent']
                : $template->advance_percent,
            'advance_product_type_ids' => array_key_exists('advance_product_type_ids', $data)
                ? $data['advance_product_type_ids']
                : $template->advance_product_type_ids,
            'updated_by' => $request->user()?->id,
        ]);
        $template->save();

        if (array_key_exists('product_type_ids', $data)) {
            $this->syncProductTypes($template, $data['product_type_ids'] ?? [], $template->tenant_id, $template->company_id, $request->user()?->id);
        }

        $template->load('productTypes');

        return response()->json([
            'data' => (new ContractTemplateResource($template))->toArray($request),
        ]);
    }

    public function destroy(Request $request, int $contractTemplate): JsonResponse
    {
        $template = $this->resolveTemplate($request, $contractTemplate);
        $template->delete();

        return response()->json(['success' => true]);
    }

    private function resolveTemplate(Request $request, int $templateId): ContractTemplate
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = ContractTemplate::query()
            ->with('productTypes')
            ->where('id', $templateId);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    private function syncProductTypes(
        ContractTemplate $template,
        array $productTypeIds,
        ?int $tenantId,
        ?int $companyId,
        ?int $userId,
    ): void {
        $payload = [];
        foreach ($productTypeIds as $typeId) {
            $payload[$typeId] = [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'created_by' => $userId,
                'updated_by' => $userId,
            ];
        }
        $template->productTypes()->sync($payload);
    }
}
