<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\PricebookImportRequest;
use App\Services\Catalog\PricebookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PricebookController extends Controller
{
    public function __construct(private readonly PricebookService $service)
    {
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id ?? 0;
        $companyId = $user?->default_company_id ?? $user?->company_id ?? 0;

        $export = $this->service->export($tenantId, $companyId);
        $fileName = 'pricebook_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download($export, $fileName);
    }

    public function template(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id ?? 0;
        $companyId = $user?->default_company_id ?? $user?->company_id ?? 0;

        $filePath = $this->service->template($tenantId, $companyId);

        if (!file_exists($filePath)) {
            $export = $this->service->export($tenantId, $companyId);
            return Excel::download($export, 'pricebook_template.xlsx');
        }

        return response()->download($filePath, 'pricebook_template.xlsx');
    }

    public function import(PricebookImportRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id ?? 0;
        $companyId = $user?->default_company_id ?? $user?->company_id ?? 0;
        $userId = $user?->id;

        $file = $request->file('file');
        $path = $file->storeAs('pricebooks/imports', 'pricebook_import_' . now()->format('Ymd_His') . '.xlsx');
        $result = $this->service->import($tenantId, $companyId, $userId, storage_path('app/' . $path));

        if (!empty($result['errors'])) {
            return response()->json([
                'message' => '?????? ?? ????????. ????????? ?????? ? ?????????.',
                'errors' => $result['errors'],
            ], 422);
        }

        return response()->json([
            'data' => $result,
        ]);
    }
}
