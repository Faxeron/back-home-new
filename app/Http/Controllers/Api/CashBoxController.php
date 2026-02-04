<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashBoxRequest;
use App\Http\Requests\UpdateCashBoxRequest;
use App\Http\Resources\CashBoxResource;
use App\Domain\Finance\Models\CashBox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CashBoxController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 25 : min($perPage, 100);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = CashBox::query()
            ->select('cashboxes.*')
            ->distinct()
            ->with(['company', 'logoPreset'])
            ->join('cashbox_company as cc', 'cc.cashbox_id', '=', 'cashboxes.id')
            ->where('cc.company_id', $companyId);

        if ($tenantId) {
            $query->where('cashboxes.tenant_id', $tenantId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $query->orderBy('name');

        $cashBoxes = $query->paginate($perPage);

        return response()->json([
            'data' => CashBoxResource::collection($cashBoxes),
            'meta' => [
                'current_page' => $cashBoxes->currentPage(),
                'per_page' => $cashBoxes->perPage(),
                'total' => $cashBoxes->total(),
                'last_page' => $cashBoxes->lastPage(),
            ],
        ]);
    }

    public function store(StoreCashBoxRequest $request): CashBoxResource
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $payload = $request->validated();
        $payload['tenant_id'] = $tenantId;
        $payload['company_id'] = $companyId;

        $logoFile = $request->file('logo');
        $logoPresetId = $request->integer('logo_preset_id');
        $logoSource = $request->string('logo_source')->toString();
        unset($payload['logo'], $payload['logo_remove'], $payload['logo_preset_id'], $payload['logo_source']);

        if ($logoPresetId) {
            $payload['logo_source'] = 'preset';
            $payload['logo_preset_id'] = $logoPresetId;
            $payload['logo_path'] = null;
        }

        if ($logoFile) {
            $payload['logo_source'] = 'custom';
            $payload['logo_preset_id'] = null;
            $payload['logo_path'] = $this->storeLogoPng($logoFile);
        }

        $cashBox = CashBox::create($payload);

        DB::connection('legacy_new')->table('cashbox_company')->updateOrInsert([
            'cashbox_id' => $cashBox->id,
            'company_id' => $companyId,
        ], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return new CashBoxResource($cashBox);
    }

    public function update(UpdateCashBoxRequest $request, CashBox $cashBox): CashBoxResource
    {
        $user = $request->user();
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $allowed = DB::connection('legacy_new')
            ->table('cashbox_company')
            ->where('cashbox_id', $cashBox->id)
            ->where('company_id', $companyId)
            ->exists();

        if (!$allowed) {
            abort(403, 'Cash box access denied.');
        }

        $payload = $request->validated();
        $logoFile = $request->file('logo');
        $removeLogo = $request->boolean('logo_remove');
        $logoPresetId = $request->integer('logo_preset_id');
        $logoSource = $request->string('logo_source')->toString();
        unset($payload['logo'], $payload['logo_remove'], $payload['logo_preset_id'], $payload['logo_source']);

        if ($removeLogo && $cashBox->logo_path) {
            Storage::disk('public')->delete($cashBox->logo_path);
            $payload['logo_path'] = null;
        }

        if ($removeLogo && $cashBox->logo_source === 'custom') {
            $payload['logo_source'] = null;
        }

        if ($logoPresetId) {
            $payload['logo_source'] = 'preset';
            $payload['logo_preset_id'] = $logoPresetId;
            $payload['logo_path'] = null;
        }

        if ($logoFile) {
            if ($cashBox->logo_path) {
                Storage::disk('public')->delete($cashBox->logo_path);
            }
            $payload['logo_source'] = 'custom';
            $payload['logo_preset_id'] = null;
            $payload['logo_path'] = $this->storeLogoPng($logoFile);
        }

        $cashBox->update($payload);

        return new CashBoxResource($cashBox);
    }

    public function destroy(Request $request, CashBox $cashBox): JsonResponse
    {
        $user = $request->user();
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $allowed = DB::connection('legacy_new')
            ->table('cashbox_company')
            ->where('cashbox_id', $cashBox->id)
            ->where('company_id', $companyId)
            ->exists();

        if (!$allowed) {
            abort(403, 'Cash box access denied.');
        }

        $hasReceipts = $cashBox->receipts()->exists();
        $hasSpendings = $cashBox->spendings()->exists();
        $hasTransactions = $cashBox->transactions()->exists();
        $hasTransfers = $cashBox->transfersFrom()->exists() || $cashBox->transfersTo()->exists();

        if ($hasReceipts || $hasSpendings || $hasTransactions || $hasTransfers) {
            return response()->json([
                'message' => 'Нельзя удалить кассу: есть транзакции, приходы, расходы или переводы.',
            ], 422);
        }

        if ($cashBox->logo_path) {
            Storage::disk('public')->delete($cashBox->logo_path);
        }

        $cashBox->delete();

        return response()->json(['status' => 'ok']);
    }

    private function storeLogoPng($logoFile): string
    {
        $filename = Str::random(40) . '.png';
        $path = "cashboxes/{$filename}";

        if (!function_exists('imagecreatefrompng')) {
            return $logoFile->storeAs('cashboxes', $filename, 'public');
        }

        $source = @imagecreatefrompng($logoFile->getRealPath());
        if (!$source) {
            return $logoFile->storeAs('cashboxes', $filename, 'public');
        }

        imagesavealpha($source, true);

        $width = imagesx($source);
        $height = imagesy($source);
        $maxSize = 256;
        $scale = min(1, $maxSize / max($width, $height));
        $targetWidth = (int) round($width * $scale);
        $targetHeight = (int) round($height * $scale);

        $target = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($target, false);
        imagesavealpha($target, true);

        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $tmp = tempnam(sys_get_temp_dir(), 'cashbox_logo_');
        imagepng($target, $tmp, 7);

        imagedestroy($source);
        imagedestroy($target);

        Storage::disk('public')->put($path, file_get_contents($tmp));
        @unlink($tmp);

        return $path;
    }
}
