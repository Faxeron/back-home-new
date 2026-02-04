<?php

namespace App\Http\Controllers\Api;

use App\Domain\Finance\Models\CashboxLogo;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CashBoxLogoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $query = CashboxLogo::query()->orderBy('name');
        if (!$request->boolean('all')) {
            $query->where('is_active', true);
        }

        $logos = $query->get()->map(function (CashboxLogo $logo) {
            return [
                'id' => $logo->id,
                'name' => $logo->name,
                'file_path' => $logo->file_path,
                'logo_url' => $this->publicStorageUrl($logo->file_path),
                'is_active' => (bool) $logo->is_active,
            ];
        });

        return response()->json(['data' => $logos]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['required', 'file', 'mimes:png'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $logoFile = $request->file('logo');
        $filePath = $this->storeLogoPng($logoFile);

        $logo = CashboxLogo::query()->create([
            'name' => $validated['name'],
            'file_path' => $filePath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'data' => [
                'id' => $logo->id,
                'name' => $logo->name,
                'file_path' => $logo->file_path,
                'logo_url' => $this->publicStorageUrl($logo->file_path),
                'is_active' => (bool) $logo->is_active,
            ],
        ], 201);
    }

    public function update(Request $request, CashboxLogo $cashboxLogo): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'logo' => ['nullable', 'file', 'mimes:png'],
            'is_active' => ['nullable', 'boolean'],
            'logo_remove' => ['nullable', 'boolean'],
        ]);

        $payload = [];
        if (array_key_exists('name', $validated)) {
            $payload['name'] = $validated['name'];
        }
        if (array_key_exists('is_active', $validated)) {
            $payload['is_active'] = (bool) $validated['is_active'];
        }

        if ($request->boolean('logo_remove') && $cashboxLogo->file_path) {
            Storage::disk('public')->delete($cashboxLogo->file_path);
            $payload['file_path'] = null;
        }

        if ($request->file('logo')) {
            if ($cashboxLogo->file_path) {
                Storage::disk('public')->delete($cashboxLogo->file_path);
            }
            $payload['file_path'] = $this->storeLogoPng($request->file('logo'));
        }

        $cashboxLogo->update($payload);

        return response()->json([
            'data' => [
                'id' => $cashboxLogo->id,
                'name' => $cashboxLogo->name,
                'file_path' => $cashboxLogo->file_path,
                'logo_url' => $cashboxLogo->file_path ? $this->publicStorageUrl($cashboxLogo->file_path) : null,
                'is_active' => (bool) $cashboxLogo->is_active,
            ],
        ]);
    }

    public function destroy(Request $request, CashboxLogo $cashboxLogo): JsonResponse
    {
        $this->ensureSuperAdmin($request);

        if ($cashboxLogo->file_path) {
            Storage::disk('public')->delete($cashboxLogo->file_path);
        }

        $cashboxLogo->delete();

        return response()->json(['status' => 'ok']);
    }

    private function publicStorageUrl(string $path): string
    {
        $normalized = ltrim($path, '/');
        return '/storage/' . $normalized;
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

    private function ensureSuperAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Only superadmins can manage logo presets.');
        }

        $userId = (int) $user->id;
        $db = DB::connection('legacy_new');

        $isSuperAdmin = false;
        if (Schema::connection('legacy_new')->hasTable('role_users') && Schema::connection('legacy_new')->hasTable('roles')) {
            $isSuperAdmin = $db->table('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->where('role_users.user_id', $userId)
                ->where(function ($query) {
                    $query->where('roles.code', 'superadmin');
                })
                ->exists();
        }

        if (!$isSuperAdmin && $userId !== 1) {
            abort(403, 'Only superadmins can manage logo presets.');
        }
    }
}
