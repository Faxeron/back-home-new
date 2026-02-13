<?php

namespace App\Http\Controllers\Api;

use App\Domain\Common\Models\User;
use App\Support\Permissions\PermissionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($payload['remember'] ?? false);

        if (!Auth::guard('web')->attempt([
            'email' => $payload['email'],
            'password' => $payload['password'],
        ], $remember)) {
            return response()->json([
                'errors' => [
                    'email' => ['Invalid credentials'],
                ],
            ], 401);
        }

        $request->session()->regenerate();

        /** @var User|null $user */
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($this->buildAuthPayload($user));
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($this->buildAuthPayload($user));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => 'ok']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!Hash::check($payload['password'], (string) $user->password)) {
            return response()->json([
                'errors' => [
                    'password' => ['Invalid password'],
                ],
            ], 422);
        }

        $guard = Auth::guard('web');

        if (method_exists($guard, 'logoutOtherDevices')) {
            $guard->logoutOtherDevices($payload['password']);
        } else {
            $user->setRememberToken(Str::random(60));
            $user->save();
        }

        if (method_exists($user, 'tokens')) {
            $user->tokens()->delete();
        }

        $this->purgeDatabaseSessionsForUser($user->id);

        $guard->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['status' => 'ok']);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAuthPayload(User $user): array
    {
        $roleCodes = $this->resolveRoleCodes($user->id);
        $role = $this->resolvePrimaryRole($roleCodes);

        return [
            'userData' => [
                'id' => $user->id,
                'fullName' => $user->name,
                'username' => $user->email,
                'email' => $user->email,
                'role' => $role,
                'tenant_id' => $user->tenant_id,
                'company_id' => $user->default_company_id ?? $user->company_id ?? null,
                'avatar' => null,
            ],
            'userAbilityRules' => $this->resolveAbilityRules($user->id, $roleCodes),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function resolveRoleCodes(int $userId): Collection
    {
        if (!Schema::connection('legacy_new')->hasTable('role_users') || !Schema::connection('legacy_new')->hasTable('roles')) {
            return collect();
        }

        return DB::connection('legacy_new')
            ->table('role_users')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->where('role_users.user_id', $userId)
            ->pluck('roles.code')
            ->map(fn ($code) => strtolower((string) $code))
            ->unique()
            ->values();
    }

    private function resolvePrimaryRole(Collection $roleCodes): string
    {
        if ($roleCodes->contains('superadmin')) {
            return 'superadmin';
        }
        if ($roleCodes->contains('admin')) {
            return 'admin';
        }
        if ($roleCodes->contains('manager')) {
            return 'manager';
        }
        if ($roleCodes->contains('measurer')) {
            return 'measurer';
        }
        if ($roleCodes->contains('worker')) {
            return 'worker';
        }

        return 'user';
    }

    /**
     * @param Collection<int, string> $roleCodes
     * @return array<int, array{action: string, subject: string}>
     */
    private function resolveAbilityRules(int $userId, Collection $roleCodes): array
    {
        $codes = $roleCodes->map(fn ($code) => strtolower((string) $code));
        if ($codes->contains('superadmin') || $codes->contains('admin')) {
            return [
                [
                    'action' => 'manage',
                    'subject' => 'all',
                ],
            ];
        }

        if (!Schema::connection('legacy_new')->hasTable('role_permissions') || !Schema::connection('legacy_new')->hasTable('permissions')) {
            return [];
        }

        PermissionRegistry::sync();

        $rows = DB::connection('legacy_new')
            ->table('role_users')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_users.user_id', $userId)
            ->select(['permissions.action', 'permissions.resource'])
            ->distinct()
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows
            ->map(fn ($row) => [
                'action' => (string) $row->action,
                'subject' => (string) $row->resource,
            ])
            ->unique()
            ->values()
            ->all();
    }

    private function purgeDatabaseSessionsForUser(int $userId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $sessionTable = (string) config('session.table', 'sessions');
        $sessionConnection = (string) (config('session.connection') ?: config('database.default'));

        if (!Schema::connection($sessionConnection)->hasTable($sessionTable)) {
            return;
        }

        DB::connection($sessionConnection)
            ->table($sessionTable)
            ->where('user_id', $userId)
            ->delete();
    }
}
