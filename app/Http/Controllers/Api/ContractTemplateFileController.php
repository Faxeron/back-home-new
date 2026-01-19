<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContractTemplateFileController extends Controller
{
    private function resolveCompanyId(Request $request): ?int
    {
        $user = $request->user();

        return $user?->default_company_id ?? $user?->company_id;
    }

    private function resolveTenantId(Request $request): ?int
    {
        return $request->user()?->tenant_id;
    }

    private function templateDirectory(Request $request): string
    {
        $tenantId = $this->resolveTenantId($request) ?? 0;
        $companyId = $this->resolveCompanyId($request) ?? 0;

        return "contracts/templates/tenant_{$tenantId}/company_{$companyId}";
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        if (!$companyId) {
            return response()->json(['message' => 'Missing company context.'], 403);
        }

        $directory = $this->templateDirectory($request);
        $files = Storage::disk('local')->files($directory);

        $data = collect($files)
            ->filter(fn (string $path) => Str::endsWith(Str::lower($path), '.docx'))
            ->map(fn (string $path) => [
                'name' => basename($path),
                'path' => $path,
            ])
            ->values();

        return response()->json(['data' => $data]);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        if (!$companyId) {
            return response()->json(['message' => 'Missing company context.'], 403);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:docx', 'max:10240'],
        ]);

        $file = $validated['file'];
        $directory = $this->templateDirectory($request);

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $baseName = Str::slug($originalName) ?: 'template';
        $fileName = "{$baseName}-" . now()->format('YmdHis') . '-' . Str::random(6) . '.docx';

        $path = $file->storeAs($directory, $fileName, 'local');

        return response()->json([
            'data' => [
                'name' => basename($path),
                'path' => $path,
            ],
        ]);
    }
}
