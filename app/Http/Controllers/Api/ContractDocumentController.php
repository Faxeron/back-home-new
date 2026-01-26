<?php

namespace App\Http\Controllers\Api;

use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractDocument;
use App\Domain\CRM\Models\ContractTemplate;
use App\Domain\CRM\Services\ContractDocumentService;
use App\Domain\Finance\Models\FinanceAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Resources\ContractDocumentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use RuntimeException;

class ContractDocumentController extends Controller
{
    public function index(Request $request, int $contract): JsonResponse
    {
        $contractModel = $this->resolveContract($request, $contract);

        $documents = ContractDocument::query()
            ->with('template')
            ->where('contract_id', $contractModel->id)
            ->orderBy('document_type')
            ->orderByDesc('version')
            ->get();

        return response()->json([
            'data' => $documents->map(
                fn (ContractDocument $document) => (new ContractDocumentResource($document))->toArray($request),
            ),
        ]);
    }

    public function store(Request $request, int $contract, ContractDocumentService $service): JsonResponse
    {
        $validated = $request->validate([
            'document_id' => ['nullable', 'integer'],
            'template_id' => ['nullable', 'integer', Rule::exists('legacy_new.contract_templates', 'id')],
        ]);

        $contractModel = $this->resolveContract($request, $contract);

        $document = null;
        if (!empty($validated['document_id'])) {
            $document = ContractDocument::query()
                ->where('id', (int) $validated['document_id'])
                ->where('contract_id', $contractModel->id)
                ->when($contractModel->tenant_id, fn ($query) => $query->where('tenant_id', $contractModel->tenant_id))
                ->when($contractModel->company_id, fn ($query) => $query->where('company_id', $contractModel->company_id))
                ->firstOrFail();
        }

        $template = null;
        if (!empty($validated['template_id'])) {
            $template = ContractTemplate::query()
                ->where('id', (int) $validated['template_id'])
                ->where('company_id', $contractModel->company_id)
                ->when($contractModel->tenant_id, fn ($query) => $query->where('tenant_id', $contractModel->tenant_id))
                ->firstOrFail();
        } elseif ($document && $document->template_id) {
            $template = ContractTemplate::query()
                ->where('id', $document->template_id)
                ->where('company_id', $contractModel->company_id)
                ->when($contractModel->tenant_id, fn ($query) => $query->where('tenant_id', $contractModel->tenant_id))
                ->firstOrFail();
        }

        try {
            $document = $service->generate($contractModel, $template, $document, $request->user()?->id);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $document->load('template');

        FinanceAuditLog::create([
            'tenant_id' => $contractModel->tenant_id,
            'company_id' => $contractModel->company_id,
            'user_id' => $request->user()?->id,
            'action' => 'contract_document.created',
            'payload' => [
                'contract_id' => $contractModel->id,
                'document_id' => $document->id,
                'template_id' => $document->template_id,
                'template_name' => $document->template?->name,
                'document_type' => $document->document_type,
                'file_name' => $document->file_name,
            ],
            'created_at' => now(),
        ]);

        return response()->json([
            'data' => (new ContractDocumentResource($document))->toArray($request),
        ]);
    }

    public function download(Request $request, int $contract, int $document)
    {
        $contractModel = $this->resolveContract($request, $contract);

        $query = ContractDocument::query()
            ->where('id', $document)
            ->where('contract_id', $contractModel->id);

        if ($contractModel->tenant_id) {
            $query->where('tenant_id', $contractModel->tenant_id);
        }

        if ($contractModel->company_id) {
            $query->where('company_id', $contractModel->company_id);
        }

        $doc = $query->firstOrFail();

        if (!$doc->file_path) {
            return response()->json(['message' => 'Файл не найден.'], 404);
        }

        $fullPath = Storage::disk('local')->path($doc->file_path);
        if (!is_file($fullPath)) {
            return response()->json(['message' => 'Файл не найден.'], 404);
        }

        return response()->download($fullPath, basename($doc->file_path));
    }

    public function destroy(Request $request, int $contract, int $document): JsonResponse
    {
        $this->ensureAdmin($request);

        $contractModel = $this->resolveContract($request, $contract);

        $query = ContractDocument::query()
            ->where('id', $document)
            ->where('contract_id', $contractModel->id);

        if ($contractModel->tenant_id) {
            $query->where('tenant_id', $contractModel->tenant_id);
        }

        if ($contractModel->company_id) {
            $query->where('company_id', $contractModel->company_id);
        }

        $doc = $query->firstOrFail();

        if ($doc->file_path) {
            Storage::disk('local')->delete($doc->file_path);
        }

        FinanceAuditLog::create([
            'tenant_id' => $contractModel->tenant_id,
            'company_id' => $contractModel->company_id,
            'user_id' => $request->user()?->id,
            'action' => 'contract_document.deleted',
            'payload' => [
                'contract_id' => $contractModel->id,
                'document_id' => $doc->id,
                'template_id' => $doc->template_id,
                'template_name' => $doc->template?->name,
                'document_type' => $doc->document_type,
                'file_name' => $doc->file_name,
            ],
            'created_at' => now(),
        ]);

        $doc->delete();

        return response()->json(['status' => 'ok']);
    }

    private function resolveContract(Request $request, int $contract): Contract
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()->where('id', $contract);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->firstOrFail();
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Only admins can delete.');
        }

        $userId = (int) $user->id;
        $db = DB::connection('legacy_new');
        $isAdmin = false;

        if (Schema::connection('legacy_new')->hasTable('role_users') && Schema::connection('legacy_new')->hasTable('roles')) {
            $isAdmin = $db->table('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->where('role_users.user_id', $userId)
                ->where(function ($query) {
                    $query->where('roles.code', 'admin');
                })
                ->exists();
        }

        $isOwner = false;
        if (Schema::connection('legacy_new')->hasTable('user_company')) {
            $isOwner = $db->table('user_company')
                ->where('user_id', $userId)
                ->where('role', 'owner')
                ->exists();
        }

        if (!$isAdmin && !$isOwner && $userId !== 1) {
            abort(403, 'Only admins can delete.');
        }
    }
}



